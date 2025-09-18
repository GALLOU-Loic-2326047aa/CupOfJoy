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

namespace StripeOfficial\Classes\services;

use Country;

if (!defined('_PS_VERSION_')) {
    exit;
}

class StripeDisplayHeaderService
{
    /**
     * @var \Stripe_official
     */
    private $module;

    private $context;

    private $orderPageNames = ['order', 'orderopc', 'cart', 'product'];

    public function __construct(\Stripe_official $module)
    {
        $this->module = $module;
        $this->context = $module->getContext();
    }

    public function setHeaders()
    {
        \Hook::exec('actionStripeDefineOrderPageNames', ['orderPageNames' => &$this->orderPageNames]);
        if (!$this->areHeadersNeeded()) {
            return;
        }

        $productId = isset($this->context->smarty->tpl_vars['product']->value->id_product) ? $this->context->smarty->tpl_vars['product']->value->id_product : null;
        if ($_GET['controller'] === 'product') {
            $productLazyArray = null;
            try {
                $productLazyArray = \StripeOfficial\Classes\factories\StripeProductLazyArrayFactory::createProductLazyArrayFromProductId($productId, $this->context);
            } catch (Throwable $e) {
                StripeProcessLogger::logError('Cannot get ProductLazyArray from ID: ' . $e->getMessage() . ' - ' . $e->getTraceAsString(), 'stripe_official');
            }
            \Hook::exec('displayProductActions', !empty($productLazyArray) ? ['product' => $productLazyArray] : ['product' => new \Product($productId)]);
        }
        if ($_GET['controller'] === 'cart') {
            \Hook::exec('displayExpressCheckout');
        }

        $cart = $this->context->cart;

        $address = new \Address($cart->id_address_invoice);
        $currency = new \Currency($cart->id_currency);
        $amount = $cart->getOrderTotal();
        $amount = \Tools::ps_round($amount, 2);
        $amount = \Stripe_official::isZeroDecimalCurrency($currency->iso_code) ? $amount : $amount * 100;

        // Merchant country (for payment request API)
        $merchantCountry = new \Country(\Configuration::get('PS_COUNTRY_DEFAULT'));

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $prestashop_version = '1.7';
            $firstname = str_replace('"', '\\"', $this->context->customer->firstname ?? '');
            $lastname = str_replace('"', '\\"', $this->context->customer->lastname ?? '');
            $stripe_fullname = $firstname . ' ' . $lastname;
        }

        $this->registerJs();

