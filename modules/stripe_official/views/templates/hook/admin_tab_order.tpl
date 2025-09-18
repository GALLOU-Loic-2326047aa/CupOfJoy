{**
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
 *}
{* licence *}
<li class="nav-item">
    <a href="#StripePayment" class="nav-link" data-toggle="tab" role="tab" aria-controls="StripePayment">
        <i class="icon-money"></i>
        {if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Stripe' d='Modules.Stripeofficial.Admintaborder'} {else} {l s='Stripe' mod='stripe_official'} {/if} <span class="badge">1</span>
    </a>
</li>
