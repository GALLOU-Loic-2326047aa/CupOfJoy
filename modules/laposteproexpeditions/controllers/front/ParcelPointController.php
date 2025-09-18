<?php
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    La Poste
 * @copyright 2007-2025 PrestaShop SA / 2024-2025 La Poste
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
/**
 * Contains code for the parcel point controller class.
 */

namespace LaPoste\LaPosteProExpeditionsPrestashop\Controllers\Front;

if (!defined('_PS_VERSION_')) {
    exit;
}

use LaPoste\LaPosteProExpeditionsPrestashop\Util\AddressUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\CartStorageUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ConfigurationUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\EncodeUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\FrontendUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ParcelPointUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ShippingApiUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ShippingMethodUtil;

/**
 * Parcel point controller class.
 *
 * @class       ParcelPointController
 */
class ParcelPointController
{
    /**
     * Add scripts.
     *
     * @return string html
     */
    public static function addScripts()
    {
        $instance = \LaPosteProExpeditions::getInstance();
        $translation = [
            'error' => [
                'carrierNotFound' => $instance->l('Unable to find a carrier'),
                'couldNotSelectPoint' => $instance->l('An error occurred during parcel point selection'),
            ],
            'text' => [
                'chooseParcelPoint' => $instance->l('Choose this parcel point'),
                'closeMap' => $instance->l('Close map'),
                'closedLabel' => $instance->l('Closed     '),
            ],
            'distance' => $instance->l('%s km away'),
        ];

        $smarty = $instance->getSmarty();
        $smarty->assign('translation', $translation);
        $smarty->assign('token', \Tools::getToken(false));
        $smarty->assign('mapLogoImageUrl', ConfigurationUtil::getMapLogoImageUrl());
        $smarty->assign('mapLogoHrefUrl', ConfigurationUtil::getMapLogoHrefUrl());
        $smarty->assign('module', $instance->name);

        $instance->addJs('lp-maplibre-gl', 'views/js/maplibre-gl.min.js', 99);
        $instance->addJs('lp-parcel-point', 'views/js/parcel-point.min.js', 100);

        $instance->addCss('lp-maplibre-gl', 'views/css/maplibre-gl.css', 100);
        $instance->addCss('lp-parcel-point', 'views/css/parcel-point.css', 100);

        return $instance->displayTemplate('front/shipping-method/header.tpl');
    }

    /**
     * Add point info.
     *
     * @param array $params cart info
     *
     * @return string|null html
     */
    public static function initPoints($params)
    {
        if (!isset($params['cart'])) {
            return null;
        }
        $cart = $params['cart'];

        $address = new \Address((int) $cart->id_address_delivery);
        $parcelPointNetworks = ShippingMethodUtil::getAllSelectedParcelPointNetworks();
        if (!empty($parcelPointNetworks)) {
            $response = ShippingApiUtil::getParcelPoints(AddressUtil::convert($address), $parcelPointNetworks);
            if ($response !== null && property_exists($response, 'nearbyParcelPoints')
                && is_array($response->nearbyParcelPoints)
                && count($response->nearbyParcelPoints) > 0) {
                CartStorageUtil::set(
                    (int) $cart->id,
                    'lpParcelPoints',
                    EncodeUtil::encode($response)
                );
                $instance = \LaPosteProExpeditions::getInstance();
                $smarty = $instance->getSmarty();
                $smarty->assign('cartId', (int) $cart->id);
                $smarty->assign('token', \Tools::getToken(false));

                return $instance->displayTemplate('front/shipping-method/parcelPoint.tpl');
            }
        }
        CartStorageUtil::set($cart->id, 'lpParcelPoints', null);

        return null;
    }

    /**
     * Order creation.
     *
     * @param array $params list of order params
     *
     * @void
     */
    public static function orderCreated($params)
    {
        if (!isset($params['cart'], $params['order'])) {
            return;
        }

        $cart = $params['cart'];
        $order = $params['order'];
        $carrierId = $cart->id_carrier;

        $orderPoint = null;
        $chosenPoint = ParcelPointUtil::getChosenPoint($cart->id, $carrierId);
        $closestPoint = FrontendUtil::getCartClosestPoint($cart->id, $carrierId);

        if (null !== $chosenPoint) {
            $orderPoint = $chosenPoint;
        } elseif (null !== $closestPoint) {
            $orderPoint = $closestPoint;
        }

        CartStorageUtil::delete($cart->id);
        ParcelPointUtil::setOrderParcelPoint($order->id, $orderPoint);
    }
}
