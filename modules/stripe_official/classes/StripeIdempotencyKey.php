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
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
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
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
class StripeIdempotencyKey extends ObjectModel
{
    /** @var int */
    public $id_cart;
    /** @var string */
    public $idempotency_key;
    /** @var string */
    public $id_payment_intent;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'stripe_idempotency_key',
        'primary' => 'id_idempotency_key',
        'fields' => [
            'id_cart' => [
                'type' => ObjectModel::TYPE_INT,
                'validate' => 'isInt',
                'size' => 10,
            ],
            'idempotency_key' => [
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'size' => 255,
            ],
            'id_payment_intent' => [
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'size' => 255,
            ],
        ],
    ];

    public function setIdCart($id_cart)
    {
        $this->id_cart = $id_cart;
    }

    public function getIdCart()
    {
        return $this->id_cart;
    }

    public function setIdempotencyKey($idempotency_key)
    {
        $this->idempotency_key = $idempotency_key;
    }

    public function getIdempotencyKey()
    {
        return $this->idempotency_key;
    }

    public function setIdPaymentIntent($id_payment_intent)
    {
        $this->id_payment_intent = $id_payment_intent;
    }

    public function getIdPaymentIntent()
    {
        return $this->id_payment_intent;
    }

    private function getByField($field, $value)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from(static::$definition['table']);
        $query->where("$field = '" . pSQL($value) . "'");
        if ($field != 'id_idempotency_key') {
            $query->orderBy('id_idempotency_key DESC');
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query->build());
        if (false == $result) {
            return $this;
        }

        $this->hydrate($result);

        return $this;
    }

    public function getByIdCart($id_cart)
    {
        return $this->getByField('id_cart', $id_cart);
    }

    public function getByIdempotencyKey($idempotency_key)
    {
        return $this->getByField('idempotency_key', $idempotency_key);
    }

    public function getByIdPaymentIntent($id_payment_intent)
    {
        return $this->getByField('id_payment_intent', $id_payment_intent);
    }

    /**
     * @throws PrestaShopException
     */
    public function createIdempotencyKey($cartId)
    {
        $idempotencyKey = $cartId . '_' . uniqid();
        $stripeIdempotencyKey = new StripeIdempotencyKey();
        $stripeIdempotencyKey->setIdCart($cartId);
        $stripeIdempotencyKey->setIdempotencyKey($idempotencyKey);

        $stripeIdempotencyKey->save();

        return $stripeIdempotencyKey;
    }

    /**
     * @throws PrestaShopException
     */
    public function updateIdempotencyKey($cartId, $intent)
    {
        $stripeIdempotencyKey = new StripeIdempotencyKey();
        $stripeIdempotencyKey = $stripeIdempotencyKey->getByIdCart($cartId);
        $stripeIdempotencyKey->setIdPaymentIntent($intent->id);
        $stripeIdempotencyKey->save();
    }

    /**
     * @param int $cartId
     *
     * @return StripeIdempotencyKey|static
     *
     * @throws PrestaShopException
     */
    public static function getOrCreateIdempotencyKey($cartId)
    {
        $stripeIdempotencyKey = new self();
        $stripeIdempotencyKey = $stripeIdempotencyKey->getByIdCart($cartId);
        if (!$stripeIdempotencyKey->id) {
            $stripeIdempotencyKey = $stripeIdempotencyKey->createIdempotencyKey($cartId);
        }

        return $stripeIdempotencyKey;
    }
}
