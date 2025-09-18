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
use Stripe\WebhookEndpoint;
use StripeOfficial\Classes\StripeProcessLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

class StripeWebhook
{
    const MAX_WEBHOOKS_NR = 16;
    /**
     * @var StripeClient
     */
    private $stripeClient;

    public function __construct($secretKey = null)
    {
        $secretKey = $secretKey ?: Stripe_official::getSecretKey();
        $this->stripeClient = new StripeClient([
            'api_key' => $secretKey,
            'stripe_version' => Stripe_official::STRIPE_API_VERSION,
        ]);
    }

    public function createStripeWebhook()
    {
        $webhookEndpoint = null;
        try {
            $webhookEndpoint = $this->stripeClient->webhookEndpoints->create([
                'url' => Stripe_official::getWebhookUrl(),
                'enabled_events' => Stripe_official::$webhook_events,
            ]);
        } catch (Exception $e) {
            StripeProcessLogger::logError('Create Stripe Webhook Error => ' . $e->getMessage() . ' - ' . $e->getTraceAsString(), 'StripeWebhook');
        }

        return $webhookEndpoint;
    }

    public function updateStripeWebhook($webhookId)
    {
        $webhookEndpoint = null;
        try {
            $webhookEndpoint = $this->stripeClient->webhookEndpoints->retrieve($webhookId);
            $webhookUpdateData = $this->getWebhookUpdateData($webhookEndpoint);
            if (!empty($webhookUpdateData)) {
                $this->stripeClient->webhookEndpoints->update($webhookId, $webhookUpdateData);
            }
        } catch (ApiErrorException $e) {
            StripeProcessLogger::logError('Update Stripe Webhook Error => ' . $e->getMessage() . ' - ' . $e->getTraceAsString(), 'StripeWebhook');
        }

        return $webhookEndpoint;
    }

    public function deleteStripeWebhook($webhookId)
    {
        $deleted = false;
        try {
            $this->stripeClient->webhookEndpoints->delete($webhookId);
            $deleted = true;
        } catch (ApiErrorException $e) {
            StripeProcessLogger::logError('Delete Stripe Webhook Error => ' . $e->getMessage() . ' - ' . $e->getTraceAsString(), 'StripeWebhook');
        }

        return $deleted;
    }

    public function getWebhookList()
    {
        $webhookEndpointsList = null;
        try {
            $webhookEndpointsList = $this->stripeClient->webhookEndpoints->all();
        } catch (Exception $e) {
            StripeProcessLogger::logError('Get Webhook List Error => ' . $e->getMessage() . ' - ' . $e->getTraceAsString(), 'StripeWebhook');
        }

        return $webhookEndpointsList;
    }

    public function countWebhooksList()
    {
        $list = $this->getWebhookList();

        return (isset($list->data) && $list->data) ? count($list->data) : 0;
    }

    public function webhookCanBeRegistered()
    {
        $webhooksList = $this->getWebhookList();
        $webhookUrl = Stripe_official::getWebhookUrl();

        $webhookExists = false;
        foreach ($webhooksList->data as $webhook) {
            if ($webhook->url === $webhookUrl) {
                $webhookExists = true;
                break;
            }
        }

        if ($this->countWebhooksList() >= self::MAX_WEBHOOKS_NR && !$webhookExists) {
            return false;
        }

        return true;
    }

    public function getWebhookUpdateData($webhookEndpoint)
    {
        $webhookUrlExpected = Stripe_official::getWebhookUrl();
        $webhookUpdateData = [];

        /* Check if webhook configuration is wrong */
        if ($webhookEndpoint->url !== $webhookUrlExpected) {
            $webhookUpdateData['url'] = $webhookUrlExpected;
        }
        /* Check if webhook events are wrong */
        $eventError = false;
        if (count($webhookEndpoint->enabled_events) !== count(Stripe_official::$webhook_events)) {
            $eventError = true;
        }
        foreach (Stripe_official::$webhook_events as $webhookEvent) {
            if (!in_array($webhookEvent, $webhookEndpoint->enabled_events)) {
                $eventError = true;
                break;
            }
        }
        if ($eventError) {
            $webhookUpdateData['enabled_events'] = Stripe_official::$webhook_events;
        }

        return $webhookUpdateData;
    }

