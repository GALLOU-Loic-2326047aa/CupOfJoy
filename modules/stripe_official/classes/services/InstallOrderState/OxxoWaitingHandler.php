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

class OxxoWaitingHandler extends InstallOrderStateHandler
{
    public function handle()
    {
        if (!\Configuration::get(\Stripe_official::OXXO_WAITING)
            || !\Validate::isLoadedObject(new \OrderState(\Configuration::get(\Stripe_official::OXXO_WAITING)))) {
            $order_state = new \OrderState();
            $order_state->name = [];
            foreach (\Language::getLanguages() as $language) {
                switch (\Tools::strtolower($language['iso_code'])) {
                    case 'fr':
                        $order_state->name[$language['id_lang']] = pSQL('En attente de la confirmation de paiement OXXO');
                        break;
                    case 'es':
                        $order_state->name[$language['id_lang']] = pSQL('Esperando la confirmación del pago de OXXO');
                        break;
                    case 'de':
                        $order_state->name[$language['id_lang']] = pSQL('Warten auf OXXO-Zahlungsbestätigung');
                        break;
                    case 'nl':
                        $order_state->name[$language['id_lang']] = pSQL('Wachten op OXXO betalingsbevestiging');
                        break;
                    case 'it':
                        $order_state->name[$language['id_lang']] = pSQL('In attesa della conferma del pagamento OXXO');
                        break;

                    default:
                        $order_state->name[$language['id_lang']] = pSQL('Waiting for OXXO payment confirmation');
                        break;
                }
            }
            $order_state->invoice = false;
            $order_state->send_email = false;
            $order_state->hidden = false;
            $order_state->delivery = false;
            $order_state->logable = false;
            $order_state->color = '#C23416';
            $order_state->module_name = $this->module->name;
            if ($order_state->add()) {
                $source = _PS_MODULE_DIR_ . 'stripe_official/views/img/ca_icon.gif';
                $destination = _PS_ROOT_DIR_ . '/img/os/' . (int) $order_state->id . '.gif';
                copy($source, $destination);
            }

            \Configuration::updateValue(\Stripe_official::OXXO_WAITING, $order_state->id);
        }
    }
}
