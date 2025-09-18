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

use Stripe\PaymentIntent;
use StripeOfficial\Classes\StripeProcessLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

class stripe_officialHandleNextActionModuleFrontController extends ModuleFrontController
{
    /**
     * @var PrestashopOrderService
     */
    private $prestashopOrderService;

    public function __construct($secretKey = null)
    {
        parent::__construct();
        $secretKey = $secretKey ?: Stripe_official::getSecretKey();
        $this->prestashopOrderService = new PrestashopOrderService($this->context, $this->module, $secretKey);
    }

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $paymentIntentId = Tools::getValue('paymentIntentId') ?: null;
        $cartId = Tools::getValue('cartId');

        StripeProcessLogger::logInfo('Handle Next Action Cart Id: ' . $cartId, 'handleNextAction', $cartId);

        if (!$paymentIntentId) {
            StripeProcessLogger::logError('Error paymentIntentId is undefined!', 'handleNextAction', $cartId);
            $failUrl = $this->context->link->getModuleLink(
                'stripe_official',
                'orderFailure',
                ['cartId' => $cartId],
                true);
            Tools::redirect($failUrl);
        }

        $publishableKey = Stripe_official::getPublishableKey();
        $paymentIntent = $this->prestashopOrderService->findStripePaymentIntent($paymentIntentId);

        $finalizeUrl = $this->context->link->getModuleLink(
            'stripe_official',
            'orderConfirmationReturn',
            ['cartId' => $cartId],
            true
        );

        $cancelUrl = $this->context->link->getModuleLink(
            'stripe_official',
            'orderFailure',
            ['cartId' => $cartId],
            true);

        $duplicatedCart = $this->prestashopOrderService->duplicatePrestaShopCart(new Cart($cartId));
        if (!$duplicatedCart) {
            StripeProcessLogger::logInfo('New cart cannot be created => ', 'handleNextAction', $cartId);

            return $cancelUrl;
        }

        $getClientSecretUrl = $this->context->link->getModuleLink(
            'stripe_official',
            'getClientSecret',
            [],
            true
        );

        $idempotencyKey = new StripeIdempotencyKey();
        $idempotencyKey->getByIdCart($cartId);

        Media::addJsDef([
            'finalizeUrl' => $finalizeUrl,
            'cancelUrl' => $cancelUrl,
            'getSecretUrl' => $getClientSecretUrl,
            'publishableKey' => $publishableKey,
            'paymentIntentId' => $paymentIntentId,
            'cartId' => $cartId,
            'idempotencyKey' => $idempotencyKey->idempotency_key,
        ]);

        $this->registerJavascript(
            'stripe_official-stripe-v3',
            'https://js.stripe.com/v3/',
            [
                'server' => 'remote',
                'position' => 'head',
            ]
        );

        $this->registerJavascript(
            'stripe_official-handleNextAction',
            'modules/stripe_official/views/js/handleNextAction.js'
        );

        $this->setTemplate('module:stripe_official/views/templates/front/handle-next-action.tpl');

        if ($paymentIntent->status !== PaymentIntent::STATUS_REQUIRES_ACTION) {
            $this->context->smarty->assign([
                'stripe_history_url' => $this->context->link->getPageLink('history'),
                'use_new_ps_translation' => StripeOfficial\Classes\services\PrestashopTranslationService::useNewTranslationSystem(),
            ]);
            $this->setTemplate('module:stripe_official/views/templates/front/handle-next-action-info-redirect.tpl');
        }
    }
}
