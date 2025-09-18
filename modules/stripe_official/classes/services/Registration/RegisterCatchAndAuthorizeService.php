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

class RegisterCatchAndAuthorizeService
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

    public function registerCatchAndAuthorize()
    {
        $shopGroupId = \Stripe_official::getShopGroupIdContext();
        $shopId = \Stripe_official::getShopIdContext();

        $separateAuthAndCapture = \Tools::getValue('catchandauthorize') === 'on' ? 1 : 0;
        $catchStatus = \Tools::getValue('order_status_select') ?: null;
        $expiredStatus = (int) \Tools::getValue('capture_expired') ?: 0;

        \Configuration::updateValue(\Stripe_official::CATCHANDAUTHORIZE, $separateAuthAndCapture, false, $shopGroupId, $shopId);
        \Configuration::updateValue(\Stripe_official::CAPTURE_STATUS, $catchStatus, false, $shopGroupId, $shopId);
        \Configuration::updateValue(\Stripe_official::CAPTURE_EXPIRE, $expiredStatus, false, $shopGroupId, $shopId);
        if ($separateAuthAndCapture && (!$catchStatus || !$expiredStatus)) {
            $this->module->errors[] = $this->translationService->translate('Enable separate authorization and capture.');
        }

        return true;
    }
}
