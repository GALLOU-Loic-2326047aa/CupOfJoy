<?php

class Ps_Stripe_SubscriptionsCheckoutModuleFrontController extends ModuleFrontController
{
    /**
     * Initialise le processus de paiement Stripe Checkout
     */
    public function initContent()
    {
        parent::initContent();

        try {
            // Chargement des classes du module et initialisation de l'API avec la clé secrète
            $this->module->loadModuleClasses();
            $this->module->initStripeApi();

            // Récupération du panier et du client connecté dans PrestaShop
            $cart = $this->context->cart;
            $customer = $this->context->customer;

            // Récupère l'ID client Stripe existant ou en crée un nouveau pour ce client
            $stripeCustomerId = $this->module->createOrGetStripeCustomer(
                $customer->id,
                $customer->email,
                $customer->firstname,
                $customer->lastname
            );

            // Préparation de la liste des produits pour Stripe
            // Préparation de la liste des articles
            $lineItems = [];
            foreach ($cart->getProducts() as $product) {
                $sql = 'SELECT id_product_stripe FROM ' . _DB_PREFIX_ . 'stripe_price_link 
                        WHERE id_product_ps = ' . (int)$product['id_product'] . ' 
                        AND id_product_attribute = ' . (int)$product['id_product_attribute'];
                $stripeProductId = Db::getInstance()->getValue($sql);

                if (!$stripeProductId) {
                    die("Erreur : Le produit " . $product['name'] . " n'est pas synchronisé.");
                }

                // 2. ON RÉCUPÈRE LE PRIX CALCULÉ PAR PRESTASHOP (avec la réduction Pro)
                $finalPrice = (int)round($product['price_wt'] * 100);

                // 3. On envoie un "Prix Dynamique" à Stripe
                $lineItems[] = [
                    'price_data' => [
                        'currency' => $this->context->currency->iso_code,
                        'product' => $stripeProductId, // On lie au produit existant
                        'unit_amount' => $finalPrice,  // On envoie le prix réduit calculé par ton ami
                        'recurring' => [
                            'interval' => 'month',    // On précise que c'est un abonnement
                        ],
                    ],
                    'quantity' => (int)$product['cart_quantity'],
                ];
            }

            // Construction de l'URL de retour après paiement (inclut l'ID de session pour la validation)
            $validationUrl = $this->context->link->getModuleLink($this->module->name, 'validation', [], true);
            $separator = (strpos($validationUrl, '?') !== false) ? '&' : '?';
            $successUrl = $validationUrl . $separator . 'session_id={CHECKOUT_SESSION_ID}';

            // Création de la session de paiement sur les serveurs de Stripe
            $session = \Stripe\Checkout\Session::create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'subscription', //Définit que l'achat est un abonnement récurrent
                'metadata' => [
                    'cart_id' => (int)$cart->id, //On stocke l'ID du panier pour le récupérer à la validation
                ],
                'success_url' => $successUrl,
                'cancel_url' => $this->context->link->getPageLink('order', true, null, ['step' => 3]),
            ]);

            //Redirection immédiate du client vers la page de paiement sécurisée de Stripe
            header("Location: " . $session->url);
            exit;

        } catch (Exception $e) {
            //En cas d'erreur critique, affiche l'erreur
            die("Erreur Stripe Fatale : " . $e->getMessage());
        }
    }
}