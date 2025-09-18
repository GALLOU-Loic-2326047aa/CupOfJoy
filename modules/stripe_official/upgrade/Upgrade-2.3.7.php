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

function upgrade_module_2_3_7($module)
{
    $shopGroupId = Stripe_official::getShopGroupIdContext();
    $shopId = Stripe_official::getShopIdContext();

    if (Configuration::get('STRIPE_PARTIAL_REFUND_STATE', null, $shopGroupId, $shopId)
        && $orderState = new OrderState(Configuration::get('STRIPE_PARTIAL_REFUND_STATE', null, $shopGroupId, $shopId))) {
        if (!Configuration::deleteByName('STRIPE_PARTIAL_REFUND_STATE') && !$orderState->delete()) {
            return false;
        }
    }

    $os_sofort_waiting = Configuration::get(Stripe_official::OS_SOFORT_WAITING) ?: Configuration::get(Stripe_official::OS_SOFORT_WAITING, null, $shopGroupId, $shopId);
    if ($os_sofort_waiting) {
        Configuration::deleteByName(Stripe_official::OS_SOFORT_WAITING);
        Configuration::updateValue(Stripe_official::OS_SOFORT_WAITING, $os_sofort_waiting);
    }
    $capture_waiting = Configuration::get(Stripe_official::CAPTURE_WAITING) ?: Configuration::get(Stripe_official::CAPTURE_WAITING, null, $shopGroupId, $shopId);
    if ($capture_waiting) {
        Configuration::deleteByName(Stripe_official::CAPTURE_WAITING);
        Configuration::updateValue(Stripe_official::CAPTURE_WAITING, $capture_waiting);
    }
    $sepa_waiting = Configuration::get(Stripe_official::SEPA_WAITING) ?: Configuration::get(Stripe_official::SEPA_WAITING, null, $shopGroupId, $shopId);
    if ($sepa_waiting) {
        Configuration::deleteByName(Stripe_official::SEPA_WAITING);
        $orderState = new OrderState($sepa_waiting);
        $orderState->logable = false;
        $orderState->save();
        Configuration::updateValue(Stripe_official::SEPA_WAITING, $orderState->id);
    }
    $sepa_dispute = Configuration::get(Stripe_official::SEPA_DISPUTE) ?: Configuration::get(Stripe_official::SEPA_DISPUTE, null, $shopGroupId, $shopId);
    if ($sepa_dispute) {
        Configuration::deleteByName(Stripe_official::SEPA_DISPUTE);
        Configuration::updateValue(Stripe_official::SEPA_DISPUTE, $sepa_dispute);
    }
    $oxxo_waiting = Configuration::get(Stripe_official::OXXO_WAITING) ?: Configuration::get(Stripe_official::OXXO_WAITING, null, $shopGroupId, $shopId);
    if ($oxxo_waiting) {
        Configuration::deleteByName(Stripe_official::OXXO_WAITING);
        $orderState = new OrderState($oxxo_waiting);
        $orderState->logable = false;
        $orderState->save();
        Configuration::updateValue(Stripe_official::OXXO_WAITING, $orderState->id);
    }

    $module->cleanModuleCache();

    return true;
}
