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
<div class="tab-pane" id="StripePayment">
  <p>
    <span><strong>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Payment ID' d='Modules.Stripeofficial.Admincontentorder'} {else} {l s='Payment ID' mod='stripe_official'} {/if}</strong></span><br/>
    <span><a href="{$stripe_dashboardUrl.charge|escape:'htmlall':'UTF-8'}"
             target="blank">{$stripe_charge|escape:'htmlall':'UTF-8'}</a></span>
  </p>
  <p>
    <span><strong>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Payment method' d='Modules.Stripeofficial.Admincontentorder'} {else} {l s='Payment method' mod='stripe_official'} {/if}</strong></span><br/>
    <span><img
        src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/cc-{$stripe_paymentType|escape:'htmlall':'UTF-8'}.png"
        alt="payment method" style="width:43px;"/></span>
  </p>

    {if isset($stripe_dateCatch) && $stripe_dateCatch != '0000-00-00 00:00:00'}
      <p>
        <span><strong>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Authorize date' d='Modules.Stripeofficial.Admincontentorder'} {else} {l s='Authorize date' mod='stripe_official'} {/if}</strong></span><br/>
        <span>{$stripe_dateCatch|escape:'htmlall':'UTF-8'}</span>
      </p>
    {/if}

    {if (isset($stripe_dateAuthorize) && $stripe_dateAuthorize != '0000-00-00 00:00:00') || (isset($stripe_expired) && $stripe_expired == 1)}
      <p>
        <span><strong>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Capture date' d='Modules.Stripeofficial.Admincontentorder'} {else} {l s='Capture date' mod='stripe_official'} {/if}</strong></span><br/>
          {if $stripe_dateAuthorize != '0000-00-00 00:00:00'}
            <span>{$stripe_dateAuthorize|escape:'htmlall':'UTF-8'}</span>
          {else}
            <span>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Expired' d='Modules.Stripeofficial.Admincontentorder'} {else} {l s='Expired' mod='stripe_official'} {/if}</span>
          {/if}
      </p>
    {/if}

  <p>
    <span><strong>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Payment dispute' d='Modules.Stripeofficial.Admincontentorder'} {else} {l s='Payment dispute' mod='stripe_official'} {/if}</strong></span><br/>
      {if $stripe_dispute === true}
        <span><a href="{$stripe_dashboardUrl.charge|escape:'htmlall':'UTF-8'}"
                 target="blank">{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='check your dispute here' d='Modules.Stripeofficial.Admincontentorder'} {else} {l s='check your dispute here' mod='stripe_official'} {/if}</a></span>
      {else}
        <span>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='No dispute' d='Modules.Stripeofficial.Admincontentorder'} {else} {l s='No dispute' mod='stripe_official'} {/if}</span>
      {/if}
  </p>

    {if (isset($stripe_paymentType) && $stripe_paymentType == 'oxxo')}
      <p>
        <span><strong>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Voucher Validation' d='Modules.Stripeofficial.Admincontentorder'} {else} {l s='Voucher Validation' mod='stripe_official'} {/if}</strong></span><br/>
          {if $stripe_voucher_validate != '0000-00-00'}
            <span>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Voucher validate on:' d='Modules.Stripeofficial.Admincontentorder'} {else} {l s='Voucher validate on:' mod='stripe_official'} {/if} {$stripe_voucher_validate|escape:'htmlall':'UTF-8'}</span>
          {else}
            <span>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Voucher will expire on:' d='Modules.Stripeofficial.Admincontentorder'} {else} {l s='Voucher will expire on:' mod='stripe_official'} {/if} {$stripe_voucher_expire|escape:'htmlall':'UTF-8'}</span>
          {/if}
      </p>
    {/if}
    {if (isset($stripe_paymentMethod) && $stripe_paymentMethod == 'card')}
      <p>
        <span><strong>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Stripe Customer ID' d='Modules.Stripeofficial.Admincontentorder'} {else} {l s='Stripe Customer ID' mod='stripe_official'} {/if}</strong></span><br/>
        <span>{$stripe_customerID|escape:'htmlall':'UTF-8'}</span>
      </p>
      <p>
        <span><strong>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Risk evaluation' d='Modules.Stripeofficial.Admincontentorder'} {else} {l s='Risk evaluation' mod='stripe_official'} {/if}</strong></span><br/>
        <span><strong>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Score:' d='Modules.Stripeofficial.Admincontentorder'} {else} {l s='Score:' mod='stripe_official'} {/if}</strong> {$stripe_riskScore|escape:'htmlall':'UTF-8'}</span>

        <br/>
        <span><strong>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Level:' d='Modules.Stripeofficial.Admincontentorder'} {else} {l s='Level:' mod='stripe_official'} {/if}</strong> {$stripe_riskLevel|escape:'htmlall':'UTF-8'}</span>
      </p>
    {/if}

    {if ($stripe_partialRefunded === true)}
      <p>
        <span><strong>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Amount Refunded:' d='Modules.Stripeofficial.Admincontentorder'} {else} {l s='Amount Refunded:' mod='stripe_official'} {/if}</strong> </span> <br/>
        <span>{$stripe_refundedAmount|escape:'htmlall':'UTF-8'}</span>
      </p>
    {/if}
</div>
