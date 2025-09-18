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

use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use StripeOfficial\Classes\StripeProcessLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebhookEventHandler
{
    public const SUPPORTED_EVENTS = [
        Event::CHARGE_REFUNDED,
        Event::CHARGE_CAPTURED,
        Event::CHARGE_SUCCEEDED,
        Event::CHARGE_FAILED,
        Event::CHARGE_EXPIRED,
        Event::CHARGE_DISPUTE_CREATED,
        Event::PAYMENT_INTENT_CANCELED,
        Event::PAYMENT_INTENT_SUCCEEDED,
        Event::PAYMENT_INTENT_PAYMENT_FAILED,
    ];

    private $context;
    /**
     * @var Stripe_official
     */
    private $module;

    private $psStatuses;
    /**
     * @var PrestashopOrderService
     */
    private $prestashopOrderService;

    /**
     * @var StripePaymentIntentService
     */
    private $stripePaymentIntentService;

    /**
     * @var PrestashopBuildOrderService
     */
    private $prestashopBuildOrderService;

    /**
     * @var StripeCheckoutSessionService
     */
    private $stripeCheckoutSessionService;

    private $stripeAnonymize;

    public function __construct($context, $module)
    {
        $this->context = $context;
        $this->module = $module;

        $this->psStatuses = [
            Event::CHARGE_EXPIRED => Configuration::get('PS_OS_CANCELED'),
            Event::CHARGE_FAILED => Configuration::get('PS_OS_ERROR'),
            Event::CHARGE_SUCCEEDED => Configuration::get('PS_OS_PAYMENT'),
            Event::CHARGE_CAPTURED => Configuration::get('PS_OS_PAYMENT'),
            Event::PAYMENT_INTENT_SUCCEEDED => Configuration::get('PS_OS_PAYMENT'),
            Event::PAYMENT_INTENT_CANCELED => Configuration::get('PS_OS_CANCELED'),
            Event::PAYMENT_INTENT_PAYMENT_FAILED => Configuration::get('PS_OS_ERROR'),
            Event::CHARGE_REFUNDED => Configuration::get('PS_OS_REFUND'),
            Event::CHARGE_DISPUTE_CREATED => Configuration::get(Stripe_official::SEPA_DISPUTE),
        ];

        $secretKey = Stripe_official::getSecretKey();
        $this->stripePaymentIntentService = new StripePaymentIntentService($secretKey);
        $this->prestashopOrderService = new PrestashopOrderService($this->context, $this->module, $secretKey);
        $this->prestashopBuildOrderService = new PrestashopBuildOrderService($this->context, $this->module, $secretKey);
        $this->stripeAnonymize = new StripeAnonymize();
        $this->stripeCheckoutSessionService = new StripeCheckoutSessionService($secretKey);
    }

    public function handleRequest($content, $signatureHeader, $webhookSecret)
    {
        $this->processEvent($content, $signatureHeader, $webhookSecret);
    }

    public function processEvent($content, $signatureHeader, $webhookSecret): void
    {
        $data = json_decode($content, true);
        $contentAnonymized = $this->stripeAnonymize->anonymize($data);
        $eventType = $data['type'] ?? null;
        if (!$this->isSupportedEvent($eventType)) {
            return;
        }

        if (!$webhookSecret) {
            StripeProcessLogger::logError('Webhook secret not set. Please set webhook secret before using webhooks!', 'WebhookEventHandler', null);

            return;
        }

        $event = $this->getStripeEvent($content, $signatureHeader, $webhookSecret);
        if (!$event) {
            StripeProcessLogger::logError('Not a valid stripe event => ' . json_encode($contentAnonymized), 'WebhookEventHandler', null);

            return;
        }

        $cart = $this->getPsCart($event);
        if (!$this->isCartValid($cart)) {
            return;
        }

        $eventAnonymized = $this->stripeAnonymize->anonymize($event);
        StripeProcessLogger::logInfo('Process Webhook event => ' . json_encode($eventAnonymized), 'WebhookEventHandler', $cart->id);

        switch ($event->type) {
            case Event::CHARGE_REFUNDED:
                $this->refundedEvent($event, $cart);
                break;
            case Event::CHARGE_CAPTURED:
                $this->capturedEvent($event, $cart);
                break;
            case Event::CHARGE_SUCCEEDED:
            case Event::PAYMENT_INTENT_SUCCEEDED:
                $this->paymentSucceededEvent($event, $cart);
                break;
            case Event::CHARGE_EXPIRED:
            case Event::PAYMENT_INTENT_CANCELED:
                $this->cancelEvent($event, $cart);
                break;
            case Event::CHARGE_FAILED:
            case Event::PAYMENT_INTENT_PAYMENT_FAILED:
                $this->paymentFailedEvent($event, $cart);
                break;
            case Event::CHARGE_DISPUTE_CREATED:
                $this->disputeEvent($event, $cart);
                break;
        }
    }

    public function isSupportedEvent($eventType)
    {
        return in_array($eventType, self::SUPPORTED_EVENTS);
    }

    protected function getStripeEvent($content, $signatureHeader, $webhookSecret)
    {
        return $this->constructEvent($content, $signatureHeader, $webhookSecret);
    }

    protected function constructEvent($content, $signatureHeader, $secret)
    {
        $event = null;
        try {
            $event = Webhook::constructEvent($content, $signatureHeader, $secret);
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            StripeProcessLogger::logError('Invalid signature => ' . $e->getMessage() . ' - ' . $e->getTraceAsString(), 'WebhookEventHandler');
        }

        return $event;
    }

    public function refundedEvent(Event $event, ?Cart $cart = null): void
    {
        if (!$this->validateEventForStatusChange($event)) {
            return;
        }
        $psPaymentIntent = $this->getPsPaymentIntentFromEvent($event);
        if ($psPaymentIntent) {
            $refundedAmount = $event->data->object->amount_refunded;
            $capturedAmount = $event->data->object->amount_captured;
            $idStripe = $event->data->object->id;
            $currency = $event->data->object->currency;
            if ((int) $refundedAmount == (int) $capturedAmount) {
                $this->updatePsPaymentIntentStatus($psPaymentIntent, StripePaymentIntent::STATUS_REFUNDED);
                $this->updateStripePaymentAmount($capturedAmount, $idStripe, $currency);
                $this->syncStatusWithPs($psPaymentIntent, $cart);
            } elseif ($psPaymentIntent->getPsStatus() !== StripePaymentIntent::STATUS_REFUNDED) {
                $this->updateStripePaymentAmount($refundedAmount, $idStripe, $currency);
                $this->updatePsPaymentIntentStatus($psPaymentIntent, StripePaymentIntent::STATUS_PARTIALLY_REFUNDED);
                $this->syncStatusWithPs($psPaymentIntent, $cart);
            }
        }
    }

    public function cancelEvent($event, $cart = null): void
    {
        $psPaymentIntent = $this->getPsPaymentIntentFromEvent($event);
        if ($psPaymentIntent && $psPaymentIntent->validateStatusChange(StripePaymentIntent::STATUS_CANCEL)) {
            $this->updatePsPaymentIntentStatus($psPaymentIntent, StripePaymentIntent::STATUS_CANCEL);
            $this->syncStatusWithPs($psPaymentIntent, $cart);
        }
    }

    public function capturedEvent($event, $cart = null): void
    {
        $psPaymentIntent = $this->getPsPaymentIntentFromEvent($event);
        if ($psPaymentIntent && $psPaymentIntent->validateStatusChange(StripePaymentIntent::STATUS_SUCCESS)) {
            $this->updatePsPaymentIntentStatus($psPaymentIntent, StripePaymentIntent::STATUS_SUCCESS);
            $this->syncStatusWithPs($psPaymentIntent, $cart);
        }
    }

    public function paymentSucceededEvent($event, $cart = null): void
    {
        if (!$this->validateEventForStatusChange($event)) {
            return;
        }
        $psPaymentIntent = $this->getPsPaymentIntentFromEvent($event);
        $cartId = isset($event->data->object->metadata->id_cart) ? $event->data->object->metadata->id_cart : null;
        if (empty($psPaymentIntent->id_payment_intent) && $cartId) {
            $stripeIdempotencyKey = new StripeIdempotencyKey();
            $stripeIdempotencyKey->getByIdCart($cartId);
            if ($stripeIdempotencyKey->id_payment_intent) {
                $psPaymentIntent->findByIdPaymentIntent($stripeIdempotencyKey->id_payment_intent);
            }
        }
        if (empty($psPaymentIntent->id_payment_intent) && Configuration::get('STRIPE_ENABLE_PAYMENT_ELEMENTS') == 0) {
            $paymentIntentId = $this->getStripePaymentIntentIdFromEvent($event);
            $session = $this->stripeCheckoutSessionService->getStripeCheckoutSessionByPaymentIntentId($paymentIntentId);
            $psPaymentIntent->findByIdPaymentIntent($session->id);
        }
        if ($psPaymentIntent && $psPaymentIntent->validateStatusChange(StripePaymentIntent::STATUS_SUCCESS)) {
            $this->updatePsPaymentIntentStatus($psPaymentIntent, StripePaymentIntent::STATUS_SUCCESS);
            $this->syncStatusWithPs($psPaymentIntent, $cart);
        }
    }

    public function paymentFailedEvent($event, $cart = null): void
    {
        $psPaymentIntent = $this->getPsPaymentIntentFromEvent($event);
        if ($psPaymentIntent && $psPaymentIntent->validateStatusChange(StripePaymentIntent::STATUS_FAIL)) {
            $this->updatePsPaymentIntentStatus($psPaymentIntent, StripePaymentIntent::STATUS_FAIL);
            $this->syncStatusWithPs($psPaymentIntent, $cart);
        }
    }

    public function disputeEvent($event, $cart = null): void
    {
        $psPaymentIntent = $this->getPsPaymentIntentFromEvent($event);
        if ($psPaymentIntent && $psPaymentIntent->validateStatusChange(StripePaymentIntent::STATUS_DISPUTE)) {
            $this->updatePsPaymentIntentStatus($psPaymentIntent, StripePaymentIntent::STATUS_DISPUTE);
            $this->syncStatusWithPs($psPaymentIntent, $cart);
        }
    }

    public function getStripePaymentIntentIdFromEvent(Event $event)
    {
        return isset($event->data->object->payment_intent) ?
            $event->data->object->payment_intent :
            (
                isset($event->data->object->id) ?
                    $event->data->object->id :
                    null
            );
    }

    public function getPsPaymentIntentFromEvent(Event $event)
    {
        $psStripePaymentIntent = null;
        $paymentIntentId = $this->getStripePaymentIntentIdFromEvent($event);
        if ($paymentIntentId) {
            $psStripePaymentIntent = new StripePaymentIntent();
            $psStripePaymentIntent->findByIdPaymentIntent($paymentIntentId);
        }

        return $psStripePaymentIntent;
    }

    protected function updatePsPaymentIntentStatus(StripePaymentIntent $psPaymentIntent, string $status): void
    {
        $psPaymentIntent->setStatus($status);
        $psPaymentIntent->save();
    }

    public function syncStatusWithPs(StripePaymentIntent $psPaymentIntent, Cart $cart): void
    {
        $intentToUseAfterOrderUpdated = $stripePaymentIntent = null;
        $order = $this->getPsOrder($cart);
        if (!$order) {
            $psOrderStatus = $psPaymentIntent->getPsStatusForOrderCreation();
            if ($psOrderStatus) {
                $stripePaymentIntent = $this->prestashopOrderService->findStripePaymentIntent($psPaymentIntent->id_payment_intent);
                $orderModel = $this->prestashopBuildOrderService->buildAndCreatePrestashopOrder($psPaymentIntent, $stripePaymentIntent, null, $cart);

                StripeProcessLogger::logInfo('Create PrestaShop Order from Webhook: ' . json_encode($orderModel), 'WebhookEventHandler', $cart->id, $stripePaymentIntent->id);
                $this->stripePaymentIntentService->updateStripePaymentIntent($stripePaymentIntent, $orderModel->orderReference);
                if (isset($stripePaymentIntent->currency_conversion) && isset($stripePaymentIntent->currency_conversion->amount_total)) {
                    $amountInStoreCurrency = $stripePaymentIntent->currency_conversion->amount_total;
                } else {
                    $amountInStoreCurrency = $stripePaymentIntent->amount;
                }
                $psPaymentIntent->setAmount($amountInStoreCurrency);
                $psPaymentIntent->setIdPaymentIntent($stripePaymentIntent->id);
                $psPaymentIntent->save();
                $this->prestashopOrderService->createPsStripePayment($stripePaymentIntent, $orderModel);
                $order = $orderModel->order;
            }
        } else {
            $intentToUseAfterOrderUpdated = $this->prestashopOrderService->findStripePaymentIntent($psPaymentIntent->id_payment_intent);
            $this->prestashopOrderService->updatePsStripePayment($intentToUseAfterOrderUpdated, $cart->id);
            $psPaymentIntent->setAmount($intentToUseAfterOrderUpdated->amount);
            $psPaymentIntent->setIdPaymentIntent($intentToUseAfterOrderUpdated->id);
            $psPaymentIntent->save();
        }
        if (!$this->isOrderValid($order)) {
            return;
        }
        $psOrderStatus = $psPaymentIntent->getPsStatus();
        StripeProcessLogger::logInfo('Set current order state from Webhook: ' . $psOrderStatus, 'WebhookEventHandler', $cart->id);
        $order = new Order($order->id);
        if ($order->getCurrentState() === (int) $psOrderStatus) {
            return;
        }
        $order->setCurrentState((int) $psOrderStatus);
        if ($intentToUseAfterOrderUpdated) {
            $this->prestashopOrderService->updatePsOrders($intentToUseAfterOrderUpdated, $order->id);
            $this->prestashopOrderService->updateTransactionIdForPSOrder($cart->id, $intentToUseAfterOrderUpdated);
        }
    }

    public function validateEventForStatusChange($event): bool
    {
        $check = false;
        switch ($event->type) {
            case Event::CHARGE_REFUNDED:
            case Event::CHARGE_SUCCEEDED:
                $check = $event->data->object->captured ?: false;
                break;
        }

        return $check;
    }

    public function getPsCart(Event $event): ?Cart
    {
        $cart = null;
        $paymentIntentId = $this->getStripePaymentIntentIdFromEvent($event);
        if ($paymentIntentId) {
            $psIdempotencyKey = new StripeIdempotencyKey();
            $psIdempotencyKey->getByIdPaymentIntent($paymentIntentId);

            $cart = isset($psIdempotencyKey->id_cart) ? new Cart($psIdempotencyKey->id_cart) : null;
        }
        if (!$cart) {
            $cartId = isset($event->data->object->metadata->id_cart) ? $event->data->object->metadata->id_cart : null;
            $cart = $cartId ? new Cart($cartId) : null;
        }

        return $cart ?: null;
    }

    public function isCartValid($cart = null): bool
    {
        if (!$cart) {
            return false;
        }
        if ((int) $cart->id_shop_group !== (int) Stripe_official::getShopGroupIdContext() || (int) $cart->id_shop !== (int) Stripe_official::getShopIdContext()) {
            return false;
        }

        return true;
    }

    public function getPsOrder(Cart $cart): ?Order
    {
        $orderId = Order::getIdByCartId($cart->id) ?: null;
        $order = $orderId ? new Order($orderId) : null;

        return $order ?: null;
    }

    public function isOrderValid($order = null): bool
    {
        if (!$order) {
            return false;
        }
        if ('stripe_official' !== $order->module) {
            return false;
        }

        return true;
    }

    protected function updateStripePaymentAmount(string $amount, $idStripe, $currency): void
    {
        $amount = Stripe_official::isZeroDecimalCurrency(Tools::strtoupper($currency)) ? $amount : $amount / 100;
        Db::getInstance()->Execute(
            'UPDATE `' . _DB_PREFIX_ . 'stripe_payment` SET `refund` = "'
            . pSQL($amount) . '" WHERE `id_stripe` = "' . pSQL($idStripe) . '"'
        );
    }
}
