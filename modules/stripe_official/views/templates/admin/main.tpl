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
{include file="./_partials/messages.tpl"}
{if isset($include_prestashop_account) && $include_prestashop_account}
<prestashop-accounts></prestashop-accounts>
<div id="prestashop-cloudsync"></div>
<script src="{$urlAccountsCdn|escape:'htmlall':'UTF-8'}" rel=preload></script>

<script>
  /*********************
   * PrestaShop Account *
   * *******************/
  window?.psaccountsVue?.init();

  if(window.psaccountsVue.isOnboardingCompleted() != true)
  {
    if (document.getElementById("module-config") !== null) {
      document.getElementById("module-config").style.opacity = "0.5";
    }
  }
</script>
{/if}

<div class="tabs">
	<div class="sidebar navigation col-md-2">
		{if isset($logo)}
		  <img class="tabs-logo" src="{$logo|escape:'htmlall':'UTF-8'}"/>
		{/if}
		<nav class="list-group categorieList">
			<a class="list-group-item migration-tab" href="#stripe_step_1">
			  	<i class="icon-power-off pstab-icon"></i>
          {if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Connection' d='Modules.Stripeofficial.Main'} {else} {l s='Connection' mod='stripe_official'} {/if}
			  	<span class="badge-module-tabs pull-right {if $keys_configured === true}tab-success{else}tab-warning{/if}"></span>
			</a>
			<a class="list-group-item migration-tab" href="#stripe_step_2">
			  	<i class="icon-ticket pstab-icon"></i>
          {if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Refund' d='Modules.Stripeofficial.Main'} {else} {l s='Refund' mod='stripe_official'} {/if}
			</a>
		</nav>
	</div>

	<div class="col-md-10">
		<div class="content-wrap panel">
			<section id="section-shape-1">
				{include file="./_partials/configuration.tpl"}
			</section>
			<section id="section-shape-2">
				{include file="./_partials/refund.tpl"}
			</section>
		</div>
	</div>

  {if isset($include_prestashop_account) && $include_prestashop_account}
    <script src="{$urlCloudsync|escape:'htmlall':'UTF-8'}"></script>

    <script>
      window?.psaccountsVue?.init();
      // CloudSync
      const cdc = window.cloudSyncSharingConsent;

      cdc.init('#prestashop-cloudsync');
      cdc.on('OnboardingCompleted', (isCompleted) => {
      });
      cdc.isOnboardingCompleted((isCompleted) => {
      });
    </script>
  {/if}
</div>
