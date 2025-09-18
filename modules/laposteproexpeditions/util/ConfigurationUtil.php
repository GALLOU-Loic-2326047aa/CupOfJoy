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

use LaPoste\LaPosteProExpeditionsPrestashop\Controllers\Misc\NoticeController;

/**
 * Configuration util class.
 *
 * Helper to manage configuration.
 */
class ConfigurationUtil
{
    private static $ALL_CONFIGS = [
        'ACCESS_KEY',
        'SECRET_KEY',
        'MAP_BOOTSTRAP_URL',
        'MAP_TOKEN_URL',
        'MAP_LOGO_IMAGE_URL',
        'MAP_LOGO_HREF_URL',
        'PP_NETWORKS',
        'PAIRING_UPDATE',
        'ORDER_PREPARED',
        'ORDER_SHIPPED',
        'ORDER_DELIVERED',
        'TRACKING_URL_PATTERN',
        'HELP_CENTER_URL',
        'SHIPPING_RULES_URL',
        'TUTO_URL',
        'LOGGING',
        'CONFIGURATION_URL',
    ];

    /**
     * Get option.
     *
     * @param string $name option name
     * @param int|null $shopGroupId shop group id
     * @param int|null $shopId shop id
     * @param mixed $default option default value
     *
     * @return string|array|null option value
     */
    public static function get($name, $shopGroupId = null, $shopId = null, $default = null)
    {
        if (null === $shopGroupId) {
            $shopGroupId = ShopUtil::$shopGroupId;
        }

        if (null === $shopId) {
            $shopId = ShopUtil::$shopId;
        }

        $value = \Configuration::get($name, null, $shopGroupId, $shopId, $default);

        return null !== $value && false !== $value && '' !== $value ? $value : null;
    }

    /**
     * Get option as an integer.
     *
     * @param string $name option name
     * @param int $shopGroupId shop group id
     * @param int $shopId shop id
     * @param mixed $default option default value
     *
     * @return int|null option value as an integer or null
     */
    public static function getAsInt($name, $shopGroupId = null, $shopId = null, $default = null)
    {
        $value = self::get($name, $shopGroupId, $shopId, $default);

        return null === $value ? $value : (int) $value;
    }

    /**
     * Get options for a shop.
     *
     * @param int $shopGroupId shop group id
     * @param int $shopId shop id
     *
     * @return array options values
     */
    public static function getAll($shopGroupId = null, $shopId = null)
    {
        $all_configs = [];
        $prefix = 'LP_';
        foreach (self::$ALL_CONFIGS as $config) {
            $all_configs[$config] = self::get($prefix . $config, $shopGroupId, $shopId, null);
        }

        return $all_configs;
    }

    /**
     * Set option.
     *
     * @param string $name option name
     * @param bool|int|string|null $value option value
     *
     * @void
     */
    public static function set($name, $value)
    {
        \Configuration::updateValue($name, $value, false, ShopUtil::$shopGroupId, ShopUtil::$shopId);
    }

    /**
     * Delete option. Do NOT delete value in configuration cache.
     *
     * @param string $name option name
     * @param int|null $shopGroupId shop group id
     * @param int|null $shopId shop id
     *
     * @void
     */
    public static function delete($name, $shopGroupId, $shopId)
    {
        if (false === ShopUtil::$multistore) {
            self::deleteAllShops($name);

            return;
        }

        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'configuration` WHERE name="' . $name . '" ';

        if (null === $shopId) {
            $sql .= 'AND id_shop IS NULL ';
        } else {
            $sql .= 'AND id_shop=' . $shopId . ' ';
        }

        if (null === $shopGroupId) {
            $sql .= 'AND id_shop_group IS NULL ';
        } else {
            $sql .= 'AND id_shop_group=' . $shopGroupId . ' ';
        }

        \Db::getInstance()->execute($sql);
    }

    /**
     * Delete option for all shops. Deletes value in cache as well.
     *
     * @param string $name option name
     *
     * @void
     */
    public static function deleteAllShops($name)
    {
        \Configuration::deleteByName($name);
    }

    /**
     * Parse configuration.
     *
     * @param object $body body
     *
     * @return bool
     */
    public static function parseConfiguration($body)
    {
        return self::parseParcelPointNetworks($body)
            && self::parseMapConfiguration($body)
            && self::parseTrackingConfiguration($body)
            && self::parseLinksConfiguration($body);
    }

