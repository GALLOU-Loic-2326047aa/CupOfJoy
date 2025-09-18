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

use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use StripeOfficial\Classes\StripeProcessLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

class StripeCheckoutSessionService
{
    const SESSION_CREATE = '_SESSION_CREATE';

    /**
     * @var StripeClient
     */
    private $stripeClient;
    /**
     * @var StripeCustomerService
     */
    private $stripeCustomerService;
    /**
     * @var StripePaymentIntentService
     */
    private $stripePaymentIntentService;

    private $stripeAnonymize;

    /**
     * @param string|null $secretKey
     */
    public function __construct($secretKey = null)
    {
        $secretKey = $secretKey ?: Stripe_official::getSecretKey();
        $this->stripeClient = new StripeClient([
            'api_key' => $secretKey,
            'stripe_version' => Stripe_official::STRIPE_API_VERSION,
        ]);
        $this->stripeCustomerService = new StripeCustomerService($secretKey);
        $this->stripePaymentIntentService = new StripePaymentIntentService($secretKey);
        $this->stripeAnonymize = new StripeAnonymize();
    }

    /**
     * @param CartContextModel $cartContextModel
     * @param bool $separateAuthAndCapture
     * @param string $successReturnUrl
     * @param string $failReturnUrl
     * @param OrderModel $orderModel
     *
     * @return array
     * @return array
     *
     * @throws PrestaShopException
     * @throws ApiErrorException
     */
    public function buildStripeCheckoutParams($cartContextModel, $separateAuthAndCapture, $successReturnUrl, $failReturnUrl, $orderModel = null): array
    {
        $lineItems[] = [
            'price_data' => [
                'currency' => $cartContextModel->currencyIsoCode,
                'unit_amount_decimal' => $cartContextModel->amount,
                'product_data' => [
                    'name' => $cartContextModel->reference,
                ],
            ],
            'quantity' => 1,
        ];

        $checkoutParams = [
            'line_items' => $lineItems,
            'payment_intent_data' => $this->stripePaymentIntentService->buildPaymentIntentParams($cartContextModel, $separateAuthAndCapture, $orderModel, true),
            'mode' => Session::MODE_PAYMENT,
            'locale' => ($cartContextModel->language->iso_code ?: 'auto'),
            'metadata' => [
                'id_cart' => $cartContextModel->cartId,
            ],
            'success_url' => $successReturnUrl,
            'cancel_url' => $failReturnUrl,
        ];

        $stripeCustomerId = $this->stripeCustomerService->getOrCreateStripeCustomerId($cartContextModel, true);
        if ($stripeCustomerId) {
            $checkoutParams['customer'] = $stripeCustomerId;
        } else {
            $checkoutParams['customer_email'] = $cartContextModel->customer->email;
        }

        return $checkoutParams;
    }

    /**
     * @param int $cartId
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public function buildStripeCheckoutOptions($cartId): array
    {
        $idempotencyKey = StripeIdempotencyKey::getOrCreateIdempotencyKey($cartId);

        return [
            'idempotency_key' => $idempotencyKey->idempotency_key . uniqid() . self::SESSION_CREATE,
        ];
    }

    /**
     * @param array $stripeCheckoutParams
     * @param array $stripeCheckoutOptions
     *
     * @return Session|null
     */
    public function createStripeCheckoutSession($stripeCheckoutParams, $stripeCheckoutOptions)
    {
        $checkoutSession = null;
        try {
            $checkoutSession = $this->stripeClient->checkout->sessions->create($stripeCheckoutParams, $stripeCheckoutOptions);
        } catch (Exception $e) {
            StripeProcessLogger::logError('Create Stripe Checkout Session Error => ' . $e->getMessage() . ' - ' . $e->getTraceAsString(), 'StripeCheckoutSessionService', $stripeCheckoutParams['metadata']['id_cart']);
        }

        return $checkoutSession;
    }

    /**
     * @param CartContextModel $cartContextModel
     * @param bool $separateAuthAndCapture
     * @param string $successReturnUrl
     * @param string $failReturnUrl
     * @param OrderModel $orderModel
     *
     * @return Session|null
     */
    public function createCheckoutSession($cartContextModel, $separateAuthAndCapture, $successReturnUrl, $failReturnUrl, $orderModel = null)
    {
        $checkoutSession = null;
        try {
            $stripeCheckoutParams = $this->buildStripeCheckoutParams($cartContextModel, $separateAuthAndCapture, $successReturnUrl, $failReturnUrl, $orderModel);
            $stripeCheckoutOptions = $this->buildStripeCheckoutOptions($cartContextModel->cartId);
            $checkoutSession = $this->createStripeCheckoutSession($stripeCheckoutParams, $stripeCheckoutOptions);

            $idempotencyKey = new StripeIdempotencyKey();
            $idempotencyKey->updateIdempotencyKey($cartContextModel->cartId, $checkoutSession);
            StripePaymentIntent::getOrCreatePaymentIntent($checkoutSession);
        } catch (Exception $e) {
            StripeProcessLogger::logError('Create Checkout Session Error => ' . $e->getMessage() . ' - ' . $e->getTraceAsString(), 'StripeCheckoutSessionService', $cartContextModel->cartId);
        }
        $checkoutSessionAnonymized = $this->stripeAnonymize->anonymize($checkoutSession);
        StripeProcessLogger::logInfo('Create Checkout Session Ending ' . json_encode($checkoutSessionAnonymized), 'StripeCheckoutSessionService', $cartContextModel->cartId, $checkoutSession->id);

        return $checkoutSession;
    }

    public function getStripeCheckoutSession($sessionId): ?Session
    {
        $session = null;
        try {
            $session = $this->stripeClient->checkout->sessions->retrieve($sessionId);
        } catch (Exception $e) {
            StripeProcessLogger::logError('Get Stripe Checkout Session Error => ' . $e->getMessage() . ' - ' . $e->getTraceAsString(), 'StripeCheckoutSessionService', null, $sessionId);
        }
        $sessionAnonymized = $this->stripeAnonymize->anonymize($session);
        StripeProcessLogger::logInfo('Retrieve Session from Stripe ' . json_encode($sessionAnonymized), 'StripeCheckoutSessionService', null, $sessionId);

        return $session;
    }

    public function getStripeCheckoutSessionByPaymentIntentId($paymentIntentId): ?Session
    {
        try {
            $sessionList = $this->stripeClient->checkout->sessions->all([
                'payment_intent' => $paymentIntentId,
            ]);
        } catch (Exception $e) {
            StripeProcessLogger::logError('Get Stripe Checkout Session by payment Intent Error => ' . $e->getMessage() . ' - ' . $e->getTraceAsString(), 'StripeCheckoutSessionService', null, $paymentIntentId);

            return null;
        }
        $session = $sessionList->first();
        $sessionAnonymized = $this->stripeAnonymize->anonymize($session);
        StripeProcessLogger::logInfo('Retrieve Session from Stripe by payment intent ' . json_encode($sessionAnonymized), 'StripeCheckoutSessionService', null, $paymentIntentId);

        return $session;
    }
}
