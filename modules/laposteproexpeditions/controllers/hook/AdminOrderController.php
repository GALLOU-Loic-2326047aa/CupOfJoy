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
 * Contains code for the admin order controller class.
 */

namespace LaPoste\LaPosteProExpeditionsPrestashop\Controllers\Hook;

if (!defined('_PS_VERSION_')) {
    exit;
}

use LaPoste\LaPosteProExpeditionsPrestashop\Util\AuthUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ConfigurationUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\OrderUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ParcelPointUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ShippingApiUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ShippingMethodUtil;

/**
 * Admin order controller class.
 *
 * Generate the content to display on AdminOrder hook
 *
 * @class AdminOrderController
 */
class AdminOrderController
{
    private static $templateFile = 'hook/hookAdminOrder.tpl';

    private static function setBackofficeOrderParcelPointData($smarty, $orderId)
    {
        $parcelpoint = ParcelPointUtil::getOrderParcelPoint($orderId);
        $network = ConfigurationUtil::getParcelPointNetworks();
        $carrierId = OrderUtil::getCarrierId($orderId);
        $carrierNetworks = ShippingMethodUtil::getSelectedParcelPointNetworks($carrierId);

        $showParcelPoint = null !== $parcelpoint && null !== $network;
        $parcelpointShippingMethods = [];
        if ($showParcelPoint && $network->{$parcelpoint->network}) {
            $parcelpointShippingMethods = $network->{$parcelpoint->network};
        }
        $carrierShippingMethods = [];
        foreach ($carrierNetworks as $carrierNetwork) {
            $carrierShippingMethods = array_merge($carrierShippingMethods, $network->{$carrierNetwork});
        }

        $smarty->assign('showParcelPoint', $showParcelPoint);
        $smarty->assign('parcelpointBadge', $showParcelPoint ? 1 : 0);

        if ($showParcelPoint) {
            $showParcelPointAddress = !empty($parcelpoint->name)
                && !empty($parcelpoint->address)
                && !empty($parcelpoint->city)
                && !empty($parcelpoint->zipcode)
                && !empty($parcelpoint->country);

            $smarty->assign('parcelpoint', $parcelpoint);
            $smarty->assign('hasOpeningHours', count($parcelpoint->openingHours) > 0);
            $smarty->assign('openingHours', ParcelPointUtil::formatParcelPointOpeningHours($parcelpoint));
            $smarty->assign('showParcelPointAddress', $showParcelPointAddress);
            $smarty->assign('parcelpointValidForCarrier', in_array($parcelpoint->network, $carrierNetworks));
            $smarty->assign('parcelpointShippingMethods', implode(', ', $parcelpointShippingMethods));
            $smarty->assign('carrierHasNetworks', count($carrierNetworks) > 0);
            $smarty->assign('carrierShippingMethods', implode(', ', $carrierShippingMethods));
        }
    }

    private static function setBackofficeOrderTrackingData($smarty, $orderId)
    {
        $tracking = ShippingApiUtil::getOrder($orderId);

        $showTracking = null !== $tracking
            && property_exists($tracking, 'shipmentsTracking')
            && !empty($tracking->shipmentsTracking);

        $smarty->assign('showTracking', $showTracking);
        $smarty->assign('trackingBadge', $showTracking ? count($tracking->shipmentsTracking) : 0);

        if ($showTracking) {
            $smarty->assign('tracking', $tracking);
            $smarty->assign('dateFormat', \LaPosteProExpeditions::getInstance()->l('Y-m-d H:i:s'));
        }
    }

    /**
     * Generate the content to display on AdminOrder hook
     *
     * @param mixed $params
     *
     * @return string|null extra content to display
     */
    public static function trigger($params)
    {
        if (!AuthUtil::canUsePlugin()) {
            return null;
        }

        $smarty = \LaPosteProExpeditions::getInstance()->getSmarty();
        $orderId = (int) $params['id_order'];
        self::setBackofficeOrderParcelPointData($smarty, $orderId);
        self::setBackofficeOrderTrackingData($smarty, $orderId);

        return \LaPosteProExpeditions::getInstance()->displayTemplate(self::$templateFile);
    }
}
