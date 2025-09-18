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
 * Contains code for configuration util class.
 */

namespace LaPoste\LaPosteProExpeditionsPrestashop\Util;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Configuration report util class.
 *
 * Helper to generate a full configuration report.
 */
class ConfigurationReportUtil
{
    public static function getConfigurationReport()
    {
        $report = [];

        $report['config'] = self::getAllShopConfiguration();
        $report['carriers'] = self::getCarriers();
        $report['shipment_states'] = self::getAllShipmentStatuses();
        $report['modules'] = self::getActiveModules();
        $report['zones'] = self::getZones();
        $report['versions'] = self::getVersions();
        $report['php_extensions'] = self::getPhpExtensions();
        $report['registered_hooks'] = self::getRegisteredHooks();
        $report['groups'] = self::getGroups();
        $report['parcelpoint'] = self::getParcelPoints();

        return $report;
    }

    private static function getAllShopConfiguration()
    {
        $shops = \Shop::getShopsCollection();
        $configs = [];

        foreach ($shops as $shop) {
            $configs[] = [
                'shop' => property_exists($shop, 'name') ? $shop->name : '',
                'configs' => ConfigurationUtil::getAll(null, $shop->id),
            ];
        }

        return $configs;
    }

    private static function getAllShipmentStatuses()
    {
        $statuses = OrderUtil::getOrderStatuses(\LaPosteProExpeditions::getInstance()->getContext()->language->id);
        $statuses_by_id = [];

        foreach ($statuses as $status) {
            $statuses_by_id[$status['id_order_state']] = $status['name'];
        }

        return $statuses_by_id;
    }

    private static function getVersions()
    {
        $versions = [];

        $versions['php'] = phpversion();
        $versions['prestashop'] = defined('_PS_VERSION_') ? _PS_VERSION_ : null;
        $versions['laposteproexpeditions'] = \LaPosteProExpeditions::getInstance()->version;

        return $versions;
    }

    private static function getPhpExtensions()
    {
        $extensions = get_loaded_extensions();
        sort($extensions);

        return $extensions;
    }

    private static function getActiveModules()
    {
        $modules = \Module::getModulesInstalled();
        $modules_by_name = [];

        foreach ($modules as $module) {
            $modules_by_name[$module['name']] = $module['version'];
        }
        ksort($modules_by_name);

        return $modules_by_name;
    }

    private static function getZones()
    {
        $zones = \Zone::getZones();
        $active_countries = \Country::getCountries(\LaPosteProExpeditions::getInstance()->getContext()->language->id, true);
        $result = [];

        foreach ($zones as $zone) {
            $zone_countries = [];

            foreach ($active_countries as $country) {
                if ($country['id_zone'] === $zone['id_zone']) {
                    $zone_countries[$country['iso_code']] = $country['country'];
                }
            }
            ksort($zone_countries);

            $result[] = [
                'name' => $zone['name'],
                'active' => (bool) $zone['active'],
                'countries' => $zone_countries,
            ];
        }

        return $result;
    }

    private static function getCarrierRange($carrier)
    {
        $zones = \Zone::getZones();
        $range_table = $carrier->getRangeTable();
        $ranges = null;
        $result = null;

        if ('range_weight' === $range_table) {
            $ranges = \RangeWeight::getRanges($carrier->id);
        } elseif ('range_price' === $range_table) {
            $ranges = \RangePrice::getRanges($carrier->id);
        }

        if ($ranges) {
            $result = [];
            foreach ($ranges as $key => $range) {
                if ('range_weight' === $range_table) {
                    $range = [
                        'min_weight' => $range['delimiter1'],
                        'max_weight' => $range['delimiter2'],
                        'prices' => [],
                    ];

                    foreach ($zones as $zone) {
                        $price = $carrier->getDeliveryPriceByWeight($range['min_weight'], $zone['id_zone']);
                        if (false !== $price) {
                            $range['prices'][] = [
                                'zone' => $zone['name'],
                                'price' => $price,
                            ];
                        }
                    }

                    $result[$key] = $range;
                } else {
                    $range = [
                        'min_price' => $range['delimiter1'],
                        'max_price' => $range['delimiter2'],
                        'prices' => [],
                    ];

                    foreach ($zones as $zone) {
                        $price = $carrier->getDeliveryPriceByPrice($range['min_price'], $zone['id_zone']);
                        if (false !== $price) {
                            $range['prices'][] = [
                                'zone' => $zone['name'],
                                'price' => $price,
                            ];
                        }
                    }

                    $result[$key] = $range;
                }
            }
        }

        return $result;
    }

