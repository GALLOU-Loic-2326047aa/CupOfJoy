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

use Stripe\Exception\ApiErrorException;
use StripeOfficial\Classes\StripeProcessLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

class stripe_officialCreateElementsModuleFrontController extends ModuleFrontController
{
    /**
     * @var StripeCustomerService
     */
    private $stripeCustomerService;

    /**
     * @var StripePaymentIntentService
     */
    private $stripePaymentIntentService;

    /**
     * @param string|null $secretKey
     */
    public function __construct($secretKey = null)
    {
        parent::__construct();
        $secretKey = $secretKey ?: Stripe_official::getSecretKey();
        $this->stripePaymentIntentService = new StripePaymentIntentService($secretKey);
        $this->stripeCustomerService = new StripeCustomerService($secretKey);
    }

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();
        $elementData = $billingDetails = [];
        $customerSessionClientSecret = '';

        try {
            $cartContextModel = CartContextModel::getFromContext($this->context);
            $separateAuthAndCapture = Configuration::get(Stripe_official::CATCHANDAUTHORIZE);
            $stripeCustomerId = $this->stripeCustomerService->getOrCreateStripeCustomerId($cartContextModel, true);
            $customerSessionClientSecret = null;
            if ($stripeCustomerId && Configuration::get(Stripe_official::ENABLE_SAVE_PAYMENT_METHOD) !== Stripe_official::TYPE_SAVE_PAYMENT_METHOD_OFF) {
                $customerSessionData = $this->stripeCustomerService->buildCustomerSessionData($stripeCustomerId);
                $customerSession = $this->stripeCustomerService->createCustomerSession($customerSessionData);
                $customerSessionClientSecret = $customerSession->client_secret;
            }
            $elementData = $this->stripePaymentIntentService->buildPaymentIntentParams($cartContextModel, $separateAuthAndCapture);
            $elementData['mode'] = 'payment';
            $billingDetails = $this->stripePaymentIntentService->buildBillingDetails($cartContextModel);
        } catch (ApiErrorException $e) {
            StripeProcessLogger::logError('Retrieve Stripe Account Error => ' . $e->getMessage() . ' - ' . $e->getTraceAsString(), 'createElements', $this->context->cart->id);
        } catch (PrestaShopException $e) {
            StripeProcessLogger::logError('Retrieve Prestashop State Error => ' . $e->getMessage() . ' - ' . $e->getTraceAsString(), 'createElements', $this->context->cart->id);
        }

        echo json_encode([
            'element' => $elementData,
            'billing_details' => $billingDetails,
            'customer_session_client_secret' => $customerSessionClientSecret,
        ])
        ;
        exit;
    }
}
