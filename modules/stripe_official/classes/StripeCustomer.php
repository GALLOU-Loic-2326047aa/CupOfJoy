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

class StripeCustomer extends ObjectModel
{
    const CUSTOMER_CREATE = '_CUSTOMER_CREATE';
    const CUSTOMER_UPDATE = '_CUSTOMER_UPDATE';

    /** @var int */
    public $id_customer;
    /** @var string */
    public $stripe_customer_key;
    /** @var string */
    public $id_account;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'stripe_customer',
        'primary' => 'id_stripe_customer',
        'fields' => [
            'id_customer' => [
                'type' => ObjectModel::TYPE_INT,
                'validate' => 'isInt',
                'size' => 10,
            ],
            'stripe_customer_key' => [
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'size' => 50,
            ],
            'id_account' => [
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'size' => 50,
            ],
        ],
    ];

    public function setIdCustomer($id_customer)
    {
        $this->id_customer = $id_customer;
    }

    public function getIdCustomer()
    {
        return $this->id_customer;
    }

    public function setStripeCustomerKey($stripe_customer_key)
    {
        $this->stripe_customer_key = $stripe_customer_key;
    }

    public function getStripeCustomerKey()
    {
        return $this->stripe_customer_key;
    }

    public function setIdAccount($id_account)
    {
        $this->id_account = $id_account;
    }

    public function getIdAccount()
    {
        return $this->id_account;
    }

    public function getCustomerById($id_customer, $id_account)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from(static::$definition['table']);
        $query->where('id_customer = ' . pSQL((int) $id_customer));
        $query->where('id_account = "' . pSQL($id_account) . '"');

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query->build());
        if (true === empty($result)) {
            return $this;
        }

        $this->hydrate($result);

        return $this;
    }
}
