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

use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\StripeClient;
use Stripe\StripeObject;
use StripeOfficial\Classes\StripeProcessLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

class StripePaymentMethodService
{
    /**
     * @var StripeClient
     */
    private $stripeClient;

    /**
     * @param null $secretKey
     */
    public function __construct($secretKey = null)
    {
        $secretKey = $secretKey ?: Stripe_official::getSecretKey();
        $this->stripeClient = new StripeClient([
            'api_key' => $secretKey,
            'stripe_version' => Stripe_official::STRIPE_API_VERSION,
        ]);
    }

    /**
     * @param string $paymentMethodId
     *
     * @return PaymentMethod|null
     */
    public function getStripePaymentMethod($paymentMethodId)
    {
        if (!$paymentMethodId) {
            return null;
        }
        $paymentMethod = null;
        try {
            $paymentMethod = $this->stripeClient->paymentMethods->retrieve($paymentMethodId);
        } catch (ApiErrorException $e) {
            StripeProcessLogger::logError('Get Stripe Payment Method Error => ' . $e->getMessage() . '-' . $e->getTraceAsString(), 'StripePaymentMethodService');
        }

        return $paymentMethod;
    }

    /**
     * @param PaymentMethod $paymentMethod
     *
     * @return string
     */
    public function getStripePaymentMethodType($paymentMethod)
    {
        return $paymentMethod ? $paymentMethod->type : null;
    }

    /**
     * @param PaymentIntent $paymentIntent
     *
     * @return string|null
     */
    public function getStripePaymentMethodTypeByPaymentIntent($paymentIntent)
    {
        if (!$paymentIntent) {
            return null;
        }

        $paymentMethodId = isset($paymentIntent->payment_method) ? $paymentIntent->payment_method : null;
        $paymentMethod = $this->getStripePaymentMethod($paymentMethodId);
        if (!$paymentMethod) {
            return null;
        }
        $paymentMethodType = $this->getStripePaymentMethodType($paymentMethod);

        return $paymentMethodType ?: $this->getStripePaymentMethodFromStripePaymentIntent($paymentIntent);
    }

    /**
     * @param PaymentIntent $paymentIntent
     *
     * @return string|null
     */
    public function getStripePaymentMethodFromStripePaymentIntent($paymentIntent)
    {
        $paymentMethodType = null;
        if (isset($paymentIntent->payment_method_details->type)) {
            $paymentMethodType = $paymentIntent->payment_method_details->type;
        } elseif (isset($paymentIntent->payment_method_types[0])) {
            $paymentMethodType = $paymentIntent->payment_method_types[0];
        }

        return $paymentMethodType;
    }

    /**
     * @param PaymentMethod $paymentMethod
     *
     * @return StripeObject|null
     */
    public function getBillingDetailsFromStripePaymentMethod($paymentMethod)
    {
        if (!$paymentMethod) {
            return null;
        }

        return $paymentMethod->billing_details;
    }
}
