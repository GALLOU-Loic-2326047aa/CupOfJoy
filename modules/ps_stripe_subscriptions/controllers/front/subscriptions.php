<?php

class Ps_Stripe_SubscriptionsSubscriptionsModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        // 1. On vérifie si le client est connecté
        if (!$this->context->customer->isLogged()) {
            Tools::redirect('index.php?controller=authentication');
        }

        $this->module->loadModuleClasses();
        $this->module->initStripeApi();

        $subscriptions = [];

        try {
            $stripeCustomerId = Db::getInstance()->getValue('
                SELECT id_customer_stripe 
                FROM ' . _DB_PREFIX_ . 'stripe_customer_link 
                WHERE id_customer_ps = ' . (int)$this->context->customer->id
            );

            if ($stripeCustomerId) {
                // 3. Récupération des abonnements chez Stripe
                $stripeSubs = \Stripe\Subscription::all([
                    'customer' => $stripeCustomerId,
                    'expand' => ['data.plan.product']
                ]);
                $subscriptions = $stripeSubs->data;
            }

        } catch (Exception $e) {
            $this->errors[] = $this->module->l('Erreur technique : ') . $e->getMessage();
        }

        $this->context->smarty->assign([
            'subscriptions' => $subscriptions,
            'my_account_url' => $this->context->link->getPageLink('my-account'),
        ]);

        $this->setTemplate('module:ps_stripe_subscriptions/views/templates/front/subscriptions.tpl');
    }
}