    private function updateWebhookDataInDB($webhook)
    {
        $shopGroupId = Stripe_official::getShopGroupIdContext();
        $shopId = Stripe_official::getShopIdContext();
        $accountId = Configuration::get(Stripe_official::ACCOUNT_ID, null, $shopGroupId, $shopId);
        $webhookId = '';
        $webhookSecret = '';
        $stripeAccountDetails = new StripeAccountDetails();
        $stripeAccountDetails->getByIdStripeAccount($accountId);
        if ($webhook instanceof WebhookEndpoint) {
            $webhookId = $webhook->id;
            $webhookSecret = $webhook->secret;

            $stripeAccountDetails->setIdWebhook($webhook->id);
            $stripeAccountDetails->setWebhookSecret($webhook->secret);
            $stripeAccountDetails->save();
        }

        Configuration::updateValue(Stripe_official::WEBHOOK_SIGNATURE, $webhookSecret, false, $shopGroupId, $shopId);
        Configuration::updateValue(Stripe_official::WEBHOOK_ID, $webhookId, false, $shopGroupId, $shopId);
    }

    public function registerWebhook()
    {
        $shopGroupId = Stripe_official::getShopGroupIdContext();
        $shopId = Stripe_official::getShopIdContext();
        $webhookId = Configuration::get(Stripe_official::WEBHOOK_ID, null, $shopGroupId, $shopId);
        $webhookEndpoint = null;

        $existsWebhookEndpoint = $this->checkIfExistsWebhookEndpoints($webhookId);

        if ($existsWebhookEndpoint) {
            $webhookEndpoint = $this->updateStripeWebhook($webhookId);
        }

        if (!$webhookEndpoint && $this->webhookCanBeRegistered()) {
            $this->deleteStripeWebhookWithSameUrlFromList();

            $webhookEndpoint = $this->createStripeWebhook();
            $this->updateWebhookDataInDB($webhookEndpoint);
        }
    }

    private function deleteStripeWebhookWithSameUrlFromList()
    {
        $webhooksList = $this->getWebhookList();
        foreach ($webhooksList as $webhookEndpoint) {
            if ($webhookEndpoint->url === Stripe_official::getWebhookUrl()) {
                $webhookEndpoint->delete();
            }
        }
    }

    private function retrieveWebhookEndpoints($webhookId)
    {
        $webhookEndpoint = null;
        try {
            $webhookEndpoint = $this->stripeClient->webhookEndpoints->retrieve($webhookId);
        } catch (ApiErrorException $e) {
            StripeProcessLogger::logError('Update Stripe Webhook Error => ' . $e->getMessage() . ' - ' . $e->getTraceAsString(), 'StripeWebhook');
        }

        return $webhookEndpoint;
    }

    private function checkIfExistsWebhookEndpoints($webhookId)
    {
        $shopGroupId = Stripe_official::getShopGroupIdContext();
        $shopId = Stripe_official::getShopIdContext();
        $accountId = Configuration::get(Stripe_official::ACCOUNT_ID, null, $shopGroupId, $shopId);
        $existsWebhookEndpoint = null;
        if ($webhookId) {
            $existsWebhookEndpoint = $this->retrieveWebhookEndpoints($webhookId);
        }

        if (!$existsWebhookEndpoint) {
            $stripeAccountDetails = new StripeAccountDetails();
            $stripeAccountDetails->getByIdStripeAccount($accountId);
            if ($stripeAccountDetails->id_stripe_account) {
                $webhookId = $stripeAccountDetails->id_webhook;
                $webhookSignature = $stripeAccountDetails->webhook_secret;

                if ($webhookId) {
                    $existsWebhookEndpoint = $this->retrieveWebhookEndpoints($webhookId);
                }

                if ($existsWebhookEndpoint) {
                    Configuration::updateValue(Stripe_official::WEBHOOK_SIGNATURE, $webhookSignature, false, $shopGroupId, $shopId);
                    Configuration::updateValue(Stripe_official::WEBHOOK_ID, $webhookId, false, $shopGroupId, $shopId);
                }
            }
        }

        return $existsWebhookEndpoint;
    }
}
