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

class BuildContextAction extends BaseAction
{
    public function execute()
    {
        $domain = \Tools::getShopDomain(true, true);
        if (\Tools::usingSecureMode()) {
            $domain = \Tools::getShopDomainSsl(true, true);
        }

        $this->module->getContext()->controller->addJS($this->module->getPathUri() . '/views/js/faq.js');
        $this->module->getContext()->controller->addJS($this->module->getPathUri() . '/views/js/back.js');
        $this->module->getContext()->controller->addJS($this->module->getPathUri() . '/views/js/PSTabs.js');
        $this->module->getContext()->controller->addJS($this->module->getPathUri() . '/views/js/handleNextAction.js');

        $this->module->getContext()->controller->addCSS($this->module->getPathUri() . '/views/css/admin.css');

        $keys_configured = false;
        if (\Stripe_official::isWellConfigured()) {
            $keys_configured = true;
        }

        $allOrderStatus = \OrderState::getOrderStates($this->module->getContext()->language->id);
        $statusSelected = [];
        $statusUnselected = [];

        $shopGroupId = $this->getShopGroupId();
        $shopId = $this->getShopId();

        if (\Configuration::get(\Stripe_official::CAPTURE_STATUS, null, $shopGroupId, $shopId) && \Configuration::get(\Stripe_official::CAPTURE_STATUS, null, $shopGroupId, $shopId) != '') {
            $capture_status = explode(',', \Configuration::get(\Stripe_official::CAPTURE_STATUS, null, $shopGroupId, $shopId));
            foreach ($allOrderStatus as $status) {
                if (in_array($status['id_order_state'], $capture_status)) {
                    $statusSelected[] = $status;
                } else {
                    $statusUnselected[] = $status;
                }
            }
        } else {
            $statusUnselected = $allOrderStatus;
        }

        $orderStatus = [];
        $orderStatus['selected'] = $statusSelected;
        $orderStatus['unselected'] = $statusUnselected;
        $includePSAccount = $this->module->isCloudSyncReady();

        $this->module->getContext()->smarty->assign([
            'logo' => $domain . __PS_BASE_URI__ . basename(_PS_MODULE_DIR_) . '/' . $this->module->name . '/views/img/Stripe_logo.png',
            'new_base_dir', $this->module->getPathUri(),
            'keys_configured' => $keys_configured,
            'link' => new \Link(),
            'catchandauthorize' => \Configuration::get(\Stripe_official::CATCHANDAUTHORIZE, null, $shopGroupId, $shopId),
            'orderStatus' => $orderStatus,
            'orderStatusSelected' => \Configuration::get(\Stripe_official::CAPTURE_STATUS, null, $shopGroupId, $shopId),
            'allOrderStatus' => $allOrderStatus,
            'captureExpire' => \Configuration::get(\Stripe_official::CAPTURE_EXPIRE, null, $shopGroupId, $shopId),
            'payment_methods' => \Stripe_official::$paymentMethods,
            'language_iso_code' => $this->module->getContext()->language->iso_code,
            'stripe_payments_url' => 'https://dashboard.stripe.com/settings/payments',
            'include_prestashop_account' => $includePSAccount,
        ]);

        $this->displaySomething();
        $this->assignSmartyVars();

        if (count($this->module->warning)) {
            $this->module->getContext()->smarty->assign('warnings', $this->module->warning);
        }
        if (!empty($this->module->success) && !count($this->module->errors)) {
            $this->module->getContext()->smarty->assign('success', $this->module->success);
        }
        if (count($this->module->errors)) {
            $this->module->getContext()->smarty->assign('errors', $this->module->errors);
        }

        return true;
    }

