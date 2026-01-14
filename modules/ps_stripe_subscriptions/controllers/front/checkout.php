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
            $lineItems = [];
            foreach ($cart->getProducts() as $product) {
                // On cherche l'ID du prix Stripe associé au produit ou à sa déclinaison
                $priceId = StripePriceLink::getStripePriceIdByPsId(
                    (int)$product['id_product'],
                    (int)$product['id_product_attribute']
                );

                //Si un produit du panier n'est pas lié à Stripe, on arrête le processus
                if (!$priceId) {
                    die("Erreur : Le produit " . $product['name'] . " (ID: " . $product['id_product'] . ") n'est pas configuré comme abonnement Stripe.");
                }

                // Ajout de l'article à la liste d'achat
                $lineItems[] = [
                    'price' => $priceId,
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