<?php

class Ps_Stripe_SubscriptionsCheckoutModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        try {
            //Chargement des outils et de l'API
            $this->module->loadModuleClasses();
            $this->module->initStripeApi();

            $cart = $this->context->cart;
            $customer = $this->context->customer;

            //Gestion du client Stripe
            $stripeCustomerId = $this->module->createOrGetStripeCustomer(
                $customer->id,
                $customer->email,
                $customer->firstname,
                $customer->lastname
            );

            //Préparation des articles
            $lineItems = [];
            foreach ($cart->getProducts() as $product) {
                $priceId = StripePriceLink::getStripePriceIdByPsId(
                    (int)$product['id_product'],
                    (int)$product['id_product_attribute']
                );

                if (!$priceId) {
                    die("Erreur : Le produit " . $product['name'] . " (ID: " . $product['id_product'] . ") n'est pas configuré comme abonnement Stripe.");
                }

                $lineItems[] = [
                    'price' => $priceId,
                    'quantity' => (int)$product['cart_quantity'],
                ];
            }

            $validationUrl = $this->context->link->getModuleLink($this->module->name, 'validation', [], true);
            $separator = (strpos($validationUrl, '?') !== false) ? '&' : '?';
            $successUrl = $validationUrl . $separator . 'session_id={CHECKOUT_SESSION_ID}';

            //Création de la session Stripe Checkout
            $session = \Stripe\Checkout\Session::create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'subscription', // Mode abonnement actif
                'metadata' => [
                    'cart_id' => (int)$cart->id,
                ],
                'success_url' => $successUrl,
                'cancel_url' => $this->context->link->getPageLink('order', true, null, ['step' => 3]),
            ]);

            //Redirection vers Stripe
            header("Location: " . $session->url);
            exit;

        } catch (Exception $e) {
            // Affichage de l'erreur réelle en cas de problème
            die("Erreur Stripe Fatale : " . $e->getMessage());
        }
    }
}