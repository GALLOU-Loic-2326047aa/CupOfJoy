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
class Order extends OrderCore
{
    /** Set current order status
     * @param int $id_order_state
     * @param int $id_employee (/!\ not optional except for Webservice
     */
    public function setCurrentState($id_order_state, $id_employee = 0)
    {
        if ($this->module !== 'stripe_official') {
            return parent::setCurrentState($id_order_state, $id_employee);
        }

        $lock = new StripePdoLockService('setCurrentState-' . $this->id . '-' . $id_order_state, 5);
        $lock->executeUnderLock(function () use ($id_order_state, $id_employee) {
            $last_order_state = Db::getInstance()->getValue(
                'SELECT id_order_state FROM ' . _DB_PREFIX_ . 'order_history WHERE id_order = ' . $this->id . ' ORDER BY id_order_history DESC',
                false
            );
            if ($last_order_state && (int) $last_order_state == (int) $id_order_state) {
                return;
            }
            parent::setCurrentState($id_order_state, $id_employee);
        });
    }
}
