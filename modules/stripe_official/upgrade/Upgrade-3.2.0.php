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
require_once dirname(__FILE__) . '/../classes/StripeAccountDetails.php';

function upgrade_module_3_2_0($module)
{
    /*
     * Clear both Smarty and Symfony cache.
     */
    Tools::clearAllCache();

    $result = Db::getInstance()->executeS('SHOW FIELDS FROM ' . _DB_PREFIX_ . 'stripe_payment_intent');

    if (!empty($result)) {
        $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'stripe_payment_intent MODIFY COLUMN id_payment_intent VARCHAR(100) NOT NULL';
        if (!Db::getInstance()->execute($sql)) {
            return false;
        }
    }

    /*
     * Clean cache for upgrade to prevent issue during module upgrade
     */
    $module->cleanModuleCache();

    /*
     * Clear both Smarty and Symfony cache.
     */
    Tools::clearAllCache();

    $installer = new Stripe_officialClasslib\Install\ModuleInstaller($module);
    $installer->installObjectModel('StripeAccountDetails');

    return true;
}
