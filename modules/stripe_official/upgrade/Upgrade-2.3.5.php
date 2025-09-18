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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @throws ApiErrorException
 */
function upgrade_module_2_3_5($module)
{
    $context = Context::getContext();

    /* Clean all webhooks from stripe module in Live Mode */
    if (Configuration::get(Stripe_official::KEY)) {
        $stripeClient = new StripeClient(Configuration::get(Stripe_official::KEY));
        $webhooksList = $stripeClient->webhookEndpoints->all();
        foreach ($webhooksList as $webhookEndpoint) {
            if ($webhookEndpoint->url == $context->link->getModuleLink('stripe_official', 'webhook', [], true, Configuration::get('PS_LANG_DEFAULT'), Configuration::get('PS_SHOP_DEFAULT'))) {
                $webhookEndpoint->delete();
            }
        }
    }
    /* Clean all webhooks from stripe module in Test Mode */
    if (Configuration::get(Stripe_official::TEST_KEY)) {
        $stripeClient = new StripeClient(Configuration::get(Stripe_official::TEST_KEY));
        $webhooksList = $stripeClient->webhookEndpoints->all();
        foreach ($webhooksList as $webhookEndpoint) {
            if ($webhookEndpoint->url == $context->link->getModuleLink('stripe_official', 'webhook', [], true, Configuration::get('PS_LANG_DEFAULT'), Configuration::get('PS_SHOP_DEFAULT'))) {
                $webhookEndpoint->delete();
            }
        }
    }
    /* Create new webhook in current Mode */
    StripeWebhook::create();
    /* Delete (if exist) table stripe_webhook from previous module version */
    $sql = 'DROP TABLE IF EXISTS ' . _DB_PREFIX_ . 'stripe_webhook;';
    if (!Db::getInstance()->execute($sql)) {
        return false;
    }

    return true;
}
