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

class CheckoutFlowHandler implements FlowHandlerInterface
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
     * @var StripePaymentIntentService
     */
    private $stripePaymentIntentService;

    /**
     * @var PrestashopOrderService
     */
    private $prestashopOrderService;

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
        $this->stripePaymentIntentService = new StripePaymentIntentService($secretKey);
        $this->prestashopOrderService = new PrestashopOrderService($this->context, $this->module, $secretKey);
        $this->module->setStripeAppInformation();
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

        $failReturnUrl = $this->context->link->getPageLink('order');

        $successReturnUrl = $this->context->link->getModuleLink(
            'stripe_official',
            'orderConfirmationReturn',
            ['cartId' => $cartContextModel->cartId],
            true
        );

        $checkoutSession = $this->stripeCheckoutSessionService->createCheckoutSession($cartContextModel, $separateAuthAndCapture, $successReturnUrl, $failReturnUrl);
        $duplicatedCart = $this->prestashopOrderService->duplicatePrestaShopCart(new Cart($cartContextModel->cartId));
        if (!$duplicatedCart) {
            StripeProcessLogger::logWarning('New cart cannot be created', 'CheckoutFlowHandler', $cartContextModel->cartId);

            return $failReturnUrl;
        }

        return ($checkoutSession instanceof Session) ? $checkoutSession->url : $failReturnUrl;
    }
}
