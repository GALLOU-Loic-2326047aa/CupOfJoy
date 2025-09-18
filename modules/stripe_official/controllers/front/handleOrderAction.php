<?php

/**
 * Copyright (c) since 2010 Stripe, Inc. (https://stripe.com)
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Stripe <https://support.stripe.com/contact/email>
 * @copyright Since 2010 Stripe, Inc.
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

use StripeOfficial\Classes\StripeProcessLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

class stripe_officialHandleOrderActionModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $confirmationToken = Tools::getValue('confirmationToken') ?: null;
        $verificationToken = Tools::getValue('verifyToken') ?: null;

        $cart = $this->context->cart;
        $failUrl = $cart->id ?
                $this->context->link->getModuleLink('stripe_official', 'orderFailure', ['cartId' => $cart->id], true) :
                'index.php?controller=order';
        if (!$cart->id_customer || !$cart->id_address_delivery || !$cart->id_address_invoice || !$this->module->active) {
            StripeProcessLogger::logError('Cart verification failed: ' . json_encode($cart), 'handleOrderAction', $cart->id);
            Tools::redirect($failUrl);
        }

        if ($confirmationToken && !$verificationToken) {
            StripeProcessLogger::logError('Confirmation token or verification token validation failed : ct: ' . $confirmationToken . ' token: ' . $verificationToken, 'handleOrderAction', $cart->id);
            Tools::redirect($failUrl);
        }

        try {
            $separateAuthAndCapture = Configuration::get(Stripe_official::CATCHANDAUTHORIZE);
            $paymentElements = Configuration::get(Stripe_official::ENABLE_PAYMENT_ELEMENTS);
            // ToDo check if this has to be rewritten
            $paymentFlow = $paymentElements ? Stripe_official::PM_PAYMENT_ELEMENTS : Stripe_official::PM_CHECKOUT;

            $paymentHandler = new PaymentHandler($this->context, $this->module);
            StripeProcessLogger::logInfo('Selected handler: ' . get_class($paymentHandler) . 'Using flow: ' . $paymentFlow, 'handleOrderAction', $cart->id);
            $redirectUrl = $paymentHandler->handlePayment($paymentFlow, $separateAuthAndCapture, $confirmationToken, $verificationToken);

            Tools::redirect($redirectUrl);
        } catch (Exception $e) {
            StripeProcessLogger::logError('Stripe Payment Error => ' . $e->getMessage() . ' - ' . $e->getTraceAsString(), 'handleOrderAction', $cart->id);

            Tools::redirect($failUrl);
        }
    }
}