    private static function getShippingPreferences()
    {
        $shops = \Shop::getShopsCollection();
        $all_configs = [];

        foreach ($shops as $shop) {
            $shop_group = property_exists($shop, 'id_shop_group') ? $shop->id_shop_group : null;
            $shop_id = $shop->id;
            $all_configs[] = [
                'shop' => property_exists($shop, 'name') ? $shop->name : '',
                'configs' => [
                    'PS_SHIPPING_HANDLING' => \Configuration::get(
                        'PS_SHIPPING_HANDLING',
                        null,
                        $shop_group,
                        $shop_id
                    ),
                    'PS_SHIPPING_FREE_WEIGHT' => \Configuration::get(
                        'PS_SHIPPING_FREE_WEIGHT',
                        null,
                        $shop_group,
                        $shop_id
                    ),
                    'PS_SHIPPING_FREE_PRICE' => \Configuration::get(
                        'PS_SHIPPING_FREE_PRICE',
                        null,
                        $shop_group,
                        $shop_id
                    ),
                ],
            ];
        }

        return $all_configs;
    }

    private static function getCarrierGroups($carrier)
    {
        $groups = $carrier->getGroups();
        $result = [];

        foreach ($groups as $group) {
            $result[] = $group['id_group'];
        }

        return $result;
    }

    private static function getCarriers()
    {
        $result = [
            'preferences' => self::getShippingPreferences(),
            'list' => [],
        ];
        $carriers = \Carrier::getCarriers(\LaPosteProExpeditions::getInstance()->getContext()->language->id);

        foreach ($carriers as $c) {
            $carrier = new \Carrier($c['id_carrier']);
            $tax = new \Tax($carrier->getIdTaxRulesGroup());

            $result['list'][] = [
                'id' => $carrier->id,
                'name' => $carrier->name,
                'delay' => $carrier->delay,
                'active' => $carrier->active,
                'tracking_url' => $carrier->url,
                'free_shipping' => (bool) $carrier->is_free,
                'handling_cost' => (bool) $carrier->shipping_handling,
                'max_width' => $carrier->max_width,
                'max_height' => $carrier->max_height,
                'max_depth' => $carrier->max_depth,
                'max_weight' => $carrier->max_weight,
                'tax_rate' => $tax->rate,
                'out_of_range_behavior' => $carrier->range_behavior ? 'disable' : 'highest_range',
                'ranges' => self::getCarrierRange($carrier),
                'groups' => self::getCarrierGroups($carrier),
                'networks' => ShippingMethodUtil::getSelectedParcelPointNetworks($carrier->id),
            ];
        }

        return $result;
    }

    private static function getRegisteredHooks()
    {
        $module = \LaPosteProExpeditions::getInstance();
        $hooks = \Hook::getHooks();
        $result = [];

        foreach ($hooks as $hook) {
            $name = $hook['name'];

            if ($module->isRegisteredInHook($name)) {
                $result[] = $name;
            }
        }

        return $result;
    }

    private static function getGroups()
    {
        $shops = \Shop::getShopsCollection();
        $result = [];

        foreach ($shops as $shop) {
            $groups = \Group::getGroups(\LaPosteProExpeditions::getInstance()->getContext()->language->id, $shop->id);

            $result[] = [
                'shop' => property_exists($shop, 'name') ? $shop->name : '',
                'groups' => $groups,
            ];
        }

        return $result;
    }

    private static function getParcelPoints()
    {
        $networks = ConfigurationUtil::getParcelPointNetworks();
        $networks_keys = $networks === null ? [] : array_keys($networks);
        $address = [
            'street' => '15 rue marsolier',
            'city' => 'PARIS',
            'zipCode' => '75002',
            'country' => 'fr',
        ];

        $response = ShippingApiUtil::getParcelPoints($address, $networks_keys);

        return [
            'networks' => $networks_keys,
            'address' => $address,
            'response' => $response,
        ];
    }
}
