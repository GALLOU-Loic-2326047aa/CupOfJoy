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
{extends file='page.tpl'}

{block name='content'}
  <section id="content-hook_order_confirmation" class="card">
    <div class="card-block">
      <div class="row">
        <div class="col-md-12">
          <br />
          <p class="h3">
              {if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Order processed' d='Modules.Stripeofficial.Handlenextactioninforedirect'} {else} {l s='Order processed' mod='stripe_official'} {/if}
          </p>
          <div>
            <br />
            <br />
              {if isset($use_new_ps_translation) && $use_new_ps_translation}
                {{l s='Please check your order’s payment status in [a @href1@]My Account -> Order history and details[/a].' d='Modules.Stripeofficial.Handlenextactioninforedirect'}|stripelreplace:['@href1@' => {{$stripe_history_url|escape:'htmlall'}}] nofilter}<br />
              {else}
                {{l s='Please check your order’s payment status in [a @href1@]My Account -> Order history and details[/a].' mod='stripe_official'}|stripelreplace:['@href1@' => {{$stripe_history_url|escape:'htmlall'}}] nofilter}<br />
              {/if}
            <br />
            <br />
          </div>
        </div>
      </div>
    </div>
  </section>
{/block}
