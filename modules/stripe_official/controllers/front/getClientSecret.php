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

use Stripe\StripeClient;
use StripeOfficial\Classes\StripeProcessLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

class stripe_officialGetClientSecretModuleFrontController extends ModuleFrontController
{
    /**
     * @var StripeClient
     */
    private $stripeClient;

    /**
     * @param null $secretKey
     */
    public function __construct($secretKey = null)
    {
        parent::__construct();
        $secretKey = $secretKey ?: Stripe_official::getSecretKey();
        $this->stripeClient = new StripeClient([
            'api_key' => $secretKey,
            'stripe_version' => Stripe_official::STRIPE_API_VERSION,
        ]);
    }

    private function getClientSecret($paymentIntentId, $idempotencyKey)
    {
        if (empty($paymentIntentId) || empty($idempotencyKey)) {
            throw new Exception('Empty parameters: ' . json_encode(['intent' => $paymentIntentId, 'key' => $idempotencyKey]));
        }
        $idempotencyKeyInstance = new StripeIdempotencyKey();
        $idempotencyKeyInstance->getByIdempotencyKey($idempotencyKey);
        if (empty($idempotencyKeyInstance->id_cart)) {
            throw new Exception('Cart id not found by idempotency key: ' . $idempotencyKey . ' wit payment intent: ' . $paymentIntentId);
        }
        $currentCartId = $idempotencyKeyInstance->id_cart;
        $paymentIntent = $this->stripeClient->paymentIntents->retrieve($paymentIntentId);
        // Validate cart ownership
        $intentCartId = isset($paymentIntent->metadata->id_cart) ? (int) $paymentIntent->metadata->id_cart : 0;
        if ((int) $currentCartId !== $intentCartId) {
            throw new Exception('Unauthorized access to intent: current cart Id is: ' . $currentCartId . ' and the intent cart Id is: ' . $intentCartId);
        }

        return $paymentIntent->client_secret;
    }

    public function initContent()
    {
        parent::initContent();

        $paymentIntentId = Tools::getValue('paymentIntentId') ?: null;
        $idempotencyKey = Tools::getValue('idempotencyKey') ?: null;

        try {
            echo json_encode([
                'client_secret' => $this->getClientSecret($paymentIntentId, $idempotencyKey),
            ]);
            exit;
        } catch (Exception $e) {
            StripeProcessLogger::logInfo('Error Get Client Secret: ' . $e->getMessage(), 'getClientSecret');
            echo json_encode([
                'error' => 'Error Get Client Secret',
            ]);
            exit;
        }
    }
}
