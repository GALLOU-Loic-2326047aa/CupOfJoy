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

use StripeOfficial\Classes\StripeProcessLogger;

class ProductContextModel
{
    public $currency;
    public $currencyIsoCode;
    public $cartId;
    public $cart;
    public $amount;
    public $orderId;
    public $order;
    public $customerModel;
    public $customer;
    public $reference;
    public $language;
    public $phone;

    /**
     * @param Currency $psCurrency
     * @param string $currencyIsoCode
     * @param int $cartId
     * @param Cart $psCart
     * @param int $amount
     * @param int $orderId
     * @param Order|null $psOrder
     * @param CustomerModel $customerModel
     * @param Customer $psCustomer
     * @param string $reference
     * @param Language $psLanguage
     * @param string $phone
     */
    public function __construct($psCurrency, $currencyIsoCode, $cartId, $psCart, $amount, $orderId, $psOrder, $customerModel, $psCustomer, $reference, $psLanguage, $phone)
    {
        $this->currency = $psCurrency;
        $this->currencyIsoCode = $currencyIsoCode;
        $this->cartId = $cartId;
        $this->cart = $psCart;
        $this->amount = $amount;
        $this->orderId = $orderId;
        $this->order = $psOrder;
        $this->customerModel = $customerModel;
        $this->customer = $psCustomer;
        $this->reference = $reference;
        $this->language = $psLanguage;
        $this->phone = $phone;
    }

    public static function getFromExpressParams($expressParams, $amount, $context): self
    {
        $psCurrency = new Currency($context->cart->id_currency);
        $currencyIsoCode = $psCurrency->iso_code;
        $psCart = $context->cart;
        $cartId = (int) $psCart->id;

        StripeProcessLogger::logInfo('getFromExpressParams is called with ' . json_encode(['amount' => $amount]), 'ProductContextModel', $cartId);
        if (!$amount && $psCart->getOrderTotal()) {
            $amount = round($psCart->getOrderTotal(), 2);
            $amount = Stripe_official::isZeroDecimalCurrency($currencyIsoCode) ?
                (int) $amount :
                (int) number_format($amount * 100, 0, '', '');
            StripeProcessLogger::logInfo('getFromExpressParams amount not set - recalculated ' . json_encode(['amount' => $amount, 'order_total' => $psCart->getOrderTotal()]), 'ProductContextModel', $cartId);
        }

        $orderId = (int) Order::getIdByCartId($cartId) ?: null;
        $psOrder = $orderId ? new Order($orderId) : null;

        $customerModel = CustomerModel::getFromExpressParams($expressParams, $context);
        $psCustomer = $context->customer;
        $reference = $psOrder ? ($context->shop->name . ' ' . $psOrder->reference) : $context->shop->name;
        $psLanguage = new Language();
        $addressDetails = new Address($psCart->id_address_invoice);
        $phone = $addressDetails->phone;

        return new self($psCurrency, $currencyIsoCode, $cartId, $psCart, $amount, $orderId, $psOrder, $customerModel, $psCustomer, $reference, $psLanguage, $phone);
    }
}
