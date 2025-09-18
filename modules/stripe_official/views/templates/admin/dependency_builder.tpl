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
<!-- Load cdc library -->
<script src="https://assets.prestashop3.com/dst/mbo/v1/mbo-cdc-dependencies-resolver.umd.js"></script>


<!-- cdc container -->
<div id="cdc-container">
  <h3 style="padding-left: 10px; margin-bottom: 10px;">{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='To skip dependencies installation and use module without CloudSync click here:' d='Modules.Stripeofficial.Main'} {else} {l s='To skip CloudSync installation click here:' mod='stripe_official'} {/if}
    <button id="skip-cloudsync-button" class="btn btn-primary" style="margin-top: 10px; margin-bottom: 10px;">
      {if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Skip' d='Modules.Stripeofficial.Main'} {else} {l s='Skip' mod='stripe_official'} {/if}
      <span class="ps-stripe-loader hide">...</span>
    </button>
  </h3>
</div>

<script defer>
  const onDependenciesResolved = function () {
    fetch(cloudsync_clear_cache_url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      }
    })
      .then(res => res.json())
      .finally(data => {
        window.location.reload();
      });
  }
  const renderMboCdcDependencyResolver = window.mboCdcDependencyResolver.render
  const context = {
    ...{$dependencies|json_encode},
    onDependenciesResolved: () => onDependenciesResolved(),
    onDependencyResolved: (dependencyData) => console.log('Dependency for PS Account installed', dependencyData), // name, displayName, version
    onDependencyFailed: (dependencyData) => console.log('Failed to install dependency for PS Account', dependencyData),
    onDependenciesFailed: () => console.log('There are some errors for PS Account'),
  }
  renderMboCdcDependencyResolver(context, '#cdc-container')
</script>
