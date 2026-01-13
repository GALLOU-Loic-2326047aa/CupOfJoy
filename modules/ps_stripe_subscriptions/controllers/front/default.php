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

        // 2. On récupère les abonnements Stripe du client
        $subscriptions = [];
        try {
            // On cherche l'ID client Stripe associé à l'ID PrestaShop
            $stripeCustomerId = Db::getInstance()->getValue('
                SELECT stripe_customer_id FROM ' . _DB_PREFIX_ . 'baba_stripe_customer_link 
                WHERE id_customer = ' . (int)$this->context->customer->id
            );

            if ($stripeCustomerId) {
                $stripeSubs = \Stripe\Subscription::all(['customer' => $stripeCustomerId]);
                $subscriptions = $stripeSubs->data;
            }
        } catch (Exception $e) {
            $this->errors[] = $this->module->l('Impossible de charger vos abonnements.');
        }

        $this->context->smarty->assign([
            'subscriptions' => $subscriptions,
            'my_account_url' => $this->context->link->getPageLink('my-account'),
        ]);

        $this->setTemplate('module:ps_stripe_subscriptions/views/templates/front/subscriptions.tpl');
    }
}