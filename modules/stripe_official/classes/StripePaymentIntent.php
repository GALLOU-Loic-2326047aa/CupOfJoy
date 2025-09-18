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

use Stripe\Charge;
use Stripe\PaymentIntent;
use StripeOfficial\Classes\StripeProcessLogger;

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
class StripePaymentIntent extends ObjectModel
{
    const PAYMENT_INTENT_CREATE = '_PAYMENT_INTENT_CREATE';
    const PAYMENT_INTENT_UPDATE = '_PAYMENT_INTENT_UPDATE';

    public const STATUS_CANCEL = '0'; // User has aborted the payment at the payment provider's site
    public const STATUS_SUCCESS = '1'; // Successful immediate payment
    public const STATUS_FAIL = '2'; // Payment has failed (e.g. missing funds)
    public const STATUS_AUTHORIZE = '3'; // Delayed payment
    public const STATUS_REFUNDED = '4'; // Refund payment
    public const STATUS_IN_PROGRESS = '5'; // Payment in progress (e.g. blocked/needs unblocking)
    public const STATUS_UNCONFIRMED = '6'; // Payments which are blocked (e.g. fraudulent cards)
    public const STATUS_DISPUTE = '7'; // Payments which are blocked (e.g. fraudulent cards)
    public const STATUS_PARTIALLY_REFUNDED = '8'; // Partial refund payment

    public const STRIPE_PAYMENT_INTENT_STATUSES = [
        PaymentIntent::STATUS_CANCELED => self::STATUS_CANCEL,
        PaymentIntent::STATUS_SUCCEEDED => self::STATUS_SUCCESS,
        PaymentIntent::STATUS_PROCESSING => self::STATUS_UNCONFIRMED,
        PaymentIntent::STATUS_REQUIRES_CAPTURE => self::STATUS_AUTHORIZE,
        PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD => self::STATUS_UNCONFIRMED,
        PaymentIntent::STATUS_REQUIRES_CONFIRMATION => self::STATUS_UNCONFIRMED,
        PaymentIntent::STATUS_REQUIRES_ACTION => self::STATUS_UNCONFIRMED,
    ];

    public const STRIPE_DECLINE_CODES = [
        Charge::DECLINED_FRAUDULENT => self::STATUS_UNCONFIRMED,
        Charge::DECLINED_STOLEN_CARD => self::STATUS_UNCONFIRMED,
    ];

    public const ALLOWED_STATUS_CHANGE = [
        self::STATUS_CANCEL => [
            self::STATUS_SUCCESS,
            self::STATUS_REFUNDED,
            self::STATUS_UNCONFIRMED,
            self::STATUS_DISPUTE,
            self::STATUS_PARTIALLY_REFUNDED,
        ],
        self::STATUS_SUCCESS => [
            self::STATUS_CANCEL,
            self::STATUS_REFUNDED,
            self::STATUS_DISPUTE,
            self::STATUS_PARTIALLY_REFUNDED,
        ],
        self::STATUS_FAIL => [
            self::STATUS_IN_PROGRESS,
            self::STATUS_SUCCESS,
            self::STATUS_UNCONFIRMED,
            self::STATUS_DISPUTE,
        ],
        self::STATUS_AUTHORIZE => [
            self::STATUS_CANCEL,
            self::STATUS_FAIL,
            self::STATUS_SUCCESS,
            self::STATUS_DISPUTE,
        ],
        self::STATUS_REFUNDED => [],
        self::STATUS_IN_PROGRESS => [
            self::STATUS_AUTHORIZE,
            self::STATUS_CANCEL,
            self::STATUS_FAIL,
            self::STATUS_SUCCESS,
            self::STATUS_DISPUTE,
            self::STATUS_UNCONFIRMED,
        ],
        self::STATUS_UNCONFIRMED => [
            self::STATUS_AUTHORIZE,
            self::STATUS_CANCEL,
            self::STATUS_FAIL,
            self::STATUS_SUCCESS,
            self::STATUS_DISPUTE,
        ],
        self::STATUS_DISPUTE => [
            self::STATUS_AUTHORIZE,
            self::STATUS_CANCEL,
            self::STATUS_FAIL,
            self::STATUS_SUCCESS,
        ],
    ];

