<?php

class Ps_Stripe_SubscriptionsWebhookModuleFrontController extends ModuleFrontController
{
    // Webhooks ne nécessitent pas de session utilisateur ou de rendu HTML
    public $ssl = true;
    public $display_header = false;
    public $display_footer = false;

    // Définition des constantes de réponse
    const HTTP_OK = 200;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_INTERNAL_ERROR = 500;

    public function postProcess()
    {
        // 1. Initialisation Stripe
        try {
            $this->module->initStripeApi();
        } catch (Exception $e) {
            // Échec de l'initialisation (clé non configurée, etc.)
            http_response_code(self::HTTP_INTERNAL_ERROR);
            exit;
        }

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

        $endpoint_secret = Configuration::get('PS_STRIPE_WEBHOOK_SECRET');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch(\UnexpectedValueException $e) {
            http_response_code(self::HTTP_BAD_REQUEST); // Payload invalide
            exit;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            http_response_code(self::HTTP_BAD_REQUEST); // Signature invalide
            exit;
        }

        // 3. TRAITEMENT DE L'ÉVÉNEMENT
        $this->handleStripeEvent($event);

        // Réponse finale (atteinte seulement si le traitement est terminé)
        http_response_code(self::HTTP_OK);
        exit;
    }

    /**
     * Traite l'événement Stripe (événement.type)
     */
    public function handleStripeEvent($event)
    {
        $object = $event->data->object;

        switch ($event->type) {
            // Événement critique pour la validation du paiement et la création de la commande PS
            case 'invoice.paid':
                $this->module->processSubscriptionPaymentSuccess($object);
                break;

            // Événement critique pour la suspension de service
            case 'invoice.payment_failed':
                $this->module->processSubscriptionPaymentFailure($object);
                break;

            case 'customer.subscription.deleted':
                $this->module->processSubscriptionDeletion($object);
                break;

            default:
                break;
        }
    }

    /**
     * Récupère l'ID Client PrestaShop à partir de l'ID Client Stripe (cus_XXXXXX)
     */
    protected function getIdCustomerByStripeId($stripe_customer_id)
    {
        if (!class_exists('StripeCustomerLink')) {
            require_once(_PS_MODULE_DIR_ . $this->module->name . '/classes/StripeCustomerLink.php');
        }

        $id_customer_ps = Db::getInstance()->getValue('
            SELECT `id_customer_ps`
            FROM `' . _DB_PREFIX_ . 'stripe_customer_link`
            WHERE `id_customer_stripe` = "' . pSQL($stripe_customer_id) . '"
        ');

        return $id_customer_ps;
    }

    public function getPageName()
    {
        return 'stripe-webhook';
    }
}