    /**
     * Display Form
     */
    protected function assignSmartyVars()
    {
        $shopGroupId = $this->getShopGroupId();
        $shopId = $this->getShopId();

        $this->module->getContext()->smarty->assign([
            'stripe_mode' => \Configuration::get(\Stripe_official::MODE, null, $shopGroupId, $shopId),
            'stripe_key' => \Configuration::get(\Stripe_official::KEY, null, $shopGroupId, $shopId),
            'stripe_publishable' => \Configuration::get(\Stripe_official::PUBLISHABLE, null, $shopGroupId, $shopId),
            'stripe_test_publishable' => \Configuration::get(\Stripe_official::TEST_PUBLISHABLE, null, $shopGroupId, $shopId),
            'stripe_test_key' => \Configuration::get(\Stripe_official::TEST_KEY, null, $shopGroupId, $shopId),
            'postcode' => \Configuration::get(\Stripe_official::POSTCODE, null, $shopGroupId, $shopId),
            'cardholdername' => \Configuration::get(\Stripe_official::CARDHOLDERNAME, null, $shopGroupId, $shopId),
            'reinsurance' => \Configuration::get(\Stripe_official::REINSURANCE, null, $shopGroupId, $shopId),
            'visa' => \Configuration::get(\Stripe_official::VISA, null, $shopGroupId, $shopId),
            'mastercard' => \Configuration::get(\Stripe_official::MASTERCARD, null, $shopGroupId, $shopId),
            'american_express' => \Configuration::get(\Stripe_official::AMERICAN_EXPRESS), null, $shopGroupId, $shopId,
            'cb' => \Configuration::get(\Stripe_official::CB, null, $shopGroupId, $shopId),
            'diners_club' => \Configuration::get(\Stripe_official::DINERS_CLUB, null, $shopGroupId, $shopId),
            'union_pay' => \Configuration::get(\Stripe_official::UNION_PAY, null, $shopGroupId, $shopId),
            'jcb' => \Configuration::get(\Stripe_official::JCB, null, $shopGroupId, $shopId),
            'discovers' => \Configuration::get(\Stripe_official::DISCOVERS, null, $shopGroupId, $shopId),
            'ideal' => \Configuration::get(\Stripe_official::ENABLE_IDEAL, null, $shopGroupId, $shopId),
            'giropay' => \Configuration::get(\Stripe_official::ENABLE_GIROPAY, null, $shopGroupId, $shopId),
            'bancontact' => \Configuration::get(\Stripe_official::ENABLE_BANCONTACT, null, $shopGroupId, $shopId),
            'fpx' => \Configuration::get(\Stripe_official::ENABLE_FPX, null, $shopGroupId, $shopId),
            'eps' => \Configuration::get(\Stripe_official::ENABLE_EPS, null, $shopGroupId, $shopId),
            'p24' => \Configuration::get(\Stripe_official::ENABLE_P24, null, $shopGroupId, $shopId),
            'sepa_debit' => \Configuration::get(\Stripe_official::ENABLE_SEPA), null, $shopGroupId, $shopId,
            'alipay' => \Configuration::get(\Stripe_official::ENABLE_ALIPAY, null, $shopGroupId, $shopId),
            'oxxo' => \Configuration::get(\Stripe_official::ENABLE_OXXO, null, $shopGroupId, $shopId),
            'applepay_googlepay' => \Configuration::get(\Stripe_official::ENABLE_APPLEPAY_GOOGLEPAY, null, $shopGroupId, $shopId),
            'url_webhhoks' => \Stripe_official::getWebhookUrl(),
            'payment_element' => \Configuration::get(\Stripe_official::ENABLE_PAYMENT_ELEMENTS, null, $shopGroupId, $shopId),
            'klarna' => \Configuration::get(\Stripe_official::ENABLE_KLARNA, null, $shopGroupId, $shopId),
            'afterpay_clearpay' => \Configuration::get(\Stripe_official::ENABLE_AFTERPAY, null, $shopGroupId, $shopId),
            'affirm' => \Configuration::get(\Stripe_official::ENABLE_AFFIRM, null, $shopGroupId, $shopId),
            'link' => \Configuration::get(\Stripe_official::ENABLE_LINK, null, $shopGroupId, $shopId),
            'stripe_theme' => \Configuration::get(\Stripe_official::THEME, null, $shopGroupId, $shopId),
            'stripe_position' => \Configuration::get(\Stripe_official::POSITION, null, $shopGroupId, $shopId),
            'stripe_layout' => \Configuration::get(\Stripe_official::LAYOUT, null, $shopGroupId, $shopId),
            'use_new_ps_translation' => $this->translationService->hasNewTranslationSystem(),
            'express_checkout' => \Configuration::get(\Stripe_official::ENABLE_EXPRESS_CHECKOUT, null, $shopGroupId, $shopId),
            'stripe_locations' => explode(', ', \Configuration::get(\Stripe_official::EXPRESS_CHECKOUT_LOCATIONS, null, $shopGroupId, $shopId)),
            'apple_pay_button_theme' => \Configuration::get(\Stripe_official::APPLE_PAY_BUTTON_THEME, null, $shopGroupId, $shopId),
            'apple_pay_button_type' => \Configuration::get(\Stripe_official::APPLE_PAY_BUTTON_TYPE, null, $shopGroupId, $shopId),
            'google_pay_button_theme' => \Configuration::get(\Stripe_official::GOOGLE_PAY_BUTTON_THEME, null, $shopGroupId, $shopId),
            'google_pay_button_type' => \Configuration::get(\Stripe_official::GOOGLE_PAY_BUTTON_TYPE, null, $shopGroupId, $shopId),
            'pay_pal_button_theme' => \Configuration::get(\Stripe_official::PAY_PAL_BUTTON_THEME, null, $shopGroupId, $shopId),
            'pay_pal_button_type' => \Configuration::get(\Stripe_official::PAY_PAL_BUTTON_TYPE, null, $shopGroupId, $shopId),
            'stripe_order_flow' => \Configuration::get(\Stripe_official::ORDER_FLOW, null, $shopGroupId, $shopId),
            'save_payment_method' => \Configuration::get(\Stripe_official::ENABLE_SAVE_PAYMENT_METHOD, null, $shopGroupId, $shopId),
        ]);
    }

    /*
     ** @Method: displaySomething
     ** @description: just display something (it's something)
     **
     ** @arg: (none)
     ** @return: (none)
     */
    public function displaySomething()
    {
        $return_url = '';

        if (\Configuration::get('PS_SSL_ENABLED')) {
            $domain = \Tools::getShopDomainSsl(true);
        } else {
            $domain = \Tools::getShopDomain(true);
        }

        if (isset($_SERVER['REQUEST_URI'])) {
            $return_url = urlencode($domain . $_SERVER['REQUEST_URI']);
        }

        $this->module->getContext()->smarty->assign('return_url', $return_url);
    }
}
