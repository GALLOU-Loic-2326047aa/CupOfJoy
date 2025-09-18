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
          <p>
              {if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='An error occured during your payment.' d='Modules.Stripeofficial.Orderconfirmationfailed17'} {else} {l s='An error occured during your payment.' mod='stripe_official'} {/if}<br />
              {if isset($use_new_ps_translation) && $use_new_ps_translation}
                  {{l s='Please [a @href1@]try again[/a] or contact the website owner.' d='Modules.Stripeofficial.Orderconfirmationfailed17'}|stripelreplace:['@href1@' => {$stripe_order_url|escape}] nofilter}<br/><br/>
              {else}
                  {{l s='Please [a @href1@]try again[/a] or contact the website owner.' mod='stripe_official'}|stripelreplace:['@href1@' => {$stripe_order_url|escape}] nofilter}<br/><br/>
              {/if}
          </p>
        </div>
      </div>
    </div>
  </section>
{/block}
