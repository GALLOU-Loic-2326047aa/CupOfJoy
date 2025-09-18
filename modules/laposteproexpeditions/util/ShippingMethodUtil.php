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
 * Contains code for shipping method util class.
 */

namespace LaPoste\LaPosteProExpeditionsPrestashop\Util;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Shipping method util class.
 *
 * Helper to manage shipping methods.
 */
class ShippingMethodUtil
{
    /**
     * Get all parcel point networks selected in at least one shipping method.
     *
     * @return array $selectedParcelPointNetworks
     */
    public static function getAllSelectedParcelPointNetworks()
    {
        $selectedParcelPointNetworks = [];
        $shippingMethods = self::getShippingMethods();
        $parcelPointNetworks = EncodeUtil::decode(
            ConfigurationUtil::get('LP_PP_NETWORKS')
        );
        if (!is_object($parcelPointNetworks)) {
            return [];
        }

        foreach ((array) $shippingMethods as $shippingMethod) {
            if (isset($shippingMethod['parcel_point_networks'])) {
                $shippingMethodNetworks = EncodeUtil::decode($shippingMethod['parcel_point_networks']);
                foreach ((array) $shippingMethodNetworks as $shippingMethodNetwork) {
                    if (!in_array($shippingMethodNetwork, $selectedParcelPointNetworks, true)) {
                        foreach ($parcelPointNetworks as $parcelPointNetwork => $carrier) {
                            if ($shippingMethodNetwork === $parcelPointNetwork) {
                                $selectedParcelPointNetworks[] = $shippingMethodNetwork;
                            }
                        }
                    }
                }
            }
        }

        return $selectedParcelPointNetworks;
    }

    /**
     * Get parcel point networks associated with shipping methods.
     *
     * @param int $carrierId carrier id
     * @param array $parcelPointNetworks array of parcel point network codes
     */
    public static function setSelectedParcelPointNetworks($carrierId, $parcelPointNetworks)
    {
        $data = [
            'id_carrier' => (int) $carrierId,
            'id_shop_group' => ShopUtil::$shopGroupId,
            'id_shop' => ShopUtil::$shopId,
            'parcel_point_networks' => pSQL(EncodeUtil::encode($parcelPointNetworks)),
        ];

        \Db::getInstance()->insert(
            'lp_carrier',
            $data,
            true,
            true,
            \Db::REPLACE
        );
    }

    /**
     * Get all parcel point networks selected for a shipping method.
     *
     * @param int $id shipping method id
     *
     * @return array $selectedParcelPointNetworks
     */
    public static function getSelectedParcelPointNetworks($id)
    {
        $selectedParcelPointNetworks = [];
        $shippingMethods = self::getShippingMethods();
        $parcelPointNetworks = EncodeUtil::decode(
            ConfigurationUtil::get('LP_PP_NETWORKS')
        );
        if (!is_object($parcelPointNetworks)) {
            return [];
        }

        foreach ((array) $shippingMethods as $shippingMethod) {
            if (isset($shippingMethod['parcel_point_networks']) && (int) $shippingMethod['id_carrier'] === (int) $id) {
                $shippingMethodNetworks = EncodeUtil::decode($shippingMethod['parcel_point_networks']);
                foreach ((array) $shippingMethodNetworks as $shippingMethodNetwork) {
                    if (!in_array($shippingMethodNetwork, $selectedParcelPointNetworks, true)) {
                        foreach ($parcelPointNetworks as $parcelPointNetwork => $carrier) {
                            if ($shippingMethodNetwork === $parcelPointNetwork) {
                                $selectedParcelPointNetworks[] = $shippingMethodNetwork;
                            }
                        }
                    }
                }
            }
        }

        return $selectedParcelPointNetworks;
    }

    /**
     * Whether a shipping method has at least one parcel point network selected.
     *
     * @param int|null $id shipping method id
     *
     * @return bool
     */
    public static function hasSelectedParcelPointNetworks($id)
    {
        return null !== $id && count(self::getSelectedParcelPointNetworks($id)) > 0;
    }

    /**
     * Get parcel point networks associated with shipping methods.
     *
     * @return object shipping methods
     */
    public static function getShippingMethods()
    {
        $instance = \LaPosteProExpeditions::getInstance();
        $sql = new \DbQuery();
        $sql->select('c.id_carrier, c.name, bc.parcel_point_networks');
        $sql->from('carrier', 'c');
        $sql->innerJoin(
            'carrier_lang',
            'cl',
            'c.id_carrier = cl.id_carrier AND cl.id_lang = ' . (int) $instance->getContext()->language->id
        );

        $carrierJoin = 'c.id_carrier = bc.id_carrier';

        if (null === ShopUtil::$shopGroupId) {
            $carrierJoin .= ' AND bc.id_shop_group IS NULL';
        } else {
            $carrierJoin .= ' AND bc.id_shop_group =' . (int) ShopUtil::$shopGroupId;
        }

        if (null === ShopUtil::$shopId) {
            $carrierJoin .= ' AND bc.id_shop IS NULL';
            $sql->where('cl.id_shop IS NULL');
        } else {
            $carrierJoin .= ' AND bc.id_shop =' . (int) ShopUtil::$shopId;
            $sql->where('cl.id_shop =' . (int) ShopUtil::$shopId);
        }

        $sql->leftJoin('lp_carrier', 'bc', $carrierJoin);
        $sql->where('c.deleted = 0');

        return \Db::getInstance()->executeS($sql);
    }

    /**
     * Get carrier tracking url.
     *
     * @param int $carrierId carrier id
     *
     * @return string
     */
    public static function getCarrierTrackingUrl($carrierId)
    {
        $sql = new \DbQuery();
        $sql->select('c.url');
        $sql->from('carrier', 'c');
        $sql->where('c.id_carrier = ' . (int) $carrierId);
        $result = \Db::getInstance()->executeS($sql);

        return isset($result[0]['url']) && !empty($result[0]['url']) ? $result[0]['url'] : null;
    }
}
