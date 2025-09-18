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

/**
 * 2007-2022 Stripe.
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
 * @author    202-ecommerce <tech@202-ecommerce.com>
 * @copyright Copyright (c) Stripe
 * @license   Academic Free License (AFL 3.0)
 */
class StripeEvent extends ObjectModel
{
    /**
     * @var string
     */
    public $id_payment_intent;
    /**
     * @var string
     */
    public $status;
    /**
     * @var DateTime
     */
    public $date_add;
    /**
     * @var bool
     */
    public $is_processed;

    public $flow_type = 'webhook';

    /**
     * @var array
     *
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'stripe_event',
        'primary' => 'id_stripe_event',
        'fields' => [
            'id_payment_intent' => [
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'size' => 40,
            ],
            'status' => [
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'size' => 30,
            ],
            'date_add' => [
                'type' => ObjectModel::TYPE_DATE,
                'validate' => 'isDate',
            ],
            'is_processed' => [
                'type' => ObjectModel::TYPE_BOOL,
                'validate' => 'isBool',
            ],
            'flow_type' => [
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'size' => 30,
            ],
        ],
    ];

    public function setIdPaymentIntent($id_payment_intent)
    {
        $this->id_payment_intent = $id_payment_intent;
    }

    public function getIdPaymentIntent()
    {
        return $this->id_payment_intent;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setDateAdd($date_add)
    {
        $this->date_add = $date_add;
    }

    public function getDateAdd()
    {
        return $this->date_add;
    }

    public function isProcessed()
    {
        return $this->is_processed;
    }

    public function setIsProcessed($is_processed)
    {
        $this->is_processed = $is_processed;
    }

    public function setFlowType($flow_type)
    {
        $this->flow_type = $flow_type;
    }

    public function getFlowType()
    {
        return $this->flow_type;
    }

    public function save($null_values = false, $auto_date = false)
    {
        return parent::save($null_values, $auto_date);
    }
}
