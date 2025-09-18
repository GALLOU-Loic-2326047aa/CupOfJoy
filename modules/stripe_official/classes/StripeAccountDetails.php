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

class StripeAccountDetails extends ObjectModel
{
    /** @var string */
    public $id_stripe_account;
    /** @var string */
    public $id_webhook;
    /** @var string */
    public $webhook_secret;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'stripe_webhook',
        'primary' => 'id_stripe_account_details',
        'fields' => [
            'id_stripe_account' => [
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'size' => 50,
            ],
            'id_webhook' => [
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'size' => 50,
                'allow_null' => true,
            ],
            'webhook_secret' => [
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'size' => 255,
                'allow_null' => true,
            ],
        ],
    ];

    public function setIdStripeAccount($id_stripe_account)
    {
        $this->id_stripe_account = $id_stripe_account;
    }

    public function getIdStripeAccount()
    {
        return $this->id_stripe_account;
    }

    public function setIdWebhook($id_webhook)
    {
        $this->id_webhook = $id_webhook;
    }

    public function getIdWebhook()
    {
        return $this->id_webhook;
    }

    public function setWebhookSecret($webhook_secret)
    {
        $this->webhook_secret = $webhook_secret;
    }

    public function getWebhookSecret()
    {
        return $this->webhook_secret;
    }

    public function getByIdStripeAccount($id_stripe_account)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from(static::$definition['table']);
        $query->where('id_stripe_account = "' . pSQL($id_stripe_account) . '"');

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query->build());
        if (false == $result) {
            return $this;
        }

        $this->hydrate($result);

        return $this;
    }
}
