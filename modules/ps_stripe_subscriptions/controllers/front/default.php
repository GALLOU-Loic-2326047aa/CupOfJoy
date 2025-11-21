<?php

class Ps_Stripe_SubscriptionsDefaultModuleFrontController extends ModuleFrontController {
    public $auth = true;
    public $ssl = true;

    public function initContent() {
        parent::initContent();
        $customer = $this->context->customer;

        $stripe_customer_id = $this->module->createOrGetStripeCustomer(
            $customer->id,
            $customer->email,
            $customer->firstname,
            $customer->lastname
        );

        if($stripe_customer_id) {
            $this->context->smarty->assign(
                'stripe_error',
                $this->module->l('Impossible de lier le compte au service de paiement.'));
        }
        else {
            $subscription_data = $this->getSubscriptionData($stripe_customer_id);
            $this->context->smarty->assign([
                'stripe_customer_id' => $stripe_customer_id,
                'subscription_status' => $subscription_data['status'],
                'subscription_next_bill' => $subscription_data['next_bill'],
                'subscription_items' => $subscription_data['items'],
                'page_title' => $this->module->l('Gestion de mes abonnements')
            ]);
        }

        $this->setTemplate('module:ps_stripe_subscriptions/views/templates/subscription_management.tpl');
    }
    protected function getSubscriptionData($stripe_customer_id) {
        return [
            'status' => 'active',
            'next_bill' => date('d-m-Y', strtotime('+1 month')),
            'items' => [['name' => 'Abonnement Pro Mensuel', 'price' => 10.00]],
        ];
    }

}