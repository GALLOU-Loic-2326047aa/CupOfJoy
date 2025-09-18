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

use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PrestashopAdminTokenService
{
    public static function getToken(string $controllerName): string
    {
        if (version_compare(_PS_VERSION_, '9.0.0', '>=')) {
            $container = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
            $csrfTokenManager = $container->get(CsrfTokenManagerInterface::class);

            return $csrfTokenManager->getToken($controllerName);
        } else {
            return \Tools::getAdminTokenLite($controllerName);
        }
    }

    public static function verifyToken(string $controllerName, string $token): bool
    {
        if (version_compare(_PS_VERSION_, '9.0.0', '>=')) {
            $container = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
            $csrfTokenManager = $container->get(CsrfTokenManagerInterface::class);

            return $csrfTokenManager->isTokenValid(new CsrfToken($controllerName, $token));
        } else {
            return \Tools::getAdminTokenLite($controllerName) === $token;
        }
    }
}
