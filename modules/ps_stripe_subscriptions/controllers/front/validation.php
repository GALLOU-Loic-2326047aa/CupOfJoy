<?php

require_once(dirname(__FILE__).'/../../classes/StripePriceLink.php');

class Ps_Stripe_SubscriptionsValidationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $this->module->loadModuleClasses();
        $this->module->initStripeApi();

        //Récupération de l'ID session (enfin corrigé !)
        $sessionId = Tools::getValue('session_id');

        if (!$sessionId || $sessionId === '{CHECKOUT_SESSION_ID}') {
            PrestaShopLogger::addLog('Erreur Validation : ID de session non remplacé par Stripe.', 3);
            return $this->displayError();
        }

        try {
            //Récupération de la session réelle chez Stripe
            $session = \Stripe\Checkout\Session::retrieve($sessionId);

            if ($session->payment_status === 'paid') {

                $id_cart = (int)$session->metadata->cart_id;
                $cart = new Cart($id_cart);
                $customer = new Customer((int)$cart->id_customer);

                //Création de la commande PrestaShop
                $this->module->validateOrder(
                    (int)$cart->id,
                    (int)Configuration::get('PS_OS_PAYMENT'),
                    (float)$cart->getOrderTotal(true, Cart::BOTH),
                    "Abonnement Stripe",
                    null,
                    ['transaction_id' => $session->payment_intent],
                    (int)$cart->id_currency,
                    false,
                    $customer->secure_key
                );

                //Redirection vers la confirmation de commande
                $confirmUrl = $this->context->link->getPageLink('order-confirmation', true, null, [
                    'id_cart' => (int)$cart->id,
                    'id_module' => (int)$this->module->id,
                    'id_order' => (int)$this->module->currentOrder,
                    'key' => $customer->secure_key
                ]);

                Tools::redirect($confirmUrl);
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Erreur fatale validation : ' . $e->getMessage(), 3);
            $this->displayError($e->getMessage());
        }
    }

    protected function displayError($msg = '')
    {
        $this->errors[] = $this->module->l('Erreur technique : ') . $msg;
        $this->redirectWithNotifications($this->context->link->getPageLink('order', true, null, ['step' => 3]));
    }
}