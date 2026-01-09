<?php

class Ps_Stripe_SubscriptionsValidationModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $sessionId = Tools::getValue('session_id');

        if (!$sessionId) {
            PrestaShopLogger::addLog('Stripe Validation: Session ID manquant.', 3);
            Tools::redirect('index.php?controller=order');
        }

        try {
            $this->module->initStripeApi();

            // 2. Récupération de la session chez Stripe pour vérifier le paiement
            $session = \Stripe\Checkout\Session::retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                $cart = $this->context->cart;
                $customer = new Customer($cart->id_customer);

                // 3. Identification du mode pour le message de commande
                $isSubscription = ($session->mode === 'subscription');
                $message = $isSubscription ? 'Abonnement Stripe validé : ' : 'Paiement Stripe validé : ';
                $message .= $sessionId;

                $this->module->validateOrder(
                    (int)$cart->id,
                    (int)Configuration::get('PS_OS_PAYMENT'),
                    (float)$cart->getOrderTotal(true, Cart::BOTH),
                    $this->module->displayName,
                    $message,
                    [],
                    (int)$this->context->currency->id,
                    false,
                    $customer->secure_key
                );

                // 5. Redirection vers la page de confirmation de PrestaShop
                Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.(int)$this->module->currentOrder.'&key='.$customer->secure_key);
            } else {
                // Si le paiement n'est pas "paid", on renvoie au tunnel d'achat
                $this->errors[] = $this->module->l('Le paiement n\'a pas été confirmé par Stripe.');
                $this->redirectWithNotifications('index.php?controller=order&step=3');
            }

        } catch (Exception $e) {
            PrestaShopLogger::addLog('Erreur Validation Stripe : ' . $e->getMessage(), 3);
            $this->errors[] = $this->module->l('Erreur lors de la validation du paiement.');
            $this->redirectWithNotifications('index.php?controller=order&step=3');
        }
    }
}