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

use PrestaShop\PrestaShop\Core\Domain\Order\Exception\OrderException;
use StripeOfficial\Classes\StripeProcessLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PrestashopCartService
{
    public function checkDiscountCouponForCart($cart)
    {
        $cartRules = $cart->getCartRules();
        $discountDetails = [
            'free_shipping' => false,
            'percentage_discount' => 0,
            'fixed_discount' => 0,
            'total_discount' => 0,
        ];

        foreach ($cartRules as $cartRule) {
            // free shipping
            if ($cartRule['free_shipping']) {
                $discountDetails['free_shipping'] = true;
            }
            // percentage discount
            if ($cartRule['reduction_percent']) {
                $discountDetails['percentage_discount'] += $cartRule['reduction_percent'];
            }
            // fixed discount
            if ($cartRule['reduction_amount']) {
                $discountDetails['fixed_discount'] += $cartRule['reduction_amount'];
            }
        }

        // total discount
        $discountDetails['total_discount'] = $discountDetails['percentage_discount'] + $discountDetails['fixed_discount'];

        StripeProcessLogger::logInfo('Discount details => ' . json_encode($discountDetails), 'PrestashopCartService', $cart->id);

        return $discountDetails;
    }

    public static function createPrestashopCart($psCustomer)
    {
        $psCart = new Cart();
        $psCart->id_customer = $psCustomer->id;
        $psCart->secure_key = $psCustomer->secure_key;
        $psCart->id_guest = Context::getContext()->cookie->id_guest;
        $psCart->id_currency = Context::getContext()->cookie->id_currency;
        $psCart->id_shop_group = Context::getContext()->shop->id_shop_group;
        $psCart->id_lang = Context::getContext()->cookie->id_lang;
        $psCart->recyclable = 0;
        $psCart->gift = 0;
        $psCart->add();

        return $psCart;
    }

    public function createPrestashopCartProduct($expressParams, $cart, $idProductAttribute)
    {
        $customizationId = Customization::getOrderedCustomizations((int) $cart->id);

        $sql = new DbQuery();
        $sql->select('count(*)');
        $sql->from('cart_product', 'cp');
        $sql->where('id_cart = ' . (int) $cart->id);
        $result = Db::getInstance()->getValue($sql);

        if ($result == 0) {
            $result = $cart->updateQty(
                (int) $expressParams['productQuantity'] ?? '',
                (int) $expressParams['productId'],
                (int) $idProductAttribute,
                $customizationId,
                'up',
                $cart->id_address_delivery,
                new Shop($cart->id_shop),
                true,
                true
            );
            if (!$result) {
                throw new OrderException(sprintf('Product with id "%s" is out of stock.', $expressParams['productId']));
            }

            StripeProcessLogger::logInfo('Create Prestashop Cart Product => ' . json_encode($result), 'PrestashopCartService', $cart->id);
        }
    }

    public function updatePrestashopCartProduct($addressDeliveryId, $cartId)
    {
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'cart_product`
        SET `id_address_delivery` = ' . (int) $addressDeliveryId . '
        WHERE  `id_cart` = ' . (int) $cartId;

        StripeProcessLogger::logInfo('Update Prestashop Cart Product => ' . $sql, 'createIntent', $cartId);
        Db::getInstance()->execute($sql);
    }

    public function updatePrestashopCart($delivery_option, $cartId)
    {
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'cart
        SET delivery_option = \'' . $delivery_option . '\'
        WHERE id_cart = ' . (int) $cartId;

        Db::getInstance()->execute($sql);
    }
}
