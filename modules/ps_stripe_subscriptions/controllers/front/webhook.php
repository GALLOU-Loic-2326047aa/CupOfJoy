<?php

class Ps_Stripe_SubscriptionsWebhookModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_header = false;
    public $display_footer = false;

    public function postProcess()
    {
        // 1. Initialisation de l'API Stripe
        try {
            $this->module->initStripeApi();
            $this->module->loadModuleClasses();
        } catch (Exception $e) {
            http_response_code(500);
            exit('Erreur Init API');
        }

        // Récupération du signal envoyé par Stripe
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try {
            // On reconstruit l'événement pour vérifier qu'il vient bien de Stripe
            $event = \Stripe\Event::constructFrom(json_decode($payload, true));
        } catch(\UnexpectedValueException $e) {
            http_response_code(400);
            exit('Payload invalide');
        }

        //Traitement selon le type d'événement
        switch ($event->type) {
            case 'invoice.paid':
                $this->handleInvoicePaid($event->data->object);
                break;

            case 'invoice.payment_failed':
                // On pourrait envoyer un mail au client ici
                PrestaShopLogger::addLog('Échec de paiement Stripe pour la facture : ' . $event->data->object->id, 3);
                break;
        }

        http_response_code(200);
        exit('Webhook OK');
    }

    /**
     * Gère la réussite d'un paiement (Initial ou Renouvellement)
     */
    protected function handleInvoicePaid($invoice)
    {
        $stripeCustomerId = $invoice->customer;
        $subscriptionId = $invoice->subscription;

        // On cherche l'ID du client PrestaShop lié à cet ID Stripe
        $id_customer_ps = StripeCustomerLink::getStripeIdByPsId($stripeCustomerId);

        if (!$id_customer_ps) {
            $id_customer_ps = Db::getInstance()->getValue('
                SELECT id_customer_ps 
                FROM '._DB_PREFIX_.'stripe_customer_link 
                WHERE id_customer_stripe = "'.pSQL($stripeCustomerId).'"'
            );
        }

        if ($id_customer_ps) {
            $this->module->processSubscriptionPaymentSuccess($invoice);
        }
    }
}