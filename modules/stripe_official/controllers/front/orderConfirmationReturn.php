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

class stripe_officialOrderConfirmationReturnModuleFrontController extends ModuleFrontController
{
    /**
     * @var StripeOrderConfirmationService
     */
    private $stripeOrderConfirmationService;

    public function __construct($secretKey = null)
    {
        parent::__construct();
        $this->ssl = true;
        $this->ajax = true;
        $this->json = true;
        $secretKey = $secretKey ?: Stripe_official::getSecretKey();
        $this->stripeOrderConfirmationService = new StripeOrderConfirmationService($this->context, $this->module, $secretKey);
    }

    /**
     * @throws Exception
     *
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $cartId = Tools::getValue('cartId');

        try {
            $this->stripeOrderConfirmationService->checkOrderAlreadyConfirmed($cartId);
        } catch (StripeOfficial\Classes\exceptions\StripeOrderAlreadyConfirmedException $e) {
            return Tools::redirect($e->getReturnUrl());
        }

        $newOrderFlow = !(int) Configuration::get(Stripe_official::ORDER_FLOW);

        $redirectUrl = $newOrderFlow ?
            $this->stripeOrderConfirmationService->orderConfirmationNew($cartId) :
            $this->stripeOrderConfirmationService->orderConfirmationLegacy($cartId)
        ;

        Tools::redirect($redirectUrl);
    }
}
