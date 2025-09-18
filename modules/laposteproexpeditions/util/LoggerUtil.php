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
 * Logger util class
 *
 * Helper to handle logging
 */
class LoggerUtil
{
    /**
     * Log an message.
     *
     * @param int $level log level
     * @param string $message log message
     */
    private static function log($level, $message)
    {
        $prefix = '[laposteproexpeditions] ';
        if (ConfigurationUtil::getLogging() || !AuthUtil::isPluginPaired(ShopUtil::$shopGroupId, ShopUtil::$shopId)) {
            if (class_exists('PrestaShopLogger')) {
                \PrestaShopLogger::addLog($prefix . $message, $level);
            } elseif (class_exists('Logger')) {
                \Logger::addLog($prefix . $message, $level);
            }
        }
    }

    /**
     * Log an info message.
     *
     * @param string $message log message
     */
    public static function info($message)
    {
        self::log(1, $message);
    }

    /**
     * Log a warning message.
     *
     * @param string $message log message
     */
    public static function warn($message)
    {
        self::log(2, $message);
    }
}
