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

use Stripe_officialClasslib\Actions\DefaultActions;
use StripeOfficial\Classes\services\Registration\RegisterApplePayDomainService;
use StripeOfficial\Classes\services\Registration\RegisterCatchAndAuthorizeService;
use StripeOfficial\Classes\services\Registration\RegisterKeysService;
use StripeOfficial\Classes\services\Registration\RegisterOtherConfigurationsService;
use StripeOfficial\Classes\services\Registration\RegisterWebhookSignatureService;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ConfigurationActions extends DefaultActions
{
    const MODULE_CLASS = 'Modules.Stripeofficial.ConfigurationActions';

    protected $context;
    protected $module;
    /**
     * @var StripeOfficial\Classes\services\PrestashopTranslationService
     */
    protected $translationService;

    /*
        Input : 'source', 'response', 'context', 'module'
        Output : 'token', 'id_payment_intent', 'status', 'chargeId', 'amount'
     */
    public function registerKeys()
    {
        $this->module = $this->conveyor->module;
        $this->translationService = new StripeOfficial\Classes\services\PrestashopTranslationService($this->module, self::MODULE_CLASS);

        $registerKeysService = new RegisterKeysService($this->module, $this->translationService);

        return $registerKeysService->registerApiKeys();
    }

    /*
       Input : 'source', 'response', 'context', 'module'
       Output : 'token', 'id_payment_intent', 'status', 'chargeId', 'amount'
    */
    public function registerCatchAndAuthorize()
    {
        $registerCatchAndAuthorizeService = new RegisterCatchAndAuthorizeService($this->module, $this->translationService);

        return $registerCatchAndAuthorizeService->registerCatchAndAuthorize();
    }

    /*
        Input : 'status', 'id_payment_intent', 'token', 'paymentIntent'
        Output : 'source', 'secure_key', 'result'
    */
    public function registerOtherConfigurations()
    {
        $registerOtherConfigurationsService = new RegisterOtherConfigurationsService($this->module, $this->translationService);

        return $registerOtherConfigurationsService->registerOtherConfigurations();
    }

    public function registerApplePayDomain()
    {
        $registerApplePayDomainService = new RegisterApplePayDomainService($this->module, $this->translationService);

        return $registerApplePayDomainService->registerApplePayDomain();
    }

    public function registerWebhookSignature()
    {
        $registerWebhookSignature = new RegisterWebhookSignatureService($this->module, $this->translationService);

        return $registerWebhookSignature->registerWebhookSignature();
    }
}
