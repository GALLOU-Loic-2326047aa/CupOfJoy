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

use Stripe\Stripe;
use StripeOfficial\Classes\services\PrestashopTranslationService;

if (!defined('_PS_VERSION_')) {
    exit;
}

class RegisterApplePayDomainService
{
    protected $module;
    /**
     * @var PrestashopTranslationService
     */
    protected $translationService;

    public function __construct($module, PrestashopTranslationService $translationService)
    {
        $this->module = $module;
        $this->translationService = $translationService;
    }

    public function registerApplePayDomain()
    {
        $shopGroupId = \Stripe_official::getShopGroupIdContext();
        $shopId = \Stripe_official::getShopIdContext();
        $mode = (int) \Configuration::get(\Stripe_official::MODE, null, $shopGroupId, $shopId);
        $secretKey = \Stripe_official::getSecretKey($shopGroupId, $shopId);
        if ($mode === \Stripe_official::MODE_LIVE && $secretKey) {
            $this->addAppleDomainAssociation($secretKey);
        }

        return true;
    }

    public function addAppleDomainAssociation($secret_key)
    {
        if (!is_dir(_PS_ROOT_DIR_ . '/.well-known')) {
            if (!mkdir(_PS_ROOT_DIR_ . '/.well-known')) {
                $this->module->warning[] = $this->translationService->translate('Settings updated successfully.');

                return false;
            }
        }

        $domain_file = _PS_ROOT_DIR_ . '/.well-known/apple-developer-merchantid-domain-association';
        if (!file_exists($domain_file)) {
            if (!$this->copyAppleDomainFile()) {
                $this->module->warning[] = $this->translationService->translate('Your host does not authorize us to add your domain to use ApplePay. To add your domain manually please follow the subject "Add my domain ApplePay manually from my dashboard" which is located in the tab F.A.Q of the module.');
            } else {
                try {
                    Stripe::setApiKey($secret_key);
                    \Stripe\ApplePayDomain::create([
                        'domain_name' => $this->module->getContext()->shop->domain,
                    ]);

                    $curl = curl_init(\Tools::getShopDomainSsl(true, true) . '/.well-known/apple-developer-merchantid-domain-association');
                    curl_setopt($curl, CURLOPT_FAILONERROR, true);
                    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
                    $result = curl_exec($curl);
                    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    curl_close($curl);

                    if ($httpcode != 200 || !$result) {
                        $this->module->warning[] = $this->translationService->translate('The configurations has been saved, however your host does not authorize us to add your domain to use ApplePay. To add your domain manually please follow the subject "Add my domain ApplePay manually from my dashboard in order to use ApplePay" which is located in the tab F.A.Q of the module.');
                    }
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    $this->module->warning[] = $e->getMessage();
                }
            }
        }
    }

    /*
     ** @Method: copyAppleDomainFile
     ** @description: Copy apple-developer-merchantid-domain-association file to .well-known/ folder
     **
     ** @arg: (none)
     ** @return: bool
     */
    public function copyAppleDomainFile()
    {
        if (!\Tools::copy(_PS_MODULE_DIR_ . 'stripe_official/apple-developer-merchantid-domain-association', _PS_ROOT_DIR_ . '/.well-known/apple-developer-merchantid-domain-association')) {
            return false;
        } else {
            return true;
        }
    }
}
