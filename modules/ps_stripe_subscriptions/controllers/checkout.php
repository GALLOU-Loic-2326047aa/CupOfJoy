<?php

class Ps_Stripe_SubscriptionsCheckoutModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        /* 1. Initialisation Stripe */
        try {
            $this->module->initStripeApi();
        } catch (Exception $e) {
            $this->errors[] = $this->module->l('Erreur de configuration Stripe. Veuillez contacter un administrateur.');
            return $this->setTemplate('module:ps_stripe_subscriptions/views/templates/front/error.tpl');
        }

        /* 2. Vérifications PrestaShop */
        $customer = $this->context->customer;
        $cart = $this->context->cart;

        if (!$customer->isLogged() || $cart->nbProducts() == 0) {
            Tools::redirect('index.php?controller=authentication&back=order');
        }

        /* 3. Récupération / création du client Stripe */
        $stripe_customer_id = $this->module->createOrGetStripeCustomer(
            $customer->id,
            $customer->email,
            $customer->firstname,
            $customer->lastname
        );

        if (!$stripe_customer_id) {
            $this->errors[] = $this->module->l('Impossible de lier votre compte au service de paiement.');
            return $this->setTemplate('module:ps_stripe_subscriptions/views/templates/front/error.tpl');
        }

        /* 4. Récupération du prix de l'abonnement */
        $stripe_price_service_id = Configuration::get('PS_STRIPE_SERVICE_PRICE_ID');

        if (!$stripe_price_service_id) {
            $this->errors[] = $this->module->l('Prix d’abonnement Stripe non configuré.');
            return $this->setTemplate('module:ps_stripe_subscriptions/views/templates/front/error.tpl');
        }

        /* 5. Calcul du montant variable du panier */
        $cart_total = $cart->getOrderTotal(true, Cart::BOTH);
        $cart_total_cents = (int)($cart_total * 100);

        /* 6. Création de la Session  Checkout*/
        try {
            $checkout_session = \Stripe\Checkout\Session::create([
                'customer' => $stripe_customer_id,
                'payment_method_types' => ['card'],

                'line_items' => [
                    [
                        'price' => $stripe_price_service_id,
                        'quantity' => 1,
                    ],
                ],

                // On ajoute les frais ponctuels sur la première facture uniquement
                'subscription_data' => [
                    'add_invoice_items' => [
                        [
                            'price_data' => [
                                'currency' => $this->context->currency->iso_code,
                                'unit_amount' => $cart_total_cents,
                                'product_data' => [
                                    'name' => $this->module->l('Frais de location du panier'),
                                ],
                            ],
                            'quantity' => 1,
                        ],
                    ],
                ],

                'mode' => 'subscription',

                // URLs de redirection
                'success_url' => $this->context->link->getModuleLink(
                        $this->module->name,
                        'validation'
                    ) . '&session_id={CHECKOUT_SESSION_ID}',

                'cancel_url' => $this->context->link->getModuleLink(
                    $this->module->name,
                    'cancel'
                ),
            ]);

            Tools::redirect($checkout_session->url);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->errors[] = $this->module->l('Erreur Stripe lors du checkout : ') . $e->getMessage();
            return $this->setTemplate('module:ps_stripe_subscriptions/views/templates/front/error.tpl');
        }
    }

    public function getPageName()
    {
        return 'stripe-checkout';
    }
}
