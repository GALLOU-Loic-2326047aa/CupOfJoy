<?php

require_once(dirname(__FILE__).'/../../classes/StripePriceLink.php');

class Ps_Stripe_SubscriptionsValidationModuleFrontController extends ModuleFrontController
{
    /**
     * Gère le retour du client après le paiement réussi sur Stripe
     */
    public function postProcess()
    {
        // Charge les outils du module et connecte l'API Stripe
        $this->module->loadModuleClasses();
        $this->module->initStripeApi();

        // Récupération de l'ID de session Checkout envoyé par Stripe dans l'URL
        $sessionId = Tools::getValue('session_id');

        // Sécurité : on vérifie que l'ID de session est présent et valide
        if (!$sessionId || $sessionId === '{CHECKOUT_SESSION_ID}') {
            PrestaShopLogger::addLog('Erreur Validation : ID de session non remplacé par Stripe.', 3);
            return $this->displayError();
        }

        try {
            // On demande à Stripe les détails réels de cette session de paiement
            $session = \Stripe\Checkout\Session::retrieve($sessionId);

            // Si le paiement est bien confirmé ("paid")
            if ($session->payment_status === 'paid') {

                // On récupère le panier et le client grâce aux métadonnées stockées lors du checkout
                $id_cart = (int)$session->metadata->cart_id;
                $cart = new Cart($id_cart);
                $customer = new Customer((int)$cart->id_customer);

                // Transformation du panier en commande officielle dans PrestaShop
                $this->module->validateOrder(
                    (int)$cart->id,
                    (int)Configuration::get('PS_OS_PAYMENT'), // État "Paiement accepté"
                    (float)$cart->getOrderTotal(true, Cart::BOTH),
                    "Abonnement Stripe", // Nom du mode de paiement affiché
                    null,
                    ['transaction_id' => $session->payment_intent], // ID de transaction Stripe
                    (int)$cart->id_currency,
                    false,
                    $customer->secure_key
                );

                // Construction de l'URL de confirmation de commande standard de PrestaShop
                $confirmUrl = $this->context->link->getPageLink('order-confirmation', true, null, [
                    'id_cart' => (int)$cart->id,
                    'id_module' => (int)$this->module->id,
                    'id_order' => (int)$this->module->currentOrder,
                    'key' => $customer->secure_key
                ]);

                // Redirection finale du client vers la page de remerciement
                Tools::redirect($confirmUrl);
            }
        } catch (Exception $e) {
            // Enregistre l'erreur dans les logs de PrestaShop en cas de panne
            PrestaShopLogger::addLog('Erreur fatale validation : ' . $e->getMessage(), 3);
            $this->displayError($e->getMessage());
        }
    }

    /**
     * Affiche un message d'erreur et renvoie le client à l'étape du paiement
     */
    protected function displayError($msg = '')
    {
        $this->errors[] = $this->module->l('Erreur technique : ') . $msg;
        $this->redirectWithNotifications($this->context->link->getPageLink('order', true, null, ['step' => 3]));
    }
}