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

use Stripe\PaymentIntent;
use StripeOfficial\Classes\StripeProcessLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PrestashopBuildOrderService
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
     * @var PrestashopOrderService
     */
    private $prestashopOrderService;

    /**
     * @param string|null $secretKey
     */
    public function __construct($context, $module, $secretKey = null)
    {
        $this->context = $context;
        $this->module = $module;
        $secretKey = $secretKey ?: Stripe_official::getSecretKey();
        $this->prestashopOrderService = new PrestashopOrderService($this->context, $this->module, $secretKey);
    }

    public function buildAndCreatePrestashopOrder(
        ?StripePaymentIntent $stripePaymentIntent = null,
        ?PaymentIntent $paymentIntent = null,
        ?CartContextModel $cartContextModel = null,
        ?Cart $prestashopCart = null,
        ?string $stripePaymentMethodId = null): OrderModel
    {
        $orderModel = $this->prestashopOrderService->buildOrderModel($stripePaymentIntent, $paymentIntent, $prestashopCart, $cartContextModel, $stripePaymentMethodId);
        StripeProcessLogger::logInfo('Build order model ' . json_encode($orderModel), 'PrestashopBuildOrderService');

        $orderModel = $this->prestashopOrderService->createPsOrder($orderModel);
        StripeProcessLogger::logInfo('Create order model ' . json_encode($orderModel), 'PrestashopBuildOrderService');

        return $orderModel;
    }
}
