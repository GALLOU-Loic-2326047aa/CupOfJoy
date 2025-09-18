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

namespace StripeOfficial\Classes;

if (!defined('_PS_VERSION_')) {
    exit;
}

class StripeProcessLogger
{
    public static function logInfo($infoMessage, $logName = null, $cartId = '', $paymentIntentId = '')
    {
        $infoMessage = self::getCallerString() . ' - ' . $infoMessage;
        \PrestaShopLogger::addLog(self::getFormattedMessageLogs($infoMessage, $paymentIntentId), 1, null, $logName, $cartId, true);
    }

    public static function logError($errorMessage, $logName = null, $cartId = '', $paymentIntentId = '')
    {
        $errorMessage = self::getCallerString() . ' - ' . $errorMessage;
        \PrestaShopLogger::addLog(self::getFormattedMessageLogs($errorMessage, $paymentIntentId), 3, null, $logName, $cartId, true);
    }

    public static function logWarning($warningMessage, $logName = null, $cartId = '', $paymentIntentId = '')
    {
        $warningMessage = self::getCallerString() . ' - ' . $warningMessage;
        \PrestaShopLogger::addLog(self::getFormattedMessageLogs($warningMessage, $paymentIntentId), 2, null, $logName, $cartId, true);
    }

    public static function getFormattedMessageLogs($messageLog, $paymentIntent = '')
    {
        $mode = \Configuration::get('STRIPE_MODE') == 1 ? 'T' : 'L';
        $pe_enabled = \Configuration::get('STRIPE_ENABLE_PAYMENT_ELEMENTS') == 1 ? 'I' : 'R';
        $express_checkout = \Configuration::get('STRIPE_ENABLE_EXPRESS_CHECKOUT');
        foreach (explode(',', \Configuration::get('STRIPE_EXPRESS_CHECKOUT_LOCATIONS') ?? '') as $ec_location) {
            $ec_location = trim($ec_location);
            if ($ec_location) {
                $express_checkout .= strtoupper($ec_location[0]);
            }
        }
        $ac = \Configuration::get('STRIPE_CATCHANDAUTHORIZE');
        $flow = \Configuration::get('STRIPE_ORDER_FLOW') == 1 ? 'L' : 'N';
        $save = \Configuration::get('STRIPE_ENABLE_SAVE_PAYMENT_METHOD');

        $configuration_string = "m{$mode}pe{$pe_enabled}ec{$express_checkout}ac{$ac}f{$flow}s{$save}";

        $message = 'stripe_official - ' . $configuration_string . ' - ';
        $message .= $paymentIntent ? 'PaymentIntent: ' . $paymentIntent . '; ' : '';
        $message .= $messageLog;

        return $message;
    }

    private static function getCallerString()
    {
        $bt = debug_backtrace();
        $caller = $bt[1] ?? null;
        if (empty($caller)) {
            return '';
        }
        $path = preg_replace('#^.*?stripe_official#', '', $caller['file']);

        return $path . ':' . $caller['line'];
    }
}