    /** @var string */
    public $id_payment_intent;
    /** @var string */
    public $status;
    /** @var float */
    public $amount;
    /** @var string */
    public $currency;
    /** @var date */
    public $date_add;
    /** @var date */
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'stripe_payment_intent',
        'primary' => 'id_stripe_payment_intent',
        'fields' => [
            'id_payment_intent' => [
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'size' => 100,
            ],
            'status' => [
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'size' => 30,
            ],
            'amount' => [
                'type' => ObjectModel::TYPE_FLOAT,
                'validate' => 'isFloat',
                'size' => 10,
                'scale' => 2,
            ],
            'currency' => [
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'size' => 3,
            ],
            'date_add' => [
                'type' => ObjectModel::TYPE_DATE,
                'validate' => 'isDate',
            ],
            'date_upd' => [
                'type' => ObjectModel::TYPE_DATE,
                'validate' => 'isDate',
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

    public function setAmount($amount)
    {
        $module = Module::getInstanceByName('stripe_official');
        $amount = Stripe_official::isZeroDecimalCurrency(Tools::strtoupper($this->currency)) ? $amount : $amount / 100;

        $this->amount = $amount;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setDateAdd($date_add)
    {
        $this->date_add = $date_add;
    }

    public function getDateAdd()
    {
        return $this->date_add;
    }

    public function setDateUpd($date_upd)
    {
        $this->date_upd = $date_upd;
    }

    public function getDateUpd()
    {
        return $this->date_upd;
    }

    public function findByIdPaymentIntent($idPaymentIntent)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from(self::$definition['table']);
        $query->where('id_payment_intent = "' . pSQL($idPaymentIntent) . '"');

        $data = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query->build());
        if (!$data) {
            return false;
        }
        $this->hydrate($data);

        return $this;
    }

    /**
     * @param $intent
     *
     * @return StripePaymentIntent|static
     */
    public static function getOrCreatePaymentIntent($intent)
    {
        $paymentIntent = new self();
        $paymentIntent = $paymentIntent->findByIdPaymentIntent($intent->id);
        if (!$paymentIntent) {
            $paymentIntent = self::createPaymentIntent($intent);
        }

        return $paymentIntent;
    }

    public static function createPaymentIntent($intent)
    {
        $paymentIntent = new self();
        $paymentIntent->setIdPaymentIntent($intent->id);
        $paymentIntent->setStatus(self::STATUS_IN_PROGRESS);
        $paymentIntent->setAmount(0);
        $paymentIntent->setCurrency($intent->currency);
        $paymentIntent->setDateAdd(date('Y-m-d H:i:s', $intent->created));
        $paymentIntent->setDateUpd(date('Y-m-d H:i:s', $intent->created));
        $paymentIntent->save(false, false);

        return $paymentIntent;
    }

    public function validateStatusChange($status)
    {
        if (!isset(self::ALLOWED_STATUS_CHANGE[$this->getStatus()])) {
            StripeProcessLogger::logInfo('validateStatusChange - allowed status change not indexed: ' . json_encode($this->getStatus()) . ' for: ' . $this->id_payment_intent, 'StripePaymentIntent');

            return false;
        }

        return in_array($status, self::ALLOWED_STATUS_CHANGE[$this->getStatus()]);
    }

    public function getStatusFromStripePaymentIntentStatus($paymentIntentStatus)
    {
        return self::STRIPE_PAYMENT_INTENT_STATUSES[$paymentIntentStatus];
    }

    public function getStatusFromStripeDeclineCode($declineCode)
    {
        if (!$declineCode) {
            return null;
        }

        return in_array($declineCode, array_keys(self::STRIPE_DECLINE_CODES)) ? self::STRIPE_DECLINE_CODES[$declineCode] : self::STATUS_FAIL;
    }

    public function getPsStatusForOrderCreation($paymentMethodType = null)
    {
        $status = null;
        switch ($this->status) {
            case self::STATUS_UNCONFIRMED:
                $status = Configuration::get(Stripe_official::PAYMENT_WAITING);
                break;
            case self::STATUS_AUTHORIZE:
                $status = Configuration::get(Stripe_official::CAPTURE_WAITING);
                break;
            case self::STATUS_SUCCESS:
                if ($paymentMethodType === 'oxxo') {
                    $status = Configuration::get(Stripe_official::OXXO_WAITING);
                } elseif ($paymentMethodType === 'sepa_debit') {
                    $status = Configuration::get(Stripe_official::SEPA_WAITING);
                } else {
                    $status = Configuration::get('PS_OS_PAYMENT');
                }
                break;
        }

        return $status;
    }

    public function getPsStatus()
    {
        $status = null;
        switch ($this->status) {
            case self::STATUS_SUCCESS:
                $status = Configuration::get('PS_OS_PAYMENT');
                break;
            case self::STATUS_CANCEL:
                $status = Configuration::get('PS_OS_CANCELED');
                break;
            case self::STATUS_REFUNDED:
                $status = Configuration::get('PS_OS_REFUND');
                break;
            case self::STATUS_FAIL:
                $status = Configuration::get('PS_OS_ERROR');
                break;
            case self::STATUS_UNCONFIRMED:
            case self::STATUS_IN_PROGRESS:
                $status = Configuration::get(Stripe_official::PAYMENT_WAITING);
                break;
            case self::STATUS_AUTHORIZE:
                $status = Configuration::get(Stripe_official::CAPTURE_WAITING);
                break;
            case self::STATUS_DISPUTE:
                $status = Configuration::get(Stripe_official::SEPA_DISPUTE);
                break;
            case self::STATUS_PARTIALLY_REFUNDED:
                $status = Configuration::get(Stripe_official::PARTIAL_REFUND);
                break;
        }

        return $status;
    }
}
