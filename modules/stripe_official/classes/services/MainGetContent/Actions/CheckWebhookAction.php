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

namespace StripeOfficial\Classes\services\MainGetContent\Actions;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CheckWebhookAction extends BaseAction
{
    public function execute()
    {
        /* Check if webhook limit has been reached */
        if (\Stripe_official::isWellConfigured()) {
            $stripeWebhook = new \StripeWebhook();
            if (!$stripeWebhook->webhookCanBeRegistered()) {
                $this->module->warning[] =
                    $this->translationService->translate('You reached the limit of 16 webhook endpoints registered in your Dashboard Stripe for this account. Please remove one of them if you want to register this domain.');
            }
        }

        /* Check if webhook_id has been defined */
        $webhookId = \Configuration::get(\Stripe_official::WEBHOOK_ID, null, $this->getShopGroupId(), $this->getShopId());
        if (!$webhookId) {
            $this->module->errors[] = $this->translationService->translate('Webhook configuration cannot be found in PrestaShop, click on save button to fix issue. A new webhook will be created on Stripe, then saved in PrestaShop.');
        }
        if ($webhookId && \Stripe_official::isWellConfigured()) {
            /* Check if webhook access is write */
            try {
                $webhookEndpoint = \Stripe\WebhookEndpoint::retrieve($webhookId);
                $stripeWebhook = new \StripeWebhook();
                $updateWebhookData = $stripeWebhook->getWebhookUpdateData($webhookEndpoint);

                if (!empty($updateWebhookData) && isset($updateWebhookData['url'])) {
                    $this->module->errors[] = $this->translationService->translate('Webhook URL configuration is wrong, click on save button to fix issue. Webhook configuration will be corrected.') . ' | ' .
                        $this->translationService->translate('Current webhook URL : ') . $webhookEndpoint->url . ' | ' .
                        $this->translationService->translate('Expected webhook URL : ') . \Stripe_official::getWebhookUrl();
                }
                if (!empty($updateWebhookData) && isset($updateWebhookData['enabled_events'])) {
                    $this->module->errors[] =
                        $this->translationService->translate('Webhook events configuration are wrong, click on save button to fix issue. Webhook configuration will be corrected.') . ' | ' .
                        $this->translationService->translate('Current webhook events : ') . implode(' / ', $webhookEndpoint->enabled_events) . ' | ' .
                        $this->translationService->translate('Expected webhook events : ') . implode(' / ', \Stripe_official::$webhook_events);
                }
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $this->module->errors[] = $this->translationService->translate('Webhook configuration cannot be accessed, click on save button to fix issue. A new webhook will be created on Stripe.');
            }
        }

        return true;
    }
}
