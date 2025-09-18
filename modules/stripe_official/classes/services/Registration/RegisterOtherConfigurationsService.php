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

namespace StripeOfficial\Classes\services\Registration;

use StripeOfficial\Classes\services\PrestashopTranslationService;

if (!defined('_PS_VERSION_')) {
    exit;
}

class RegisterOtherConfigurationsService
{
    /**
     * @var PrestashopTranslationService
     */
    protected $translationService;
    protected $module;

    public function __construct($module, PrestashopTranslationService $translationService)
    {
        $this->module = $module;
        $this->translationService = $translationService;
    }

    public function registerOtherConfigurations()
    {
        $shopGroupId = \Stripe_official::getShopGroupIdContext();
        $shopId = \Stripe_official::getShopIdContext();

        $neverCollectPostcode = 0;
        if (gettype(\Tools::getValue('stripe_locations')) === 'boolean') {
            $locations = \Tools::getValue('stripe_locations');
        } else {
            $locations = implode(', ', \Tools::getValue('stripe_locations') === 0 ? null : \Tools::getValue('stripe_locations'));
        }

        \Configuration::updateValue(\Stripe_official::POSTCODE, $neverCollectPostcode, false, $shopGroupId, $shopId);
        \Configuration::updateValue(\Stripe_official::ENABLE_PAYMENT_ELEMENTS, (int) \Tools::getValue('payment_element'), false, $shopGroupId, $shopId);
        \Configuration::updateValue(\Stripe_official::THEME, \Tools::getValue('stripe_theme'), false, $shopGroupId, $shopId);
        \Configuration::updateValue(\Stripe_official::POSITION, \Tools::getValue('stripe_position'), false, $shopGroupId, $shopId);
        \Configuration::updateValue(\Stripe_official::LAYOUT, \Tools::getValue('stripe_layout'), false, $shopGroupId, $shopId);
        \Configuration::updateValue(\Stripe_official::ENABLE_EXPRESS_CHECKOUT, (int) \Tools::getValue('express_checkout'), false, $shopGroupId, $shopId);
        \Configuration::updateValue(\Stripe_official::EXPRESS_CHECKOUT_LOCATIONS, $locations, false, $shopGroupId, $shopId);
        \Configuration::updateValue(\Stripe_official::APPLE_PAY_BUTTON_THEME, \Tools::getValue('apple_pay_button_theme'), false, $shopGroupId, $shopId);
        \Configuration::updateValue(\Stripe_official::APPLE_PAY_BUTTON_TYPE, \Tools::getValue('apple_pay_button_type'), false, $shopGroupId, $shopId);
        \Configuration::updateValue(\Stripe_official::GOOGLE_PAY_BUTTON_THEME, \Tools::getValue('google_pay_button_theme'), false, $shopGroupId, $shopId);
        \Configuration::updateValue(\Stripe_official::GOOGLE_PAY_BUTTON_TYPE, \Tools::getValue('google_pay_button_type'), false, $shopGroupId, $shopId);
        \Configuration::updateValue(\Stripe_official::PAY_PAL_BUTTON_THEME, \Tools::getValue('pay_pal_button_theme'), false, $shopGroupId, $shopId);
        \Configuration::updateValue(\Stripe_official::PAY_PAL_BUTTON_TYPE, \Tools::getValue('pay_pal_button_type'), false, $shopGroupId, $shopId);
        \Configuration::updateValue(\Stripe_official::ORDER_FLOW, (int) \Tools::getValue('stripe_order_flow'), false, $shopGroupId, $shopId);
        \Configuration::updateValue(\Stripe_official::ENABLE_SAVE_PAYMENT_METHOD, \Tools::getValue('save_payment_method'), false, $shopGroupId, $shopId);

        if (!count($this->module->errors)) {
            $this->module->success = $this->translationService->translate('Settings updated successfully.');
        }

        return true;
    }
}
