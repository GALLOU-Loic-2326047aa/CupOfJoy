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

use Stripe\StripeClient;

if (!defined('_PS_VERSION_')) {
    exit;
}

class StripeDispute extends ObjectModel
{
    /** @var string */
    public $stripe_dispute_id;

    public function __construct($stripe_dispute_id = null)
    {
        $this->stripe_dispute_id = $stripe_dispute_id;
    }

    public function getAllDisputes($id_shop)
    {
        $module = Module::getInstanceByName('stripe_official');
        $key = $module->getSecretKey($id_shop);

        if (empty($key)) {
            return false;
        }

        $stripe = new StripeClient($key);

        return $stripe->disputes->all();
    }

    public function orderHasDispute($id_charge, $id_shop)
    {
        if ($disputes = $this->getAllDisputes($id_shop)) {
            foreach ($disputes->data as $dispute) {
                if ($dispute->charge == $id_charge) {
                    return true;
                }
            }
        }

        return false;
    }
}
