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

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use StripeOfficial\Classes\StripeProcessLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

class StripeCustomerService
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
        $secretKey = $secretKey ?: Stripe_official::getSecretKey();
        $this->stripeClient = new StripeClient([
            'api_key' => $secretKey,
            'stripe_version' => Stripe_official::STRIPE_API_VERSION,
        ]);
    }

    /**
     * @param CartContextModel $cartContextModel
     *
     * @return string
     *
     * @throws ApiErrorException
     * @throws PrestaShopException
     */
    public function getOrCreateStripeCustomerId($cartContextModel, $update = true)
    {
        $customerModel = $cartContextModel->customerModel;
        $stripeAccount = $this->stripeClient->accounts->retrieve();
        $psStripeCustomer = new StripeCustomer();
        $psStripeCustomer = $psStripeCustomer->getCustomerById($customerModel->id, $stripeAccount->id);
        if (!$psStripeCustomer->id_customer && !$cartContextModel->customer->is_guest) {
            $stripeCustomerParams = $this->buildStripeCustomerParams($customerModel, $cartContextModel);
            $stripeCustomerOptions = $this->buildStripeCustomerOptions($cartContextModel->cartId);
            $stripeCustomer = $this->createStripeCustomer($stripeCustomerParams, $stripeCustomerOptions);

            $psStripeCustomer->id_customer = $customerModel->id;
            $psStripeCustomer->stripe_customer_key = $stripeCustomer->id;
            $psStripeCustomer->id_account = $stripeAccount->id;
            $psStripeCustomer->save();
        } elseif ($psStripeCustomer->id_customer && !$cartContextModel->customer->is_guest && $update) {
            $psStripeCustomer = $this->updateStripeCustomer($psStripeCustomer->stripe_customer_key, ['phone' => $cartContextModel->phone]);
        }

        return $psStripeCustomer->stripe_customer_key;
    }

    /**
     * @param CartContextModel $cartContextModel
     *
     * @return string
     *
     * @throws ApiErrorException
     * @throws PrestaShopException
     */
    public function getOrCreateStripeCustomerIdExpressCheckout($customerModel, $cartId)
    {
        $stripeAccount = $this->stripeClient->accounts->retrieve();
        $psStripeCustomer = new StripeCustomer();
        $psStripeCustomer = $psStripeCustomer->getCustomerById($customerModel->id, $stripeAccount->id);
        if (!$psStripeCustomer->id_customer) {
            $stripeCustomerParams = $this->buildStripeCustomerParamsExpressCheckout($customerModel);
            $stripeCustomerOptions = $this->buildStripeCustomerOptions($cartId);
            $stripeCustomer = $this->createStripeCustomer($stripeCustomerParams, $stripeCustomerOptions);

            if ($stripeCustomer) {
                $psStripeCustomer->id_customer = $customerModel->id;
                $psStripeCustomer->stripe_customer_key = $stripeCustomer['id'];
                $psStripeCustomer->id_account = $stripeAccount->id;
                $psStripeCustomer->save();
            }
        }

        return $psStripeCustomer->stripe_customer_key;
    }

    public function buildStripeCustomerParams($customerModel, $cartContextModel)
    {
        return [
            'email' => $customerModel->email,
            'description' => 'Customer created from Prestashop Stripe Official',
            'name' => $customerModel->name,
            'address' => $customerModel->address->__serialize(),
            'phone' => $cartContextModel->phone,
        ];
    }

    public function buildStripeCustomerParamsExpressCheckout($customerModel)
    {
        return [
            'email' => $customerModel->email,
            'description' => 'Customer created from Prestashop Stripe Official',
            'name' => $customerModel->name,
            'address' => $customerModel->address,
        ];
    }

    public function buildStripeCustomerOptions($cartId)
    {
        $idempotencyKey = StripeIdempotencyKey::getOrCreateIdempotencyKey($cartId);

        return [
            'idempotency_key' => $idempotencyKey->idempotency_key . uniqid() . StripeCustomer::CUSTOMER_CREATE,
        ];
    }

    public function createStripeCustomer($stripeCustomerParams, $stripeCustomerOptions)
    {
        $stripeCustomer = null;
        try {
            $stripeCustomer = $this->stripeClient->customers->create($stripeCustomerParams, $stripeCustomerOptions);
        } catch (ApiErrorException $e) {
            StripeProcessLogger::logError('Create Stripe Customer Error => ' . $e->getMessage() . ' - ' . $e->getTraceAsString(), 'StripeCustomerService');
        }

        return $stripeCustomer;
    }

    public function buildCustomerSessionData(string $customerId)
    {
        $save_type = Configuration::get(Stripe_official::ENABLE_SAVE_PAYMENT_METHOD);
        $usage = $save_type === Stripe_official::TYPE_SAVE_PAYMENT_METHOD_OFF_SESSION ? 'off_session' : 'on_session';

        $sessionData = [
            'customer' => $customerId,
            'components' => [
                'payment_element' => [
                    'enabled' => true,
                    'features' => [
                        'payment_method_redisplay' => 'enabled',
                        'payment_method_save' => 'enabled',
                        'payment_method_save_usage' => $usage,
                        'payment_method_remove' => 'enabled',
                    ],
                ],
            ]];

        return $sessionData;
    }

    public function createCustomerSession(array $customerSessionData)
    {
        return $this->stripeClient->customerSessions->create($customerSessionData);
    }

    public function updateStripeCustomer($stripeCustomerID, $stripeCustomerParams)
    {
        $stripeCustomer = null;
        try {
            $stripeCustomer = $this->stripeClient->customers->update($stripeCustomerID, $stripeCustomerParams);
        } catch (ApiErrorException $e) {
            StripeProcessLogger::logError('Update Stripe Customer Error => ' . $e->getMessage() . ' - ' . $e->getTraceAsString(), 'StripeCustomerService');
        }

        return $stripeCustomer;
    }
}
