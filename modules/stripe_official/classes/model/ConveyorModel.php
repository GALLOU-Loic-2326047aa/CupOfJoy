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
if (!defined('_PS_VERSION_')) {
    exit;
}

class ConveyorModel
{
    public $context;
    /**
     * @var Stripe_official|null
     */
    public $module;
    public $paymentIntentId;
    public $chargeId;
    public $paymentMethodId;
    public $status;
    public $currencyIso;
    public $cartId;
    public $cart;
    public $amount;
    public $intentPaymentMethod;
    public $intentPaymentMethodType;
    public $intentPaymentOwnerName;
    public $result;
    public $voucherUrl;
    public $voucherExpire;
    public $eventJson;
    public $eventState;

    /**
     * @param mixed $context
     */
    public function setContext($context): void
    {
        $this->context = $context;
    }

    /**
     * @param mixed $module
     */
    public function setModule($module): void
    {
        $this->module = $module;
    }

    /**
     * @param mixed $paymentIntentId
     */
    public function setPaymentIntentId($paymentIntentId): void
    {
        $this->paymentIntentId = $paymentIntentId;
    }

    /**
     * @param mixed $chargeId
     */
    public function setChargeId($chargeId): void
    {
        $this->chargeId = $chargeId;
    }

    /**
     * @param mixed $paymentMethodId
     */
    public function setPaymentMethodId($paymentMethodId): void
    {
        $this->paymentMethodId = $paymentMethodId;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @param mixed $currencyIso
     */
    public function setCurrencyIso($currencyIso): void
    {
        $this->currencyIso = $currencyIso;
    }

    /**
     * @param mixed $cartId
     */
    public function setCartId($cartId): void
    {
        $this->cartId = $cartId;
    }

    /**
     * @param mixed $cart
     */
    public function setCart($cart): void
    {
        $this->cart = $cart;
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @param mixed $intentPaymentMethod
     */
    public function setIntentPaymentMethod($intentPaymentMethod): void
    {
        $this->intentPaymentMethod = $intentPaymentMethod;
    }

    /**
     * @param mixed $intentPaymentMethodType
     */
    public function setIntentPaymentMethodType($intentPaymentMethodType): void
    {
        $this->intentPaymentMethodType = $intentPaymentMethodType;
    }

    /**
     * @param $intentPaymentOwnerName
     */
    public function setIntentPaymentOwnerName($intentPaymentOwnerName): void
    {
        $this->intentPaymentOwnerName = $intentPaymentOwnerName;
    }

    /**
     * @param mixed $result
     */
    public function setResult($result): void
    {
        $this->result = $result;
    }

    /**
     * @param mixed $voucherUrl
     */
    public function setVoucherUrl($voucherUrl): void
    {
        $this->voucherUrl = $voucherUrl;
    }

    /**
     * @param mixed $voucherExpire
     */
    public function setVoucherExpire($voucherExpire): void
    {
        $this->voucherExpire = $voucherExpire;
    }

    /**
     * @param $eventJson
     */
    public function setEventJson($eventJson): void
    {
        $this->eventJson = $eventJson;
    }

    /**
     * @param $eventState
     */
    public function setEventState($eventState): void
    {
        $this->eventState = $eventState;
    }

    //    public static function getFromContext($context): self
    //    {
    //        $psCurrency = new Currency($context->cart->id_currency);
    //        $currencyIsoCode = $psCurrency->iso_code;
    //        $psCart = $context->cart;
    //        $cartId = (int) $psCart->id;
    //
    //        $amount = $psCart->getOrderTotal();
    //        $amount = round($amount, 2);
    //        $amount = Stripe_official::isZeroDecimalCurrency($currencyIsoCode) ?
    //            (int) $amount :
    //            (int) number_format($amount * 100, 0, '', '');
    //
    //        $orderId = (int) Order::getIdByCartId($cartId);
    //        $psOrder = $orderId ? new Order($orderId) : null;
    //        $customerModel = CustomerModel::getFromContext($context);
    //        $psCustomer = $context->customer;
    //        $reference = $psOrder ? $context->shop->name . ' ' . $psOrder->reference : null;
    //        $psLanguage = new Language();
    //
    //        return new self($psCurrency, $currencyIsoCode, $cartId, $psCart, $amount, $orderId, $psOrder, $customerModel, $psCustomer, $reference, $psLanguage);
    //    }
}