    /**
     * Has configuration.
     *
     * @param int $shopGroupId shop group id
     * @param int $shopId shop id
     *
     * @return bool
     */
    public static function hasConfiguration($shopGroupId, $shopId)
    {
        return null !== self::get('LP_MAP_BOOTSTRAP_URL', $shopGroupId, $shopId)
            && null !== self::get('LP_MAP_TOKEN_URL', $shopGroupId, $shopId)
            && null !== self::get('LP_MAP_LOGO_IMAGE_URL', $shopGroupId, $shopId)
            && null !== self::get('LP_MAP_LOGO_HREF_URL', $shopGroupId, $shopId)
            && null !== self::get('LP_PP_NETWORKS', $shopGroupId, $shopId)
            && null !== self::get('LP_TRACKING_URL_PATTERN', $shopGroupId, $shopId);
    }

    /**
     * Build onboarding link.
     *
     * @param int $shopGroupId shop group id
     * @param int $shopId shop id
     *
     * @return string onboarding link
     */
    public static function getOnboardingLink($shopGroupId, $shopId)
    {
        $instance = \LaPosteProExpeditions::getInstance();
        $url = $instance->onboardingUrl;
        $email = MiscUtil::getFirstAdminUserEmail();
        $locale = \Language::getIsoById((int) $instance->getContext()->cookie->id_lang);
        $shopUrl = ShopUtil::getShopUrl($shopGroupId, $shopId);

        $params = [
            'acceptLanguage' => $locale,
            'email' => $email,
            'shopUrl' => $shopUrl,
            'shopType' => 'prestashop',
        ];

        $query = parse_url($url, PHP_URL_QUERY);

        return $url . ($query ? '&' : '?') . http_build_query($params);
    }

    /**
     * Get map logo href url.
     *
     * @return string|null map logo href url
     */
    public static function getMapLogoHrefUrl()
    {
        $url = self::get('LP_MAP_LOGO_HREF_URL');

        return $url;
    }

    /**
     * Get map logo image url.
     *
     * @return string|null map logo image url
     */
    public static function getMapLogoImageUrl()
    {
        $url = self::get('LP_MAP_LOGO_IMAGE_URL');

        return $url;
    }

    /**
     * Get tracking url pattern.
     *
     * @return string|null tracking url pattern
     */
    public static function getTrackingUrlPattern()
    {
        $url = self::get('LP_TRACKING_URL_PATTERN');

        return $url;
    }

    /**
     * Get parcel point networks.
     *
     * @return array|null of network => shipping method names
     */
    public static function getParcelPointNetworks()
    {
        $networks = self::get('LP_PP_NETWORKS');

        return EncodeUtil::decode($networks);
    }

    /**
     * Get help center url.
     *
     * @return string|null help center url
     */
    public static function getHelpCenterUrl()
    {
        $url = self::get('LP_HELP_CENTER_URL');

        return $url;
    }

    /**
     * Get shipping rules url.
     *
     * @return string|null shipping rules url
     */
    public static function getShippingRulesUrl()
    {
        $url = self::get('LP_SHIPPING_RULES_URL');

        return $url;
    }

    /**
     * Get configuration url.
     *
     * @return string|null help center url
     */
    public static function getConfigurationUrl()
    {
        $url = self::get('LP_CONFIGURATION_URL');

        return $url;
    }

    /**
     * Set configuration url.
     *
     * @param string|null $url help center url
     */
    public static function setConfigurationUrl($url)
    {
        self::set('LP_CONFIGURATION_URL', $url);
    }

    /**
     * Get logging state (enabled or not).
     *
     * @return bool logging
     */
    public static function getLogging()
    {
        return self::get('LP_LOGGING') == true;
    }

    /**
     * Set logging state (enabled or not).
     *
     * @param bool $logging
     */
    public static function setLogging($logging)
    {
        self::set('LP_LOGGING', $logging);
    }

    /**
     * Delete configuration.
     *
     * @void
     */
    public static function deleteConfiguration()
    {
        self::deleteAllShops('LP_ACCESS_KEY');
        self::deleteAllShops('LP_SECRET_KEY');
        self::deleteAllShops('LP_MAP_BOOTSTRAP_URL');
        self::deleteAllShops('LP_MAP_TOKEN_URL');
        self::deleteAllShops('LP_MAP_LOGO_IMAGE_URL');
        self::deleteAllShops('LP_MAP_LOGO_HREF_URL');
        self::deleteAllShops('LP_PP_NETWORKS');
        self::deleteAllShops('LP_PAIRING_UPDATE');
        self::deleteAllShops('LP_ORDER_PREPARED');
        self::deleteAllShops('LP_ORDER_SHIPPED');
        self::deleteAllShops('LP_ORDER_DELIVERED');
        self::deleteAllShops('LP_TRACKING_URL_PATTERN');
        self::deleteAllShops('LP_HELP_CENTER_URL');
        self::deleteAllShops('LP_CONFIGURATION_URL');
        self::deleteAllShops('LP_SHIPPING_RULES_URL');
        NoticeController::removeAllNoticesForShop();
    }

