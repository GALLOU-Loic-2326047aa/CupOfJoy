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

class stripe_officialCalculateShippingModuleFrontController extends ModuleFrontController
{
    /**
     * @var PrestashopCartService
     */
    private $prestashopCartService;

    private $stripeAnonymize;

    public function __construct()
    {
        parent::__construct();
        $this->prestashopCartService = new PrestashopCartService();
        $this->stripeAnonymize = new StripeAnonymize();
    }

    /**
     * @throws OrderException
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        $values = @Tools::file_get_contents('php://input');
        $content = json_decode($values, true);
        $contentAnonymized = $this->stripeAnonymize->anonymize($content);
        $shippingAddress = $content['shippingAddress'];

        $countryId = (int) Country::getByIso($shippingAddress['country'], true);
        if (!$countryId) {
            StripeProcessLogger::logError('CalculateShipping - Shipping country unavailable - content => ' . json_encode($contentAnonymized), 'calculateShipping', Context::getContext()->cart->id);
            echo json_encode(['error' => true, 'message' => 'Shipping country unavailable']);
            exit;
        }
        Context::getContext()->country = new Country($countryId); // set the contry in context. Using this prestashop will canculate the corect taxes automatically

        if (Context::getContext()->cart->id) {
            $cart = new Cart(Context::getContext()->cart->id);
        }

        $idProductAttribute = null;
        if ($content['expressCheckoutType'] === 'product') {
            if ($content['idProductAttribute']) {
                $idProductAttribute = $content['idProductAttribute'];
            }

            $cart = $this->prestashopCartService->createPrestashopCart(Context::getContext()->customer);
            $this->prestashopCartService->createPrestashopCartProduct($content, $cart, $idProductAttribute);
        }

        if (empty($cart)) {
            StripeProcessLogger::logError('CalculateShipping - Invalid cart - content => ' . json_encode($contentAnonymized), 'calculateShipping', Context::getContext()->cart->id);
            echo json_encode(['error' => true, 'message' => 'Invalid cart, please refresh and try again']);
            exit;
        }

        StripeProcessLogger::logInfo('CalculateShipping - content => ' . json_encode($contentAnonymized), 'calculateShipping', Context::getContext()->cart->id);

        $idZone = Country::getIdZone($countryId);

        $discountDetails = $this->prestashopCartService->checkDiscountCouponForCart($cart);

        $carriers = Carrier::getCarriersForOrder($idZone, null, $cart);

        // get default carrier set in back office
        $defaultCarrierId = (int) Configuration::get('PS_CARRIER_DEFAULT');

        // ensure the default carrier is used
        $defaultCarrierExists = false;
        foreach ($carriers as $carrier) {
            if ((int) $carrier['id_carrier'] === $defaultCarrierId) {
                $defaultCarrierExists = true;
                break;
            }
        }

        if ($defaultCarrierExists) {
            $cart->id_carrier = $defaultCarrierId;
        } elseif (!empty($carriers)) {
            $cart->id_carrier = (int) $carriers[0]['id_carrier'];
        }

        $cart->update();

        if ($discountDetails['free_shipping']) {
            foreach ($carriers as &$carrier) {
                $carrier['is_free'] = true;
                $carrier['shipping_cost'] = 0;
            }
        }

        $cartAmountWithoutShipping = 0;
        $currency = Context::getContext()->currency;
        $precision = 2; // default precision
        if ($currency) {
            $precision = $currency->precision;
        }
        if ($cart) {
            $cartRows = $cart->getWsCartRows();
            if ($cartRows) {
                foreach ($cartRows as &$cartRow) {
                    $cartRow['id_address_delivery'] = 0; // set the address to 0, to force prestashop to take the country from context, and apply the taxes based on the country, not the user saved address from checkout page
                }
                $cart->setWsCartRows($cartRows); // save. This method was provied by prestashop, so did not had to make DB update query directly
                $cart = new Cart($cart->id); // refresh cart with the updated values
                $cartAmountWithoutShipping = $cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING) * pow(10, $precision); // get total with the applied taxes from the selected country
            } else {
                StripeProcessLogger::logError('CalculateShipping - Cart product unavailable', 'calculateShipping', Context::getContext()->cart->id);
                echo json_encode(['error' => true, 'message' => 'Cart product unavailable']);
                exit;
            }
        }

        echo json_encode([
            'carriers' => $carriers,
            'cartId' => $cart->id,
            'discountDetails' => $discountDetails,
            'updatedCartAmount' => Tools::ps_round($cartAmountWithoutShipping, $precision),
            'precision' => $precision,
            'defaultCarrierId' => $defaultCarrierId,
            'productAttributeId' => $idProductAttribute,
        ]);
        exit;
    }
}
