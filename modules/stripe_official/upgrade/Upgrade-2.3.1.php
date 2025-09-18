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

require_once dirname(__FILE__) . '/../classes/StripeIdempotencyKey.php';

function upgrade_module_2_3_1($module)
{
    $installer = new Stripe_officialClasslib\Install\ModuleInstaller($module);
    $installer->installObjectModel('StripeIdempotencyKey');
    $installer->installObjectModel('StripePayment');

    $sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'stripe_official_processlogger` MODIFY msg TEXT';
    if (!Db::getInstance()->execute($sql)) {
        return false;
    }

    $indexes = [
        'id_idempotency_key',
        'id_cart',
        'idempotency_key',
        'id_payment_intent',
    ];
    $already_indexed = [];
    $results = Db::getInstance()->executeS('SHOW INDEX FROM ' . _DB_PREFIX_ . 'stripe_idempotency_key');

    foreach ($results as $result) {
        array_push($already_indexed, $result['Column_name']);
    }

    $to_index = array_diff($indexes, $already_indexed);

    if (!empty($to_index)) {
        $sql = '';
        foreach ($to_index as $index) {
            $sql .= 'ALTER TABLE `' . _DB_PREFIX_ . 'stripe_idempotency_key` ADD INDEX( `' . $index . '`);';
        }

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }
    }

    return true;
}
