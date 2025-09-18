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

class RegisterKeysService
{
    /**
     * @var PrestashopTranslationService
     */
    protected $translationService;
    protected $module;

    const TEST_KEY_NEEDLE = '_test_';
    const LIVE_KEY_NEEDLE = '_live_';

    public function __construct($module, PrestashopTranslationService $translationService)
    {
        $this->module = $module;
        $this->translationService = $translationService;
    }

    public function registerApiKeys()
    {
        $shopGroupId = \Stripe_official::getShopGroupIdContext();
        $shopId = \Stripe_official::getShopIdContext();

        $mode = (int) \Tools::getValue(\Stripe_official::MODE);
        \Configuration::updateValue(\Stripe_official::MODE, $mode, false, $shopGroupId, $shopId);

        $apiSecretKeyConstant = \Stripe_official::getApiKeyConstantByModeAndType($mode, \Stripe_official::TYPE_SECRET);
        $apiPublishableKeyConstant = \Stripe_official::getApiKeyConstantByModeAndType($mode, \Stripe_official::TYPE_PUBLISHABLE);

        $secretKey = trim(\Tools::getValue($apiSecretKeyConstant));
        $publishableKey = trim(\Tools::getValue($apiPublishableKeyConstant));

        if (!$this->correctKeysProvided($mode, $secretKey, $publishableKey)) {
            return;
        }

        $stripeAccount = $this->module->checkApiConnection($secretKey);

        if ($stripeAccount) {
            \Configuration::updateValue($apiSecretKeyConstant, $secretKey, false, $shopGroupId, $shopId);
            \Configuration::updateValue($apiPublishableKeyConstant, $publishableKey, false, $shopGroupId, $shopId);
            \Configuration::updateValue(\Stripe_official::ACCOUNT_ID, $stripeAccount->id, false, $shopGroupId, $shopId);
            $this->updateAccountIdInDB($stripeAccount->id);
        }

        return true;
    }

    private function correctKeysProvided($mode, $secretKey, $publishableKey)
    {
        if (!$secretKey || !$publishableKey) {
            $this->module->errors[] = $this->translationService->translate('Client ID and Secret Key fields are mandatory');

            return false;
        }
        $needle = $this->getKeyNeedleByMode($mode);
        if (false === strpos($secretKey, $needle) || false === strpos($publishableKey, $needle)) {
            switch ($mode) {
                case \Stripe_official::MODE_LIVE:
                    $this->module->errors[] = $this->translationService->translate('Test API keys provided instead of live API keys.');
                    break;
                case \Stripe_official::MODE_TEST:
                    $this->module->errors[] = $this->translationService->translate('Live API keys provided instead of test API keys.');
                    break;
            }

            return false;
        }

        $needlesk = 'sk_';
        $needlepk = 'pk_';
        $needlerk = 'rk_';

        if (false === strpos($publishableKey, $needlepk)) {
            $this->module->errors[] = $this->translationService->translate('Secret key provided instead of Publishable key.');
        }

        if (false === strpos($secretKey, $needlesk) && false === strpos($secretKey, $needlerk)) {
            $this->module->errors[] = $this->translationService->translate('Publishable key provided instead of secret key.');

            return false;
        }

        if (!$this->module->checkApiConnection($secretKey)) {
            $this->module->errors[] = $this->translationService->translate('The Secret key provided is incorrect.');

            return false;
        }

        return true;
    }

    private function getKeyNeedleByMode($mode)
    {
        $needle = '';
        switch ($mode) {
            case \Stripe_official::MODE_LIVE:
                $needle = self::LIVE_KEY_NEEDLE;
                break;
            case \Stripe_official::MODE_TEST:
                $needle = self::TEST_KEY_NEEDLE;
                break;
        }

        return $needle;
    }

    private function updateAccountIdInDB($accountId)
    {
        if ($accountId) {
            $stripeAccountDetails = new \StripeAccountDetails();
            $stripeAccountDetails->getByIdStripeAccount($accountId);
            if (!$stripeAccountDetails->id_stripe_account) {
                $stripeAccountDetails->setIdStripeAccount($accountId);
                $stripeAccountDetails->save();
            }
        }
    }
}