    /**
     * Parse parcel point operators response.
     *
     * @param object $body body
     *
     * @return bool
     */
    private static function parseParcelPointNetworks($body)
    {
        $instance = \LaPosteProExpeditions::getInstance();
        if (is_object($body) && property_exists($body, 'parcelPointNetworks')) {
            $storedNetworks = self::get('LP_PP_NETWORKS');
            if (is_array($storedNetworks)) {
                $removedNetworks = $storedNetworks;
                foreach ($body->parcelPointNetworks as $newNetwork => $newNetworkCarriers) {
                    foreach ($storedNetworks as $oldNetwork => $oldNetworkCarriers) {
                        if ($newNetwork === $oldNetwork) {
                            unset($removedNetworks[$oldNetwork]);
                        }
                    }
                }

                if (count($removedNetworks) > 0) {
                    NoticeController::addNotice(
                        NoticeController::$custom,
                        ShopUtil::$shopGroupId,
                        ShopUtil::$shopId,
                        [
                            'status' => 'warning',
                            // phpcs:ignore Generic.Files.LineLength
                            'message' => $instance->l('There\'s been a change in the parcel point network list, we\'ve adapted your shipping method configuration. Please check that everything is in order.'),
                        ]
                    );
                }

                $addedNetworks = $body->parcelPointNetworks;
                foreach ($body->parcelPointNetworks as $newNetwork => $newNetworkCarriers) {
                    foreach ($storedNetworks as $oldNetwork => $oldNetworkCarriers) {
                        if ($newNetwork === $oldNetwork) {
                            unset($addedNetworks[$oldNetwork]);
                        }
                    }
                }
                if (count($addedNetworks) > 0) {
                    NoticeController::addNotice(
                        NoticeController::$custom,
                        ShopUtil::$shopGroupId,
                        ShopUtil::$shopId,
                        [
                            'status' => 'info',
                            // phpcs:ignore Generic.Files.LineLength
                            'message' => $instance->l('There\'s been a change in the parcel point network list, you can add the extra parcel point network(s) to your shipping method configuration.'),
                        ]
                    );
                }
            }
            self::set(
                'LP_PP_NETWORKS',
                EncodeUtil::encode(MiscUtil::convertStdClassToArray($body->parcelPointNetworks))
            );

            return true;
        }

        return false;
    }

    /**
     * Parse map configuration.
     *
     * @param object $body body
     *
     * @return bool
     */
    private static function parseMapConfiguration($body)
    {
        if (is_object($body) && property_exists($body, 'mapsBootstrapUrl')
            && property_exists($body, 'mapsTokenUrl')
            && property_exists($body, 'mapsLogoImageUrl')
            && property_exists($body, 'mapsLogoHrefUrl')) {
            self::set('LP_MAP_BOOTSTRAP_URL', $body->mapsBootstrapUrl);
            self::set('LP_MAP_TOKEN_URL', $body->mapsTokenUrl);
            self::set('LP_MAP_LOGO_IMAGE_URL', $body->mapsLogoImageUrl);
            self::set('LP_MAP_LOGO_HREF_URL', $body->mapsLogoHrefUrl);

            return true;
        }

        return false;
    }

    /**
     * Parse tracking configuration.
     *
     * @param object $body body
     *
     * @return bool
     */
    private static function parseTrackingConfiguration($body)
    {
        if (is_object($body) && property_exists($body, 'trackingUrlPattern')) {
            $storedTrackingUrlPattern = self::getTrackingUrlPattern();
            if (null !== $storedTrackingUrlPattern && $storedTrackingUrlPattern !== $body->trackingUrlPattern) {
                $instance = \LaPosteProExpeditions::getInstance();
                NoticeController::addNotice(
                    NoticeController::$custom,
                    ShopUtil::$shopGroupId,
                    ShopUtil::$shopId,
                    [
                        'status' => 'warning',
                        'message' => sprintf(
                            // phpcs:ignore Generic.Files.LineLength
                            $instance->l('The %s tracking url has changed, you should change it in your shipping methods as well. The new link is displayed on the %s settings page.'),
                            'La Poste',
                            'La Poste'
                        ),
                    ]
                );
            }

            self::set('LP_TRACKING_URL_PATTERN', $body->trackingUrlPattern);

            return true;
        }

        return false;
    }

    /**
     * Parse help center configuration.
     *
     * @param object $body body
     *
     * @return bool
     */
    private static function parseLinksConfiguration($body)
    {
        if (is_object($body) && property_exists($body, 'helpCenterUrl')) {
            self::set('LP_HELP_CENTER_URL', $body->helpCenterUrl);
        }
        if (is_object($body) && property_exists($body, 'shippingPreferencesUrl')) {
            self::set('LP_SHIPPING_RULES_URL', $body->shippingPreferencesUrl);
        }

        return true;
    }
}
