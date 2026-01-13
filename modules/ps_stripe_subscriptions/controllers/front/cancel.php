<?php

class Ps_Stripe_SubscriptionsCancelModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        //le client doit être connecté
        if (!$this->context->customer->isLogged()) {
            Tools::redirect('index.php?controller=authentication');
        }

        $subscriptionId = Tools::getValue('id_sub');
        $this->module->loadModuleClasses();
        $this->module->initStripeApi();

        try {
            //On utilise id_customer_stripe et id_customer_ps pour trouver les abonnements
            $stripeCustomerId = Db::getInstance()->getValue('
                SELECT id_customer_stripe 
                FROM ' . _DB_PREFIX_ . 'stripe_customer_link 
                WHERE id_customer_ps = ' . (int)$this->context->customer->id
            );

            //Récupération de l'abonnement pour vérifier qu'il appartient bien au client
            $subscription = \Stripe\Subscription::retrieve($subscriptionId);

            if ($subscription->customer !== $stripeCustomerId) {
                die("Accès non autorisé.");
            }

            //Annulation à la fin de la période
            $subscription->cancel_at_period_end = true;
            $subscription->save();

            $this->success[] = $this->module->l('Votre abonnement sera annulé à la fin de la période en cours.');
            $this->redirectWithNotifications($this->context->link->getModuleLink($this->module->name, 'subscriptions'));

        } catch (Exception $e) {
            $this->errors[] = $this->module->l('Erreur lors de la résiliation : ') . $e->getMessage();
            $this->redirectWithNotifications($this->context->link->getModuleLink($this->module->name, 'subscriptions'));
        }
    }
}