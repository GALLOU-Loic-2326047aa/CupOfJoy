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

namespace StripeOfficial\Classes\services\InstallOrderState;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PaymentWaitingHandler extends InstallOrderStateHandler
{
    public function handle()
    {
        if (!\Configuration::get(\Stripe_official::PAYMENT_WAITING)
            || !\Validate::isLoadedObject(new \OrderState(\Configuration::get(\Stripe_official::PAYMENT_WAITING)))) {
            $order_state = new \OrderState();
            $order_state->name = [];
            foreach (\Language::getLanguages() as $language) {
                switch (\Tools::strtolower($language['iso_code'])) {
                    case 'fr':
                        $order_state->name[$language['id_lang']] = pSQL('En attente de confirmation de paiement');
                        break;
                    case 'es':
                        $order_state->name[$language['id_lang']] = pSQL('Esperando de confirmación de pago');
                        break;
                    case 'de':
                        $order_state->name[$language['id_lang']] = pSQL('Warten auf Zahlungsbestätigung');
                        break;
                    case 'nl':
                        $order_state->name[$language['id_lang']] = pSQL('Wachten op betalingsbevestiging');
                        break;
                    case 'it':
                        $order_state->name[$language['id_lang']] = pSQL('In attesa di conferma del pagamento');
                        break;

                    default:
                        $order_state->name[$language['id_lang']] = pSQL('Awaiting for payment confirmation');
                        break;
                }
            }
            $order_state->invoice = true;
            $order_state->send_email = false;
            $order_state->logable = true;
            $order_state->color = '#c7ff49';
            $order_state->module_name = $this->module->name;
            if ($order_state->add()) {
                $source = _PS_MODULE_DIR_ . 'stripe_official/views/img/ca_icon.gif';
                $destination = _PS_ROOT_DIR_ . '/img/os/' . (int) $order_state->id . '.gif';
                copy($source, $destination);
            }

            \Configuration::updateValue(\Stripe_official::PAYMENT_WAITING, $order_state->id);
        }
    }
}
