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

use Stripe\Checkout\Session;
use StripeOfficial\Classes\StripeProcessLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CheckoutFlowHandlerNew implements FlowHandlerInterface
{
    /**
     * @var Context
     */
    private $context;
    /**
     * @var Module
     */
    private $module;
    /**
     * @var StripeCheckoutSessionService
     */
    private $stripeCheckoutSessionService;
    /**
     * @var PrestashopBuildOrderService
     */
    private $prestashopBuildOrderService;
    /**
     * @var StripePaymentIntentService
     */
    private $stripePaymentIntentService;
    /**
     * @var PrestashopOrderService
     */
    private $prestashopOrderService;

    private $stripeAnonymize;

    /**
     * @param Context $context
     * @param Stripe_official $module
     * @param string|null $secretKey
     */
    public function __construct($context, $module, $secretKey = null)
    {
        $this->context = $context;
        $this->module = $module;
        $secretKey = $secretKey ?: Stripe_official::getSecretKey();
        $this->stripeCheckoutSessionService = new StripeCheckoutSessionService($secretKey);
        $this->prestashopBuildOrderService = new PrestashopBuildOrderService($this->context, $this->module, $secretKey);
        $this->stripePaymentIntentService = new StripePaymentIntentService($secretKey);
        $this->prestashopOrderService = new PrestashopOrderService($this->context, $this->module, $secretKey);
        $this->module->setStripeAppInformation();
        $this->stripeAnonymize = new StripeAnonymize();
    }

    /**
     * @param bool $separateAuthAndCapture
     *
     * @return string|null
     */
    public function handlePayment($separateAuthAndCapture)
    {
        return $this->getStripeCheckoutUrl($separateAuthAndCapture);
    }

    /**
     * @param bool $separateAuthAndCapture
     *
     * @return string|null
     */
    public function getStripeCheckoutUrl($separateAuthAndCapture)
    {
        $cartContextModel = CartContextModel::getFromContext($this->context);

        $failReturnUrl = $this->context->link->getModuleLink(
            'stripe_official',
            'orderFailure',
            ['cartId' => $cartContextModel->cartId],
            true);

        $successReturnUrl = $this->context->link->getModuleLink(
            'stripe_official',
            'orderConfirmationReturn',
            ['cartId' => $cartContextModel->cartId],
            true
        );

        $cart = new Cart($cartContextModel->cartId);
        $orderModel = $this->prestashopBuildOrderService->buildAndCreatePrestashopOrder(null, null, $cartContextModel, $cart);

        $checkoutSession = $this->stripeCheckoutSessionService->createCheckoutSession($cartContextModel, $separateAuthAndCapture, $successReturnUrl, $failReturnUrl, $orderModel);
        $checkoutSessionAnonymized = $this->stripeAnonymize->anonymize($checkoutSession);
        StripeProcessLogger::logInfo('Checkout flow handler - checkout session: ' . json_encode($checkoutSessionAnonymized), 'CheckoutFlowHandlerNew', $cartContextModel->cartId);

        if (!$orderModel->orderReference || !$orderModel->orderId) {
            StripeProcessLogger::logWarning('Order was not created in PrestaShop from Checkout Flow ' . json_encode($orderModel), 'CheckoutFlowHandlerNew', $cartContextModel->cartId);

            return $failReturnUrl;
        }

        $this->prestashopOrderService->createPsStripePaymentFromSession($checkoutSession, $orderModel);

        return ($checkoutSession instanceof Session) ? $checkoutSession->url : $failReturnUrl;
    }
}
