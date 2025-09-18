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
 * Contains code for environment util class.
 */

namespace LaPoste\LaPosteProExpeditionsPrestashop\Util;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Environment util class.
 *
 * Helper to check environment.
 */
class EnvironmentUtil
{
    /**
     * Get warning about PHP version, WC version.
     *
     * @param \LaPosteProExpeditions $plugin plugin object
     *
     * @return string|bool $message or false
     */
    public static function checkErrors($plugin)
    {
        if (version_compare(PHP_VERSION, $plugin->minPhpVersion, '<')) {
            /* translators: 1) Company name 2) int version 3) int version */
            $message = $plugin->l('%1$s - The minimum PHP version required for this plugin is %2$s. You ' .
                'are running %3$s.');

            return sprintf($message, \LaPosteProExpeditions::getInstance()->displayName, $plugin->minPhpVersion, PHP_VERSION);
        }

        return false;
    }
}
