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
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_4_1($module)
{
    $shopGroupId = Stripe_official::getShopGroupIdContext();
    $shopId = Stripe_official::getShopIdContext();

    $savePaymentMethod = Configuration::get(Stripe_official::ENABLE_SAVE_PAYMENT_METHOD);
    if (!$savePaymentMethod) {
        Configuration::updateValue(Stripe_official::ENABLE_SAVE_PAYMENT_METHOD, 0, false, $shopGroupId, $shopId);
    }

    /*
     * Clear both Smarty and Symfony cache.
     */
    Tools::clearAllCache();

    /*
     * Clean cache for upgrade to prevent issue during module upgrade
     */
    $module->cleanModuleCache();

    return true;
}
