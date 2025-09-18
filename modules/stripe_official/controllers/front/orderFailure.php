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

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
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
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
class stripe_officialOrderFailureModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $returnUrl = $this->context->link->getPageLink('order');
        $newOrderFlow = !(int) Configuration::get(Stripe_official::ORDER_FLOW);
        if ($newOrderFlow) {
            $cartId = Tools::getValue('cartId');
            $order = null;
            if ($cartId) {
                $orderId = Order::getIdByCartId((int) $cartId);
                $order = new Order($orderId);
            }

            if ($order->id) {
                $returnUrl = $this->context->link->getPageLink(
                    'order',
                    true,
                    null,
                    ['submitReorder' => '1', 'id_order' => $order->id]);
                if ($order->getCurrentState() !== Configuration::get('PS_OS_ERROR')) {
                    $order->setCurrentState(Configuration::get('PS_OS_ERROR'));
                    $order->save();
                }
            }
        }

        $this->context->smarty->assign([
            'stripe_order_url' => $returnUrl,
        ]);

        $this->setTemplate('module:stripe_official/views/templates/front/order-confirmation-failed.tpl');
    }
}
