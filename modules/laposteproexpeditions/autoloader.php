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
if (!defined('_PS_VERSION_')) {
    exit;
}

/*
 * Dynamically loads the class attempting to be instantiated elsewhere in the
 * plugin.
 */
spl_autoload_register(function ($className) {
    // If the specified $className does not include our namespace, duck out.
    if (false === strpos($className, 'LaPoste\LaPosteProExpeditionsPrestashop')) {
        return;
    }

    // Split the class name into an array to read the namespace and class.
    $fileParts = explode('\\', $className);

    if (count($fileParts) < 3) {
        return;
    }

    $path = '';
    for ($i = count($fileParts) - 1; $i > 1; --$i) {
        if (count($fileParts) - 1 === $i) {
            $path .= $fileParts[$i] . '.php';
        } else {
            $path = Tools::strtolower($fileParts[$i]) . '/' . $path;
        }
    }

    $filePath = null;
    if ('LaPosteProExpeditionsPrestashop' === $fileParts[1]) {
        $filePath = dirname(__FILE__) . '/' . $path;
    }

    // If the file exists in the specified path, then include it.
    if (isset($filePath) && $filePath !== null) {
        if (file_exists($filePath)) {
            include_once $filePath;
        } else {
            var_dump("The file attempting to be loaded at $filePath does not exist.");
        }
    }
});
