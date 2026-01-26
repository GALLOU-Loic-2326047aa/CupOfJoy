<?php

class Ps_Stripe_SubscriptionsCheckoutModuleFrontController extends ModuleFrontController
{
    /**
     * Pilote le tunnel de commande vers Stripe Checkout (mode abonnement)
     */
    public function initContent()
    {
        parent::initContent();

        try {
            // Initialisation SDK et API
            $this->module->loadModuleClasses();
            $this->module->initStripeApi();

            $cart = $this->context->cart;
            $customer = $this->context->customer;

            // Récupère ou crée l'identifiant client Stripe (Mapping PS <-> Stripe)
            $stripeCustomerId = $this->module->createOrGetStripeCustomer(
                $customer->id,
                $customer->email,
                $customer->firstname,
                $customer->lastname
            );

            $lineItems = [];
            foreach ($cart->getProducts() as $product) {
                // Récupération de l'ID produit Stripe mappé en base locale
                $sql = 'SELECT id_product_stripe FROM ' . _DB_PREFIX_ . 'stripe_price_link 
                        WHERE id_product_ps = ' . (int)$product['id_product'] . ' 
                        AND id_product_attribute = ' . (int)$product['id_product_attribute'];
                $stripeProductId = Db::getInstance()->getValue($sql);

                if (!$stripeProductId) {
                    die("Erreur : Le produit " . $product['name'] . " n'est pas synchronisé.");
                }

                // Conversion du prix TTC en centimes pour Stripe
                $finalPrice = (int)round($product['price_wt'] * 100);

                $lineItems[] = [
                    'price_data' => [
                        'currency' => $this->context->currency->iso_code,
                        'product' => $stripeProductId,
                        'unit_amount' => $finalPrice,
                        'recurring' => [
                            'interval' => 'month', // Configuration de la récurrence par défaut
                        ],
                    ],
                    'quantity' => (int)$product['cart_quantity'],
                ];
            }

            // Construction de l'URL de retour avec le token de session Stripe
            $validationUrl = $this->context->link->getModuleLink($this->module->name, 'validation', [], true);
            $separator = (strpos($validationUrl, '?') !== false) ? '&' : '?';
            $successUrl = $validationUrl . $separator . 'session_id={CHECKOUT_SESSION_ID}';

            // Création de la session de paiement Stripe
            $session = \Stripe\Checkout\Session::create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'subscription', // Mode abonnement requis pour les objets 'recurring'
                'metadata' => [
                    'cart_id' => (int)$cart->id,
                ],
                'success_url' => $successUrl,
                'cancel_url' => $this->context->link->getPageLink('order', true, null, ['step' => 3]),
            ]);

            // Redirection vers l'interface de paiement sécurisée
            header("Location: " . $session->url);
            exit;

        } catch (Exception $e) {
            // Log d'erreur et arrêt du processus en cas d'exception API
            die("Erreur Stripe Fatale : " . $e->getMessage());
        }
    }
}