        // Javacript variables needed by Elements
        \Media::addJsDef([
            'stripe_pk' => \Stripe_official::getPublishableKey(),
            'stripe_merchant_country_code' => $merchantCountry->iso_code,

            'stripe_currency' => \Tools::strtolower($currency->iso_code),
            'stripe_amount' => \Tools::ps_round($amount, 2),

            'stripe_fullname' => $stripe_fullname,

            'stripe_address' => $address,
            'stripe_address_country_code' => \Country::getIsoById($address->id_country),

            'stripe_email' => $this->context->customer->email,

            'stripe_locale' => $this->context->language->iso_code,

            'stripe_create_elements' => $this->getUrl('createElements'),

            'stripe_css' => '{"base": {"iconColor": "#666ee8","color": "#31325f","fontWeight": 400,"fontFamily": "-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen-Sans, Ubuntu, Cantarell, Helvetica Neue, sans-serif","fontSmoothing": "antialiased","fontSize": "15px","::placeholder": { "color": "#aab7c4" },":-webkit-autofill": { "color": "#666ee8" }}}',

            'stripe_ps_version' => $prestashop_version,

            'stripe_module_dir' => \Media::getMediaPath(_PS_MODULE_DIR_ . $this->module->name),

            'stripe_message' => [
                'processing' => $this->module->getTranslationService()->hasNewTranslationSystem()
                    ? $this->module->getTranslationService()->translate('Processing…')
                    : $this->module->l('Processing…'),
                'accept_cgv' => $this->module->getTranslationService()->hasNewTranslationSystem()
                    ? $this->module->getTranslationService()->translate('Please accept the CGV')
                    : $this->module->l('Please accept the CGV'),
                'redirecting' => $this->module->getTranslationService()->hasNewTranslationSystem()
                    ? $this->module->getTranslationService()->translate('Redirecting…')
                    : $this->module->l('Redirecting…'),
            ],

            'stripe_payment_elements_enabled' => \Configuration::get(\Stripe_official::ENABLE_PAYMENT_ELEMENTS),
            'capture_method' => \Configuration::get(\Stripe_official::CATCHANDAUTHORIZE) ? 'manual' : 'automatic',
            'postcode' => $address->postcode,
            'stripe_theme' => \Configuration::get(\Stripe_official::THEME),
            'stripe_position' => \Configuration::get(\Stripe_official::POSITION),
            'stripe_layout' => \Configuration::get(\Stripe_official::LAYOUT),
            'use_new_ps_translation' => $this->module->getTranslationService()->hasNewTranslationSystem(),
            'express_checkout' => \Configuration::get(\Stripe_official::ENABLE_EXPRESS_CHECKOUT),
            'stripe_locations' => explode(', ', \Configuration::get(\Stripe_official::EXPRESS_CHECKOUT_LOCATIONS)),
            'apple_pay_button_theme' => \Configuration::get(\Stripe_official::APPLE_PAY_BUTTON_THEME),
            'apple_pay_button_type' => \Configuration::get(\Stripe_official::APPLE_PAY_BUTTON_TYPE),
            'google_pay_button_theme' => \Configuration::get(\Stripe_official::GOOGLE_PAY_BUTTON_THEME),
            'google_pay_button_type' => \Configuration::get(\Stripe_official::GOOGLE_PAY_BUTTON_TYPE),
            'pay_pal_button_theme' => \Configuration::get(\Stripe_official::PAY_PAL_BUTTON_THEME),
            'pay_pal_button_type' => \Configuration::get(\Stripe_official::PAY_PAL_BUTTON_TYPE),
            'stripe_order_flow' => \Configuration::get(\Stripe_official::ORDER_FLOW),
            'save_payment_method' => \Configuration::get(\Stripe_official::ENABLE_SAVE_PAYMENT_METHOD),
            'handle_order_action_url' => $this->getUrl('handleOrderAction'),
            'stripe_create_intent' => $this->getUrl('createIntent'),
            'stripe_order_confirm' => $this->getUrl('orderConfirmationReturn'),
            'stripe_calculate_shipping' => $this->getUrl('calculateShipping'),
            'stripe_log_js_error' => $this->getUrl('logJsError'),
            'get_client_secret' => $this->getUrl('getClientSecret'),
        ]);
    }

    private function getUrl(string $urlName): string
    {
        return $this->context->link->getModuleLink(
            $this->module->name,
            $urlName,
            [],
            true
        );
    }

    private function shouldIncludeExpressCheckout(): bool
    {
        $controller = \Dispatcher::getInstance()->getController();
        $expressCheckoutEnabled = \Configuration::get(\Stripe_official::ENABLE_EXPRESS_CHECKOUT) == 1;
        $expressCheckoutLocations = \Configuration::get(\Stripe_official::EXPRESS_CHECKOUT_LOCATIONS);

        if (
            $expressCheckoutEnabled
            && strpos($expressCheckoutLocations, $controller) !== false
        ) {
            return true;
        }

        return false;
    }

    private function areHeadersNeeded(): bool
    {
        $controller = \Dispatcher::getInstance()->getController();

        if (in_array($controller, ['cart', 'product']) && !$this->shouldIncludeExpressCheckout()) {
            return false;
        }

        if (!in_array($controller, $this->orderPageNames)) {
            return false;
        }

        if (!\Stripe_official::isWellConfigured() || !$this->module->active) {
            return false;
        }

        return true;
    }

    private function registerJs()
    {
        $controller = \Dispatcher::getInstance()->getController();

        $jsVersion = $this->getJsVersionString();

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->context->controller->registerJavascript(
                $this->module->name . '-stripe-v3',
                'https://js.stripe.com/v3/',
                [
                    'server' => 'remote',
                    'position' => 'head',
                ]
            );

            if ($controller === 'order' || $controller === 'orderopc') {
                if (_PS_USE_MINIFY_JS_) {
                    $this->context->controller->registerJavascript(
                        $this->module->name . '-payments',
                        'modules/' . $this->module->name . '/views/js/checkout.js',
                        ['version' => $jsVersion]
                    );
                } else {
                    $this->context->controller->registerJavascript(
                        $this->module->name . '-payments',
                        'modules/' . $this->module->name . '/views/js/extensions/checkout.js',
                        ['version' => $jsVersion]
                    );
                }
            }

            if ($this->shouldIncludeExpressCheckout()) {
                $this->context->controller->registerJavascript(
                    $this->module->name . '-express-payments',
                    'modules/' . $this->module->name . '/views/js/expressCheckout.js',
                    ['version' => $jsVersion]
                );
            }
        }
    }

    private function getJsVersionString()
    {
        $jsVersion = md5($this->module->version . \Stripe_official::getPublishableKey());
        if (\Configuration::get('PS_JS_THEME_CACHE')) {// oterwise will create javascript error
            $jsVersion = '';
        }

        return $jsVersion;
    }
}
