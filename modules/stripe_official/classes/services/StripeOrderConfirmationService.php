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

use StripeOfficial\Classes\StripeProcessLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

class StripeOrderConfirmationService
{
    private $context;
    /**
     * @var StripePaymentIntentService
     */
    private $stripePaymentIntentService;

    /**
     * @var PrestashopOrderService
     */
    private $prestashopOrderService;

    /**
     * @var PrestashopBuildOrderService
     */
    private $prestashopBuildOrderService;

    /**
     * @var StripeCheckoutSessionService
     */
    private $stripeCheckoutSessionService;

    public function __construct($context, $module, $secretKey = null)
    {
        $this->context = $context;
        $secretKey = $secretKey ?: Stripe_official::getSecretKey();
        $this->stripePaymentIntentService = new StripePaymentIntentService($secretKey);
        $this->prestashopOrderService = new PrestashopOrderService($this->context, $module, $secretKey);
        $this->prestashopBuildOrderService = new PrestashopBuildOrderService($this->context, $module, $secretKey);
        $this->stripeCheckoutSessionService = new StripeCheckoutSessionService($secretKey);
    }

    public function orderConfirmationNew(?string $cartId = null)
    {
        $failUrl = $redirectUrl = $this->context->link->getModuleLink('stripe_official', 'orderFailure', ['cartId' => $cartId], true);
        $intent = null;
        StripeProcessLogger::logInfo('Order Confirmation Return init: ', 'StripeOrderConfirmationService', $cartId);
        try {
            if (!$cartId) {
                StripeProcessLogger::logWarning('Empty $cartId', 'StripeOrderConfirmationService', $cartId);

                return $failUrl;
            }
            $this->prestashopOrderService->removeProductsFromDuplicateCart(new Cart($cartId));

            $stripeIdempotencyKey = StripeIdempotencyKey::getOrCreateIdempotencyKey($cartId);
            if (!$stripeIdempotencyKey->id_payment_intent) {
                StripeProcessLogger::logWarning('Empty $stripeIdempotencyKey', 'StripeOrderConfirmationService', $cartId);

                return $failUrl;
            }
            $intent = $this->prestashopOrderService->findStripePaymentIntent($stripeIdempotencyKey->id_payment_intent);
            if (!$intent) {
                StripeProcessLogger::logWarning('Empty $intent: ' . $stripeIdempotencyKey->id_payment_intent, 'StripeOrderConfirmationService', $cartId);

                return $failUrl;
            }

            $psStripePaymentIntent = new StripePaymentIntent();
            $psStripePaymentIntent->findByIdPaymentIntent($stripeIdempotencyKey->id_payment_intent);
            if (empty($psStripePaymentIntent->id_payment_intent)) {
                // If it is empty, then checkout session id is in DB, but idempotency key is already payment intent
                // Because here the idempotency key was not refreshed, it mens the webhook already updated it, but did not reach to the point to update the payment intent too
                // We wait 1 second, until webhook updates the payment intent table too, then retry
                sleep(1);
                $psStripePaymentIntent->findByIdPaymentIntent($stripeIdempotencyKey->id_payment_intent);
            }

            if (empty($psStripePaymentIntent->id_payment_intent) && Configuration::get('STRIPE_ENABLE_PAYMENT_ELEMENTS') == 0) {
                $session = $this->stripeCheckoutSessionService->getStripeCheckoutSessionByPaymentIntentId($intent->id);
                $psStripePaymentIntent->findByIdPaymentIntent($session->id);
            }

            $lastPaymentError = $intent->last_payment_error ?? null;
            if ($lastPaymentError) {
                StripeProcessLogger::logWarning('Last Payment Error: ' . json_encode($lastPaymentError), 'StripeOrderConfirmationService', $cartId, $intent->id);

                return $failUrl;
            }
            $status = $psStripePaymentIntent->getStatusFromStripePaymentIntentStatus($intent->status);
            if ($psStripePaymentIntent->validateStatusChange($status)) {
                $psStripePaymentIntent->setIdPaymentIntent($intent->id);
                $psStripePaymentIntent->setAmount($intent->amount);
                $psStripePaymentIntent->setStatus($status);
                $psStripePaymentIntent->save();
            }

            $orderId = Order::getIdByCartId($cartId);
            $order = new Order($orderId);
            $orderStatus = $psStripePaymentIntent->getPsStatus();
            $order->setCurrentState((int) $orderStatus);
            $orderReference = $order ? $this->context->shop->name . ' / Reference: ' . $order->reference . ' / Order: ' . $orderId : null;
            $this->stripePaymentIntentService->updateStripePaymentIntent($intent, $orderReference);
            $this->prestashopOrderService->updateTransactionIdForPSOrder($cartId, $intent);

            $this->prestashopOrderService->updatePsStripePayment($intent, $cartId);
            $this->prestashopOrderService->saveStripeCapture($orderId, $intent, $orderStatus);
            $this->prestashopOrderService->updatePsOrders($intent, $orderId);
            $redirectUrl = $this->prestashopOrderService->getOrderConfirmationLink($order) ?? $failUrl;

            StripeProcessLogger::logInfo('Order Confirmation Return URL: ' . json_encode($redirectUrl), 'StripeOrderConfirmationService', $cartId);
        } catch (Exception $e) {
            StripeProcessLogger::logError('Order Confirmation Error => ' . $e->getMessage() . ' - ' . $e->getTraceAsString(), 'StripeOrderConfirmationService', $cartId, $intent ? $intent->id : '');
        }

        return $redirectUrl;
    }

