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

use Stripe\Exception\ApiConnectionException;
use StripeOfficial\Classes\StripeProcessLogger;

class IsCorrectlyConfiguredAction extends BaseAction
{
    public function execute()
    {
        /* Check if public and secret key have been defined */
        if (!\Stripe_official::isWellConfigured()) {
            $this->module->errors[] = $this->translationService->translate('Keys are empty.');
        }

        /* Check if TLS is enabled and the TLS version used is 1.2 */
        if (\Stripe_official::isWellConfigured()) {
            $secretKey = \Stripe_official::getSecretKey();
            if ($this->module->checkApiConnection($secretKey) !== false) {
                try {
                    \Stripe\Charge::all();
                } catch (ApiConnectionException $e) {
                    StripeProcessLogger::logInfo('Api Connection Exception ' . $e->getMessage(), 'stripe_official');

                    $this->module->warning[] = $this->translationService->translate('Your TLS version is not supported. You will need to upgrade your integration. Please check the FAQ if you don\'t know how to do it.');
                }
            }
        }

        return true;
    }
}
