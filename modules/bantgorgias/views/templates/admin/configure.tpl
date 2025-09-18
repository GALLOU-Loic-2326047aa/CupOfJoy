{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop-project.org/ for more information.
 *
 * @author B-Ant Digital Solutions Zrt. <addons@blueant-solutions.com>
 * @copyright 2019-2025 B-Ant Digital Solutions Zrt.
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 *}
<prestashop-accounts></prestashop-accounts>
<div id="prestashop-cloudsync"></div>
<div id="ps-modal"></div>
{if isset($isLinked) && $isLinked}
  <div id="connect" class="panel form-horizontal">
    <div class="panel-heading">{l s='General settings' mod='bantgorgias'}</div>
    <div class="form-wrapper">
      <div class="form-group">
        <label for="connectInput" class="control-label col-lg-4">{l s='Gorgias URL:' mod='bantgorgias'}</label>
        <div class="col-lg-8">
          <div class="input-group">
            <input type="text" id="connectInput" name="gorgias_connect" value="{if isset($domain) && $domain}{$domain|escape:'htmlall':'UTF-8'}{/if}" />
            <span class="input-group-addon">.gorgias.com</span>
          </div>
          <p class="help-block">{l s='This should be your first part of your Gorgias helpdesk URL. For example:' mod='bantgorgias'} <b>{l s='example' mod='bantgorgias'}</b><s>.gorgias.com</s></p>
          <p class="help-block">{l s='If you have issues with your connection just click on the Connect button again' mod='bantgorgias'}</p>
        </div>
      </div>
      <div class="form-group">
        <label for="orderState" class="control-label col-lg-4">{l s='Cancelled order state:' mod='bantgorgias'}</label>
        <div class="col-lg-8">
          <div class="input-group" style="width: 100%">
            <select id="orderState" name="orderState">
              <option disabled="disabled" value="0" {if !isset($currentOrderState) || !$currentOrderState}selected="selected"{/if}>{l s='Select an order state for cancelled orders' mod='bantgorgias'}</option>
              {foreach $orderStates as $orderState}
                <option {if isset($currentOrderState) && $orderState.id_order_state == $currentOrderState}selected="selected"{/if} value="{$orderState.id_order_state|intval}">{$orderState.name|escape:'htmlall':'UTF-8'}</option>
              {/foreach}
            </select>
          </div>
          <p class="help-block">{l s='Select the correct order status for cancelling an order' mod='bantgorgias'}</p>
        </div>
      </div>

      <div class="form-group">
        <label for="refundOrderState" class="control-label col-lg-4">{l s='Refunded order state:' mod='bantgorgias'}</label>
        <div class="col-lg-8">
          <div class="input-group" style="width: 100%">
            <select id="refundOrderState" name="refundOrderState">
              <option disabled="disabled" value="0" {if !isset($currentRefundOrderState) || !$currentRefundOrderState}selected="selected"{/if}>{l s='Select an order state for refunded orders' mod='bantgorgias'}</option>
              {foreach $orderStates as $orderState}
                <option {if isset($currentRefundOrderState) && $orderState.id_order_state == $currentRefundOrderState}selected="selected"{/if} value="{$orderState.id_order_state|intval}">{$orderState.name|escape:'htmlall':'UTF-8'}</option>
              {/foreach}
            </select>
          </div>
          <p class="help-block">{l s='Select the correct order status for refunding an order' mod='bantgorgias'}</p>
        </div>
      </div>

      <div class="form-group">
        <label for="connectChat" class="control-label col-lg-4">{l s='Chat script:' mod='bantgorgias'}</label>
        <div class="col-lg-8">
          <div class="input-group">
            <textarea id="connectChat" name="gorgias_connect_chat" style="min-height: 200px" >{if isset($chat) && $chat}{$chat nofilter}{/if}</textarea>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label for="connectInputAuthUser" class="control-label col-lg-4">{l s='Authentication popup user:' mod='bantgorgias'}</label>
        <div class="col-lg-8">
          <div class="input-group">
            <input type="text" id="connectInputAuthUser" name="gorgias_connect_auth_user" value="{if isset($authUser) && $authUser}{$authUser|escape:'htmlall':'UTF-8'}{/if}" />
          </div>
        </div>
      </div>

      <div class="form-group">
        <label for="connectInputAuthPass" class="control-label col-lg-4">{l s='Authentication popup password:' mod='bantgorgias'}</label>
        <div class="col-lg-8">
          <div class="input-group">
            <input type="text" id="connectInputAuthPass" name="gorgias_connect_auth_pass" value="{if isset($authPass) && $authPass}{$authPass|escape:'htmlall':'UTF-8'}{/if}" />
          </div>
        </div>
      </div>

      <div id="connectResponse" class="{if !$api_key || !$domain}hidden{/if}">
        <p id="connectResponseOk" class="alert alert-success {if !$api_key || !$domain}hidden{/if}">{l s='The Gorgias account was connected successfully' mod='bantgorgias'}</p>
        <p id="connectResponseError" class="alert alert-danger hidden">{l s='There was an error connecting the Gorgias account' mod='bantgorgias'}</p>
      </div>
    </div>
    <div class="panel-footer">
      <a id="connectButton" href="#" class="btn btn-default pull-right">{l s='Connect' mod='bantgorgias'}</a>
    </div>
  </div>
{/if}
<style>
  #connect {
    margin: 30px auto;
  }
  .bootstrap .input-group {
    width: 100%;
  }
</style>

<script src="{$urlAccountsCdn|escape:'htmlall':'UTF-8'}" rel=preload></script>
<script src="{$urlCloudsync|escape:'htmlall':'UTF-8'}"></script>

<script>
  window?.psaccountsVue?.init()

  // Cloud Sync
  const cdc = window.cloudSyncSharingConsent

  if (typeof cdc !== 'undefined') {
    cdc.init('#prestashop-cloudsync')
  }

  {if isset($shopUuid) && $shopUuid}
  document.getElementById('connectButton').addEventListener('click', function (e) {
    e.preventDefault()
    e.stopPropagation()

    fetch('{$apiUrl|escape:'javascript':'UTF-8'}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        ajax: true,
        accountsData: {$accountsData nofilter},
        gorgiasDomain: document.getElementById('connectInput').value,
        shopUuid: '{$shopUuid|escape:'javascript':'UTF-8'}',
        shopDomain: '{$shopDomain|escape:'javascript':'UTF-8'}',
        orderState: document.getElementById('orderState').value,
        refundOrderState: document.getElementById('refundOrderState').value,
        chat: document.getElementById('connectChat').value,
        authUser: document.getElementById('connectInputAuthUser').value,
        authPass: document.getElementById('connectInputAuthPass').value
      })
    })
      .then(response => response.json())
      .then(data => {
        if (data['message'].indexOf('/gorgias/oauth/install') === -1) {
          document.getElementById('connectResponse').classList.remove('hidden')
          document.getElementById('connectResponseError').classList.remove('hidden')
          document.getElementById('connectResponseOk').classList.add('hidden')
          window.setTimeout(() => {
            window.location.reload()
          }, 5000)
        } else {
          document.getElementById('connectResponse').classList.remove('hidden')
          document.getElementById('connectResponseError').classList.add('hidden')
          document.getElementById('connectResponseOk').classList.remove('hidden')

          window.open(data['message'], '_blank')
          window.location.reload()
        }
      })
      .catch(error => {
        console.log(['fetch error', error])
      })
  })
  {/if}
</script>
