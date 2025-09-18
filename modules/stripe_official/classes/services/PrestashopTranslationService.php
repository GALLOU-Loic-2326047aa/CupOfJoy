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

if (!defined('_PS_VERSION_')) {
    exit;
}

class PrestashopTranslationService
{
    /**
     * @var \PaymentModule
     */
    private $paymentModule;

    private $moduleClass;

    private $oldTranslationSpecific;

    public function __construct(\PaymentModule $paymentModule, string $moduleClass, $oldTranslationSpecific = false)
    {
        $this->paymentModule = $paymentModule;
        $this->moduleClass = $moduleClass;
        $this->oldTranslationSpecific = $oldTranslationSpecific;
    }

    public function translate(string $string): string
    {
        if ($this->hasNewTranslationSystem()) {
            $parameters = [
                'legacy' => 'htmlspecialchars',
            ];

            return $this->paymentModule->getTranslator()->trans($string, $parameters, $this->moduleClass);
        } else {
            return $this->paymentModule->l($string, $this->paymentModule->name, $this->oldTranslationSpecific);
        }
    }

    public function hasNewTranslationSystem(): bool
    {
        return self::useNewTranslationSystem();
    }

    public static function useNewTranslationSystem(): bool
    {
        if (version_compare(_PS_VERSION_, '1.7.6', '>=')) {
            return true;
        }

        return false;
    }
}
