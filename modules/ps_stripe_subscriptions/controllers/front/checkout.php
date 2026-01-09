<?php

class Ps_Stripe_SubscriptionsCheckoutModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $cart = $this->context->cart;
        $customer = $this->context->customer;

        // 1. Sécurité : Client et panier valides
        if (!Validate::isLoadedObject($customer) || !$customer->isLogged() || $cart->nbProducts() == 0) {
            Tools::redirect('index.php?controller=order');
        }

        try {
            $this->module->initStripeApi(); //
            if (method_exists($this->module, 'loadModuleClasses')) {
                $this->module->loadModuleClasses(); //
            }

            $line_items = [];
            $has_subscription = false;
            $has_classic_product = false;
            $products = $cart->getProducts();

            // 2. Analyse du panier pour détecter le type de produits
            foreach ($products as $product) {
                // On vérifie si ce produit spécifique est un abonnement dans baba_stripe_price_link
                $stripe_price_id = StripePriceLink::getStripePriceIdByPsId((int)$product['id_product']);

                if ($stripe_price_id) {
                    $has_subscription = true;
                    $line_items[] = [
                        'price' => $stripe_price_id,
                        'quantity' => (int)$product['cart_quantity'],
                    ];
                } else {
                    $has_classic_product = true;
                    $line_items[] = [
                        'price_data' => [
                            'currency' => $this->context->currency->iso_code,
                            'product_data' => [
                                'name' => $product['name'],
                            ],
                            'unit_amount' => (int)number_format($product['price_wt'] * 100, 0, '', ''),
                        ],
                        'quantity' => (int)$product['cart_quantity'],
                    ];
                }
            }

            // 3. Gestion de l'incompatibilité des modes Stripe
            if ($has_subscription && $has_classic_product) {
                throw new Exception("Attention : Votre panier contient un mélange d'abonnement et d'achat unique. Veuillez commander vos abonnements séparément.");
            }

            // 4. Définition du mode
            $mode = $has_subscription ? 'subscription' : 'payment'; //

            // 5. Création de la session
            $session_params = [
                'payment_method_types' => ['card'],
                'line_items' => $line_items,
                'mode' => $mode,
                'billing_address_collection' => 'required',
                'success_url' => $this->context->link->getModuleLink($this->module->name, 'validation', ['session_id' => '{CHECKOUT_SESSION_ID}'], true),
                'cancel_url' => $this->context->link->getModuleLink($this->module->name, 'checkout', ['cancel' => 1], true),
            ];

            // On ajoute le client s'il est déjà lié à Stripe
            $stripe_customer_id = $this->module->createOrGetStripeCustomer($customer->id, $customer->email, $customer->firstname, $customer->lastname);
            if ($stripe_customer_id) {
                $session_params['customer'] = $stripe_customer_id;
            }

            $session = \Stripe\Checkout\Session::create($session_params);

            Tools::redirect($session->url);

        } catch (Exception $e) {
            PrestaShopLogger::addLog('Stripe Checkout Error: ' . $e->getMessage(), 3);
            $this->errors[] = $this->module->l('Erreur de paiement : ') . $e->getMessage();
            $this->redirectWithNotifications('index.php?controller=order&step=3');
        }
    }
}