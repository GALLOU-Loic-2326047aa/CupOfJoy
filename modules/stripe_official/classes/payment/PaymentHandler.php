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

class PaymentHandler implements PaymentHandlerInterface
{
    private $context;
    /**
     * @var Stripe_official
     */
    private $module;

    public function __construct($context, $module)
    {
        $this->context = $context;
        $this->module = $module;
        $this->module->setStripeAppInformation();
    }

    /**
     * @throws Exception
     */
    public function handlePayment($paymentFlow, $separateAuthAndCapture, $confirmationToken = null, $verificationToken = null)
    {
        if (!$this->isSupportedPaymentFlow($paymentFlow)) {
            throw new Exception("The payment flow: $paymentFlow is not supported.");
        }

        $handler = $this->getPaymentFlowHandler($paymentFlow, $this->context, $this->module, $confirmationToken, $verificationToken);

        if (!$handler) {
            throw new Exception("The handler for payment flow: $paymentFlow is not found.");
        }

        return $handler->handlePayment($separateAuthAndCapture);
    }

    public function getPaymentFlowHandler($paymentFlow, $context, $module, $confirmationToken, $verificationToken)
    {
        $handler = null;
        $newOrderFlow = !(int) Configuration::get(Stripe_official::ORDER_FLOW);

        switch ($paymentFlow) {
            case Stripe_official::PM_PAYMENT_ELEMENTS:
                $handler = $newOrderFlow ?
                    new ElementsFlowHandlerNew($context, $module, $confirmationToken, $verificationToken) :
                    new ElementsFlowHandler($context, $module, $confirmationToken, $verificationToken);

                break;
            case Stripe_official::PM_CHECKOUT:
                $handler = $newOrderFlow ?
                    new CheckoutFlowHandlerNew($context, $module) :
                    new CheckoutFlowHandler($context, $module)
                ;
                break;
        }

        StripeProcessLogger::logInfo('Confirmation token ' . json_encode($confirmationToken) . ' Selected handler: ' . get_class($handler), 'PaymentHandler');

        return $handler;
    }

    public function isSupportedPaymentFlow($paymentFlow)
    {
        return $paymentFlow && in_array($paymentFlow, Stripe_official::$allowedPaymentFlows);
    }
}