    public function orderConfirmationLegacy(?string $cartId = null)
    {
        $failUrl = $redirectUrl = $this->context->link->getModuleLink('stripe_official', 'orderFailure', ['cartId' => $cartId], true);

        $intent = null;
        try {
            if (!$cartId) {
                StripeProcessLogger::logWarning('Empty $cartId', 'StripeOrderConfirmationService', $cartId);

                return $failUrl;
            }

            StripeProcessLogger::logInfo('Order Confirmation Legacy start ', 'StripeOrderConfirmationService', $cartId);
            $stripeIdempotencyKey = StripeIdempotencyKey::getOrCreateIdempotencyKey($cartId);
            if (!$stripeIdempotencyKey->id_payment_intent) {
                StripeProcessLogger::logWarning('Empty $stripeIdempotencyKey', 'StripeOrderConfirmationService', $cartId);

                return $failUrl;
            }
            $intent = $this->prestashopOrderService->findStripePaymentIntent($stripeIdempotencyKey->id_payment_intent);
            if (!$intent) {
                StripeProcessLogger::logWarning('Empty $intent: ' . $stripeIdempotencyKey->id_payment_intent, 'StripeOrderConfirmationService', $cartId);

                return $failUrl;
            }

            $psStripePaymentIntent = new StripePaymentIntent();
            $psStripePaymentIntent->findByIdPaymentIntent($stripeIdempotencyKey->id_payment_intent);
            if (empty($psStripePaymentIntent->id_payment_intent)) {
                sleep(1);
                $psStripePaymentIntent->findByIdPaymentIntent($stripeIdempotencyKey->id_payment_intent);
            }

            $status = null;
            $lastPaymentError = $intent->last_payment_error ?? null;
            if ($lastPaymentError) {
                $chargeDeclineCode = $lastPaymentError->decline_code ?? $lastPaymentError->code ?? null;
                $status = $psStripePaymentIntent->getStatusFromStripeDeclineCode($chargeDeclineCode);
                StripeProcessLogger::logInfo('Last Payment Error: ' . json_encode($lastPaymentError), 'StripeOrderConfirmationService', $cartId, $intent->id);
            }
            $status = $status ?? $psStripePaymentIntent->getStatusFromStripePaymentIntentStatus($intent->status);

            if ($intent->status == Stripe\PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD) {
                StripeProcessLogger::logWarning('Payment status not confirmed: ' . $intent->status, 'orderConfirmationReturn', $cartId, $intent->id);

                return $failUrl;
            }

            if ($psStripePaymentIntent->validateStatusChange($status)) {
                $psStripePaymentIntent->setIdPaymentIntent($intent->id);

                if (isset($intent->currency_conversion) && isset($intent->currency_conversion->amount_total)) {
                    $amountInStoreCurrency = $intent->currency_conversion->amount_total;
                } else {
                    $amountInStoreCurrency = $intent->amount;
                }

                $psStripePaymentIntent->setAmount($amountInStoreCurrency);
                $psStripePaymentIntent->setStatus($status);
                $psStripePaymentIntent->save();
            }
            $stripeIdempotencyKeyObject = new StripeIdempotencyKey();
            $stripeIdempotencyKeyObject->updateIdempotencyKey($cartId, $intent);
            $psCart = new Cart($cartId);

            $orderModel = $this->prestashopOrderService->buildOrderModel($psStripePaymentIntent, $intent, $psCart);
            $orderModel = $this->prestashopOrderService->createPsOrder($orderModel);
            StripeProcessLogger::logInfo('Create PrestaShop Order: ' . json_encode($orderModel), 'StripeOrderConfirmationService', $cartId, $intent->id);

            $orderId = Order::getIdByCartId($cartId);
            $this->prestashopOrderService->updateTransactionIdForPSOrder($cartId, $intent);
            $this->stripePaymentIntentService->updateStripePaymentIntent($intent, $orderModel->orderReference);
            $this->prestashopOrderService->saveStripeCapture($orderId, $intent, $orderModel->status);
            $this->prestashopOrderService->createPsStripePayment($intent, $orderModel);

            $redirectUrl = $orderModel->order ? $this->prestashopOrderService->getOrderConfirmationLink($orderModel->order) : $failUrl;

            StripeProcessLogger::logInfo('Order Confirmation Return Legacy URL: ' . json_encode($redirectUrl), 'StripeOrderConfirmationService', $cartId);
        } catch (Exception $e) {
            StripeProcessLogger::logError('Order Confirmation Error => ' . $e->getMessage() . ' - ' . $e->getTraceAsString(), 'StripeOrderConfirmationService', $cartId, $intent ? $intent->id : '');
        }

        return $redirectUrl;
    }

    public function checkOrderAlreadyConfirmed(?string $cartId = null)
    {
        $failUrl = $this->context->link->getModuleLink('stripe_official', 'orderFailure', ['cartId' => $cartId], true);
        if (!$cartId) {
            StripeProcessLogger::logWarning('Empty $cartId in checkOrderAlreadyConfirmed', 'StripeOrderConfirmationService', $cartId);
            throw new StripeOfficial\Classes\exceptions\StripeOrderAlreadyConfirmedException($failUrl);
        }
        $payment_id = StripePayment::getIdByCartId($cartId);
        if ($payment_id) {
            $payment = new StripePayment($payment_id);
            if (!empty($payment->id_stripe) && !empty($payment->id_payment_intent)) {
                $orderId = Order::getIdByCartId($cartId);
                $order = new Order($orderId);
                StripeProcessLogger::logWarning('Order already confirmed OrderId: ' . $orderId, 'StripeOrderConfirmationService', $cartId);
                throw new StripeOfficial\Classes\exceptions\StripeOrderAlreadyConfirmedException($this->prestashopOrderService->getOrderConfirmationLink($order));
            }
        }
    }
}
