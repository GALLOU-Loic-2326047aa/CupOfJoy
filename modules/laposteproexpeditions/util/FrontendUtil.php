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
 * Contains code for frontend util class.
 */

namespace LaPoste\LaPosteProExpeditionsPrestashop\Util;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Frontend util class.
 *
 * Helper for frontend calls.
 */
class FrontendUtil
{
    /**
     * Get map url.
     *
     * @return string|null
     */
    public static function getMapUrl()
    {
        $path = ConfigurationUtil::get('LP_MAP_TOKEN_URL');
        $token = ShippingApiUtil::getMapToken($path);

        if (null !== $token) {
            return str_replace(
                '${access_token}',
                $token,
                ConfigurationUtil::get('LP_MAP_BOOTSTRAP_URL')
            );
        }

        return null;
    }

    /**
     * Get closest parcel point for a cart and shipping method.
     *
     * @param int $cartId cart id
     * @param int $id shipping method id
     *
     * @return mixed|null
     */
    public static function getCartClosestPoint($cartId, $id)
    {
        $parcelPoints = EncodeUtil::decode(CartStorageUtil::get($cartId, 'lpParcelPoints'));
        if (null === $parcelPoints || false === $parcelPoints) {
            return null;
        }
        $networks = ShippingMethodUtil::getSelectedParcelPointNetworks($id);
        if (property_exists($parcelPoints, 'nearbyParcelPoints') && is_array($parcelPoints->nearbyParcelPoints)
            && count($parcelPoints->nearbyParcelPoints) > 0) {
            foreach ($parcelPoints->nearbyParcelPoints as $parcelPoint) {
                if (property_exists($parcelPoint, 'parcelPoint')
                    && property_exists($parcelPoint->parcelPoint, 'network')
                    && in_array($parcelPoint->parcelPoint->network, $networks)) {
                    $distanceFromSearchLocation = null;
                    if (property_exists($parcelPoint, 'distanceFromSearchLocation')) {
                        $distanceFromSearchLocation = $parcelPoint->distanceFromSearchLocation;
                    }
                    $parcelPoint->parcelPoint->{'distanceFromSearchLocation'} = $distanceFromSearchLocation;

                    return ParcelPointUtil::normalizePoint((object) $parcelPoint->parcelPoint);
                }
            }
        }

        return null;
    }
}
