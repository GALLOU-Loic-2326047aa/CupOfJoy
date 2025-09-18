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
if (!defined('_PS_VERSION_')) {
    exit;
}

// ToDo rewrite this abomination
class stripe_officialWebhookModuleFrontController extends ModuleFrontController
{
    /**
     * Override displayMaintenancePage to prevent the maintenance page to be displayed.
     *
     * @see FrontController::displayMaintenancePage()
     */
    protected function displayMaintenancePage()
    {
    }

    /**
     * Override displayRestrictedCountryPage to prevent page country is not allowed.
     *
     * @see FrontController::displayRestrictedCountryPage()
     */
    protected function displayRestrictedCountryPage()
    {
    }

    /**
     * Override geolocationManagement to prevent country GEOIP blocking.
     *
     * @see FrontController::geolocationManagement()
     *
     * @param Country $defaultCountry
     *
     * @return false
     */
    protected function geolocationManagement($defaultCountry)
    {
        return false;
    }

    /**
     * Override sslRedirection to prevent redirection.
     *
     * @see FrontController::sslRedirection()
     */
    protected function sslRedirection()
    {
    }

    /**
     * Override canonicalRedirection to prevent redirection.
     *
     * @see FrontController::canonicalRedirection()
     *
     * @param string $canonical_url
     */
    protected function canonicalRedirection($canonical_url = '')
    {
    }

    public function postProcess()
    {
        // Retrieve payload
        $eventPayload = @Tools::file_get_contents('php://input');

        // Retrieve http signature
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'];

        // Retrieve secret endpoint
        $webhookSecret = Configuration::get(Stripe_official::WEBHOOK_SIGNATURE, null, Stripe_official::getShopGroupIdContext(), Stripe_official::getShopIdContext());

        $webhookHandler = new WebhookEventHandler($this->context, $this->module);
        $webhookHandler->handleRequest($eventPayload, $signature, $webhookSecret);

        http_response_code(200);
        echo 'Webhook handled successfully!';
        exit;
    }
}
