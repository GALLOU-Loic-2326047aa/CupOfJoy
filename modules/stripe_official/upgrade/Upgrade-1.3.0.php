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

function upgrade_module_1_3_0()
{
    $alter_stripe_payment_table = true;
    $result = Db::getInstance()->executeS('SHOW FIELDS FROM ' . _DB_PREFIX_ . 'stripe_payment');

    if (!empty($result)) {
        foreach ($result as $res) {
            if ($res['Field'] == 'id_account') {
                $alter_stripe_payment_table = false;
            }
        }

        if ($alter_stripe_payment_table === true) {
            $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'stripe_payment ADD state tinyint(4) NOT NULL';
            if (!Db::getInstance()->execute($sql)) {
                return false;
            }
        }
    }

    return true;
}
