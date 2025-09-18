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
<link href="https://cdn.jsdelivr.net/npm/@coreui/coreui-pro@5.0.0/dist/css/coreui.min.css" rel="stylesheet" integrity="sha384-IWXc/Qn4K3kXUZMsZBceGfN84sg1+4HwBe2h5xrkXUexo51S/ImL+3wWnCHsh2uZ" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/@coreui/coreui-pro@5.0.0/dist/js/coreui.bundle.min.js" integrity="sha384-XhHOTYRsazIACXdXVSb4WMf8BMnDO9Pmd5nlutkwH4jryCW6NHABK94+4xk2qTYM" crossorigin="anonymous"></script>

<form id="configuration_form" class="defaultForm form-horizontal stripe_official" action="#stripe_step_1" method="post" enctype="multipart/form-data" novalidate="">
  <input type="hidden" name="submit_login" value="1">
  <input type="hidden" name="order_status_select" value="{$orderStatusSelected|escape:'htmlall':'UTF-8'}">
  <div class="panel" id="fieldset_0">
    <div class="form-wrapper">
      {if !$include_prestashop_account }
        <div class="form-group">
          {if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='If you want to use CloudSync with the module, please install the dependencies ' d='Modules.Stripeofficial.Configuration'} {else} {l s='If you want to use CloudSync with the module, please install the dependencies' mod='stripe_official'} {/if}
          <a href="javascript:void(0);" id="get-cloudsync-button" >
            {if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='here' d='Modules.Stripeofficial.Main'} {else} {l s='here' mod='stripe_official'} {/if}
            <span class="ps-stripe-loader hide">...</span>
          </a>
        </div>
      {/if}
      <div class="form-group stripe-connection">
          {assign var='stripe_url' value='https://partners-subscribe.prestashop.com/stripe/connect.php?params[return_url]='}
          {if isset($use_new_ps_translation) && $use_new_ps_translation}
              {{l s='[a @href1@]Create your Stripe account in 10 minutes[/a] and immediately start accepting card payments as well as local payment methods (no additional contract/merchant ID needed from your bank).' d='Modules.Stripeofficial.Configuration'}|stripelreplace:['@href1@' => {{$stripe_url|cat:$return_url|escape:'htmlall':'UTF-8'}}, '@target@' => {'target="blank"'}]}<br>
          {else}
              {{l s='[a @href1@]Create your Stripe account in 10 minutes[/a] and immediately start accepting card payments as well as local payment methods (no additional contract/merchant ID needed from your bank).' mod='stripe_official'}|stripelreplace:['@href1@' => {{$stripe_url|cat:$return_url|escape:'htmlall':'UTF-8'}}, '@target@' => {'target="blank"'}]}<br>
          {/if}

        <div class="connect_btn">
          <a href="https://partners-subscribe.prestashop.com/stripe/connect.php?params[return_url]={$return_url|escape:'htmlall':'UTF-8'}" class="stripe-connect">
            <span>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Connect with Stripe' d='Modules.Stripeofficial.Configuration'} {else} {l s='Connect with Stripe' mod='stripe_official'} {/if}</span>
          </a>
        </div>
      </div>
      <hr/>
      <div class="form-group">
        <label class="control-label col-lg-3"> {if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Mode' d='Modules.Stripeofficial.Configuration'} {else} {l s='Mode' mod='stripe_official'} {/if}</label>
        <div class="col-lg-9">
					<span class="switch prestashop-switch fixed-width-lg">
						<input type="radio" name="STRIPE_MODE" id="STRIPE_MODE_ON" value="1" {if $stripe_mode == 1}checked="checked"{/if}>
						<label for="STRIPE_MODE_ON">{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='test' d='Modules.Stripeofficial.Configuration'} {else} {l s='test' mod='stripe_official'} {/if}</label>
						<input type="radio" name="STRIPE_MODE" id="STRIPE_MODE_OFF" value="0" {if $stripe_mode == 0}checked="checked"{/if}>
						<label for="STRIPE_MODE_OFF">{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='live' d='Modules.Stripeofficial.Configuration'} {else} {l s='live' mod='stripe_official'} {/if}</label>
						<a class="slide-button btn"></a>
					</span>
          <p class="help-block"></p>
        </div>
        <span>
					{{l s='You can find your API keys in the Developers section of your Stripe [a @href1@]dashboard[/a].' mod='stripe_official'}|stripelreplace:['@href1@' => {'https://dashboard.stripe.com/account/apikeys'}, '@target@' => {'target="blank"'}]}
				</span>
      </div>

      <div class="form-group" {if $stripe_mode == 1}style="display: none;"{/if}>
        <label class="control-label col-lg-3 required">{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Publishable key (live mode)' d='Modules.Stripeofficial.Configuration'} {else} {l s='Publishable key (live mode)' mod='stripe_official'} {/if} </label>
        <div class="col-lg-9">
          <input type="text" name="STRIPE_PUBLISHABLE" id="public_key" value="{$stripe_publishable|escape:'htmlall':'UTF-8'}" class="fixed-width-xxl" size="20" required="required">
        </div>
      </div>
      <div class="form-group" {if $stripe_mode == 1}style="display: none;"{/if}>
        <label class="control-label col-lg-3 required">{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Secret key (live mode)' d='Modules.Stripeofficial.Configuration'} {else} {l s='Secret key (live mode)' mod='stripe_official'} {/if}</label>
        <div class="col-lg-9">
          <input type="password" name="STRIPE_KEY" id="secret_key" value="{$stripe_key|escape:'htmlall':'UTF-8'}" class="fixed-width-xxl" size="20" required="required">
        </div>
      </div>
      <div class="form-group"{if $stripe_mode == 0}style="display: none;"{/if}>
        <label class="control-label col-lg-3 required">{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Publishable key (test mode)' d='Modules.Stripeofficial.Configuration'} {else} {l s='Publishable key (test mode)' mod='stripe_official'} {/if}</label>
        <div class="col-lg-9">
          <input type="text" name="STRIPE_TEST_PUBLISHABLE" id="test_public_key" value="{$stripe_test_publishable|escape:'htmlall':'UTF-8'}" class="fixed-width-xxl" size="20" required="required">
        </div>
      </div>
      <div class="form-group"{if $stripe_mode == 0}style="display: none;"{/if}>
        <label class="control-label col-lg-3 required">{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Secret key (test mode)' d='Modules.Stripeofficial.Configuration'} {else} {l s='Secret key (test mode)' mod='stripe_official'} {/if}</label>
        <div class="col-lg-9">
          <input type="password" name="STRIPE_TEST_KEY" id="test_secret_key" value="{$stripe_test_key|escape:'htmlall':'UTF-8'}" class="fixed-width-xxl" size="20" required="required">
        </div>
      </div>

      <div id="conf-payment-methods">
        <p><b>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Testing Stripe' d='Modules.Stripeofficial.Configuration'} {else} {l s='Testing Stripe' mod='stripe_official'} {/if} </b></p>
        <ul>
          <li>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Toggle the button above to Test Mode.' d='Modules.Stripeofficial.Configuration'} {else} {l s='Toggle the button above to Test Mode.' mod='stripe_official'} {/if} </li>
          <li>
              {if isset($use_new_ps_translation) && $use_new_ps_translation}
                  {{l s='You\'ll find test card numbers in our [a @href1@]documentation[/a].' d='Modules.Stripeofficial.Configuration'}|stripelreplace:['@href1@' => {'http://www.stripe.com/docs/testing'}, '@target@' => {'target="blank"'}]}
              {else}
                  {{l s='You\'ll find test card numbers in our [a @href1@]documentation[/a].' mod='stripe_official'}|stripelreplace:['@href1@' => {'http://www.stripe.com/docs/testing'}, '@target@' => {'target="blank"'}]}
              {/if}
          </li>
          <li>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='In test mode, real cards are not accepted.' d='Modules.Stripeofficial.Configuration'} {else} {l s='In test mode, real cards are not accepted.' mod='stripe_official'} {/if}</li>
        </ul>
        <p><b>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Going live with Stripe' d='Modules.Stripeofficial.Configuration'} {else} {l s='Going live with Stripe' mod='stripe_official'} {/if} </b></p>
        <ul>
          <li>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Toggle the button above to Live Mode.' d='Modules.Stripeofficial.Configuration'} {else} {l s='Toggle the button above to Live Mode.' mod='stripe_official'} {/if} </li>
          <li>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='In live mode, tests are no longer allowed.' d='Modules.Stripeofficial.Configuration'} {else} {l s='In live mode, tests are no longer allowed.' mod='stripe_official'} {/if} </li>
        </ul>
        <p><b>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Getting support' d='Modules.Stripeofficial.Configuration'} {else} {l s='Getting support' mod='stripe_official'} {/if} </b></p>
        <ul>
          <li>
              {if isset($use_new_ps_translation) && $use_new_ps_translation}
                  {{l s='If you have any questions, please check out [a @href1@]our FAQs[/a] first.' d='Modules.Stripeofficial.Configuration'}|stripelreplace:['@href1@' => {'https://support.stripe.com/questions/prestashop'}, '@target@' => {'target="blank"'}] nofilter}
              {else}
                  {{l s='If you have any questions, please check out [a @href1@]our FAQs[/a] first.' mod='stripe_official'}|stripelreplace:['@href1@' => {'https://support.stripe.com/questions/prestashop'}, '@target@' => {'target="blank"'}] nofilter}
              {/if}
          </li>
          <li>
              {if isset($use_new_ps_translation) && $use_new_ps_translation}
                  {{l s='For questions regarding the module itself, feel free to [a @href1@]reach out to the developers[/a].' d='Modules.Stripeofficial.Configuration'}|stripelreplace:['@href1@' => {'https://addons.prestashop.com/en/contact-us?id_product=24922'}, '@target@' => {'target="blank"'}] nofilter}
              {else}
                  {{l s='For questions regarding the module itself, feel free to [a @href1@]reach out to the developers[/a].' mod='stripe_official'}|stripelreplace:['@href1@' => {'https://addons.prestashop.com/en/contact-us?id_product=24922'}, '@target@' => {'target="blank"'}] nofilter}
              {/if}
          </li>
          <li>
              {{l s='For questions regarding your Stripe account, contact the [a @href1@]Stripe support[/a].' mod='stripe_official'}|stripelreplace:['@href1@' => {'https://support.stripe.com/contact'}, '@target@' => {'target="blank"'}] nofilter}
          </li>
        </ul>

        <br>
        <div class="form-group">
          <p><b>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Payment form settings*' d='Modules.Stripeofficial.Configuration'} {else} {l s='Payment form settings*' mod='stripe_official'} {/if} </b></p>
          <div class="left20">
            <input type="radio" id="element" name="payment_element" value='1' class="child" {if $payment_element}checked{/if}>
            <label for="element">
                {if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Integrated payment form' d='Modules.Stripeofficial.Configuration'} {else} {l s='Integrated payment form' mod='stripe_official'} {/if}
            </label>
            <br>
            <input type="radio" id="checkout" name="payment_element" value='0' class="child" {if !$payment_element}checked{/if}>
            <label for="checkout">
                {if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Redirect to Stripe' d='Modules.Stripeofficial.Configuration'} {else} {l s='Redirect to Stripe' mod='stripe_official'} {/if}
            </label>
            <p>
                {if isset($use_new_ps_translation) && $use_new_ps_translation}
                    {{l s='*Additional payment methods besides cards can be activated in your [a @href1@]Stripe Dashboard[/a].' d='Modules.Stripeofficial.Configuration'}|stripelreplace:['@href1@' => {'https://dashboard.stripe.com/dashboard'}, '@target@' => {'target="blank"'}]}
                {else}
                    {{l s='*Additional payment methods besides cards can be activated in your [a @href1@]Stripe Dashboard[/a].' mod='stripe_official'}|stripelreplace:['@href1@' => {'https://dashboard.stripe.com/dashboard'}, '@target@' => {'target="blank"'}]}
                {/if}
            </p>
            <div id="layout" class="button-spacing">
              <label>{l s='Payment Form Layout' mod='stripe_official'}</label>
              <select name="stripe_layout" id="layout">
                <option value="accordion" {if $stripe_layout == 'accordion'}selected{/if}>{l s='Accordion without radio buttons' mod='stripe_official'}</option>
                <option value="tabs" {if $stripe_layout == 'tabs'}selected{/if}>{l s='Tabs' mod='stripe_official'}</option>
                <option value="radio" {if $stripe_layout == 'radio'}selected{/if}>{l s='Accordion with radio buttons'  mod='stripe_official'}</option>
              </select>
            </div>
            <div id="position" class="button-spacing">
              <label>{l s='Payment Form Position' mod='stripe_official'}</label>
              <select name="stripe_position" id="position">
                <option value="top" {if $stripe_position == 'top'}selected{/if}>{l s='On top of the Prestashop payment methods'  mod='stripe_official'}</option>
                <option value="bottom" {if $stripe_position == 'bottom'}selected{/if}>{l s='At the bottom of the Prestashop payment methods'  mod='stripe_official'}</option>
                <option value="middle" {if $stripe_position == 'middle'}selected{/if}>{l s='With the Prestashop payment methods'  mod='stripe_official'}</option>
              </select>
            </div>
            <div id="theme" class="button-spacing">
              <label>{if $use_new_ps_translation} {l s='Select a theme for the integrated payment form' d='Modules.Stripeofficial.Configuration'} {else} {l s='Select a theme for the integrated payment form' mod='stripe_official'} {/if} </label>
              <select name="stripe_theme" id="theme">
                <option value="stripe" {if $stripe_theme == 'stripe'}selected{/if}>Stripe</option>
                <option value="flat" {if $stripe_theme == 'flat'}selected{/if}>Flat</option>
                <option value="night" {if $stripe_theme == 'night'}selected{/if}>Night</option>
                <option value="none" {if $stripe_theme == 'none'}selected{/if}>None</option>
              </select>
            </div>
          </div>
        </div>

        <div class="form-group">
          <input type="checkbox" id="express_checkout" name="express_checkout" class="child button-spacing" {if $express_checkout}checked{/if}>
          <label for="express_checkout">
              {if $use_new_ps_translation} {l s='Enable Express Checkout' d='Modules.Stripeofficial.Configuration'} {else} {l s='Enable Express Checkout' mod='stripe_official'} {/if}
          </label>
          <div class="left20">
            <div id="locations" class="spacing">
              <label>{if $use_new_ps_translation} {l s='Select the locations for the Express Checkout element' d='Modules.Stripeofficial.Configuration'} {else} {l s='Select the locations for the Express Checkout element' mod='stripe_official'} {/if} </label>
              <select name="stripe_locations[]" id="locations" class="form-multi-select bootstrap" multiple data-coreui-search="true">
                <option value="product" {if in_array('product', $stripe_locations)}selected{/if}>Product Page</option>
                <option value="cart" {if in_array('cart', $stripe_locations)}selected{/if}>Shopping Cart Page</option>
              </select>
            </div>
            <div id="stripe_apple_pay" class="spacing">
              <label>Apple Pay <img src="views/img/apple_pay.png" alt=""> </label>
              <div class="left20">
                <div id="apple_pay_button_theme"  class="button-spacing">
                  <label>{if $use_new_ps_translation} {l s='Button theme' d='Modules.Stripeofficial.Configuration'} {else} {l s='Button theme' mod='stripe_official'} {/if}</label>
                  <select name="apple_pay_button_theme" id="apple_pay_button_theme">
                    <option value="black" {if $apple_pay_button_theme == 'black'}selected{/if}>Black</option>
                    <option value="white" {if $apple_pay_button_theme == 'white'}selected{/if}>White</option>
                    <option value="white-outline" {if $apple_pay_button_theme == 'white-outline'}selected{/if}>White outline</option>
                  </select>
                </div>
                <div id="apple_pay_button_type">
                  <label>{if $use_new_ps_translation} {l s='Button type' d='Modules.Stripeofficial.Configuration'} {else} {l s='Button type' mod='stripe_official'} {/if}</label>
                  <select name="apple_pay_button_type" id="apple_pay_button_type">
                    <option value="plain" {if $apple_pay_button_type == 'plain'}selected{/if}>Plain</option>
                    <option value="buy" {if $apple_pay_button_type == 'buy'}selected{/if}>Buy with</option>
                    <option value="order" {if $apple_pay_button_type == 'order'}selected{/if}>Order with</option>
                    <option value="add-money" {if $apple_pay_button_type == 'add-money'}selected{/if}>Add Money with</option>
                    <option value="book" {if $apple_pay_button_type == 'book'}selected{/if}>Book with</option>
                    <option value="check-out" {if $apple_pay_button_type == 'check-out'}selected{/if}>Check Out with</option>
                    <option value="continue" {if $apple_pay_button_type == 'continue'}selected{/if}>Continue with</option>
                    <option value="contribute" {if $apple_pay_button_type == 'contribute'}selected{/if}>Contribute with</option>
                    <option value="donate" {if $apple_pay_button_type == 'donate'}selected{/if}>Donate</option>
                    <option value="reload" {if $apple_pay_button_type == 'reload'}selected{/if}>Reload with</option>
                    <option value="rent" {if $apple_pay_button_type == 'rent'}selected{/if}>Rent with</option>
                    <option value="subscribe" {if $apple_pay_button_type == 'subscribe'}selected{/if}>Subscribe with</option>
                    <option value="support" {if $apple_pay_button_type == 'support'}selected{/if}>Support with</option>
                    <option value="tip" {if $apple_pay_button_type == 'tip'}selected{/if}>Tip with</option>
                    <option value="top-up	" {if $apple_pay_button_type == 'top-up	'}selected{/if}>Top Up with</option>
                  </select>
                </div>
              </div>
            </div>
            <div id="stripe_google_pay" class="spacing">
              <label>Google Pay <img src="views/img/google_pay.png" alt=""></label>
              <div class="left20">
                <div id="google_pay_button_theme" class="button-spacing">
                  <label>{if $use_new_ps_translation} {l s='Button theme' d='Modules.Stripeofficial.Configuration'} {else} {l s='Button theme' mod='stripe_official'} {/if}</label>
                  <select name="google_pay_button_theme" id="google_pay_button_theme">
                    <option value="black" {if $google_pay_button_theme == 'black'}selected{/if}>Black</option>
                    <option value="white" {if $google_pay_button_theme == 'white'}selected{/if}>White</option>
                  </select>
                </div>
                <div id="google_pay_button_type">
                  <label>{if $use_new_ps_translation} {l s='Button type' d='Modules.Stripeofficial.Configuration'} {else} {l s='Button type' mod='stripe_official'} {/if}</label>
                  <select name="google_pay_button_type" id="google_pay_button_type">
                    <option value="plain" {if $google_pay_button_type == 'plain'}selected{/if}>Plain</option>
                    <option value="buy" {if $google_pay_button_type == 'buy'}selected{/if}>Buy with</option>
                    <option value="pay" {if $google_pay_button_type == 'pay'}selected{/if}>Pay with</option>
                    <option value="order" {if $google_pay_button_type == 'order'}selected{/if}>Order with</option>
                    <option value="book" {if $google_pay_button_type == 'book'}selected{/if}>Book with</option>
                    <option value="checkout" {if $google_pay_button_type == 'checkout'}selected{/if}>Check Out with</option>
                    <option value="donate" {if $google_pay_button_type == 'donate'}selected{/if}>Donate with</option>
                    <option value="subscribe" {if $google_pay_button_type == 'subscribe'}selected{/if}>Subscribe with</option>
                  </select>
                </div>
              </div>
            </div>
            <div id="stripe_pay_pal" class="spacing">
              <label>PayPal <img src="./views/img/paypal.png" alt=""></label>
              <div class="left20">
                <div id="pay_pal_button_theme" class="button-spacing">
                  <label>{if $use_new_ps_translation} {l s='Button theme' d='Modules.Stripeofficial.Configuration'} {else} {l s='Button theme' mod='stripe_official'} {/if}</label>
                  <select name="pay_pal_button_theme" id="pay_pal_button_theme">
                    <option value="black" {if $pay_pal_button_theme == 'black'}selected{/if}>Black</option>
                    <option value="white" {if $pay_pal_button_theme == 'white'}selected{/if}>White</option>
                    <option value="gold" {if $pay_pal_button_theme == 'gold'}selected{/if}>Gold</option>
                    <option value="silver" {if $pay_pal_button_theme == 'silver'}selected{/if}>Silver</option>
                    <option value="blue" {if $pay_pal_button_theme == 'blue'}selected{/if}>Blue</option>
                  </select>
                </div>
                <div id="pay_pal_button_type">
                  <label>{if $use_new_ps_translation} {l s='Button type' d='Modules.Stripeofficial.Configuration'} {else} {l s='Button type' mod='stripe_official'} {/if}</label>
                  <select name="pay_pal_button_type" id="pay_pal_button_type">
                    <option value="paypal" {if $pay_pal_button_type == 'paypal'}selected{/if}>Paypal</option>
                    <option value="checkout" {if $pay_pal_button_type == 'checkout'}selected{/if}>Checkout</option>
                    <option value="buynow" {if $pay_pal_button_type == 'buynow'}selected{/if}>Buy Now</option>
                    <option value="pay" {if $pay_pal_button_type == 'pay'}selected{/if}>Pay with</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="form-group">
          <input type="checkbox" id="catchandauthorize" name="catchandauthorize" {if $catchandauthorize}checked="checked"{/if}/>
          <label for="catchandauthorize">
              {if isset($use_new_ps_translation) && $use_new_ps_translation}
                  {l s='Enable separate authorization and capture. If enabled, Stripe will place a hold on the card for the amount of the order during checkout. That authorization will be captured and the money settled to your account when the order transitions to the status of your choice.' d='Modules.Stripeofficial.Configuration'}
              {else}
                  {l s='Enable separate authorization and capture. If enabled, Stripe will place a hold on the card for the amount of the order during checkout. That authorization will be captured and the money settled to your account when the order transitions to the status of your choice.' mod='stripe_official'}
              {/if}
          </label>
          <p class="left20">
            <b>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Warning: you have 7 calendar days to capture the authorization before it expires and the hold on the card is released.' d='Modules.Stripeofficial.Configuration'} {else} {l s='Warning: you have 7 calendar days to capture the authorization before it expires and the hold on the card is released.' mod='stripe_official'} {/if} </b>
          </p>
          <span class="left20">{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Capture the payment when transitioning to the following order statuses.' d='Modules.Stripeofficial.Configuration'} {else} {l s='Capture the payment when transitioning to the following order statuses.' mod='stripe_official'} {/if} </span>
          <div id="status_restrictions" class="left20">
            <br />
            <table class="table">
              <tr>
                <td class="col-md-6">
                  <p>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Your status' d='Modules.Stripeofficial.Configuration'} {else} {l s='Your status' mod='stripe_official'} {/if} </p>
                  <select id="order_status_select_1" class="input-large child" multiple>
                      {foreach from=$orderStatus.unselected item='orderState'}
                        <option value="{$orderState.id_order_state|intval}">{$orderState.name|escape}</option>
                      {/foreach}
                  </select>
                  <a id="order_status_select_add" class="btn btn-default btn-block clearfix" >{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Add' d='Modules.Stripeofficial.Configuration'} {else} {l s='Add' mod='stripe_official'} {/if}  <i class="icon-arrow-right"></i></a>
                </td>
                <td class="col-md-6">
                  <p>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Catch status' d='Modules.Stripeofficial.Configuration'} {else} {l s='Catch status' mod='stripe_official'} {/if} </p>
                  <select id="order_status_select_2" class="input-large child" multiple>
                      {foreach from=$orderStatus.selected item='orderState'}
                        <option value="{$orderState.id_order_state|intval}">{$orderState.name|escape}</option>
                      {/foreach}
                  </select>
                  <a id="order_status_select_remove" class="btn btn-default btn-block clearfix"><i class="icon-arrow-left"></i> {if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Remove' d='Modules.Stripeofficial.Configuration'} {else} {l s='Remove' mod='stripe_official'} {/if}  </a>
                </td>
              </tr>
            </table>
          </div>

          <div class="left20">
            <p>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Transition to the following order status if the authorization expires before being captured.' d='Modules.Stripeofficial.Configuration'} {else} {l s='Transition to the following order status if the authorization expires before being captured.' mod='stripe_official'} {/if} </p>
            <select name="capture_expired" id="capture_expired" class="child">
              <option value="0">{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Select a status' d='Modules.Stripeofficial.Configuration'} {else} {l s='Select a status' mod='stripe_official'} {/if} </option>
                {foreach from=$allOrderStatus item=status}
                  <option value="{$status.id_order_state|intval}" {if isset($captureExpire) && $captureExpire == $status.id_order_state}selected="selected"{/if}>{$status.name|escape}</option>
                {/foreach}
            </select>
          </div>
        </div>
        <div class="form-group">
          <p><b>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Payment flow' d='Modules.Stripeofficial.Configuration'} {else} {l s='Payment flow' mod='stripe_official'} {/if} </b></p>
          <div class="left20">
            <input type="radio" id="order_new" name="stripe_order_flow" value='0' class="child" {if !$stripe_order_flow}checked{/if}>
            <label for="order_new">
              {if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Create order after payment is initiated' d='Modules.Stripeofficial.Configuration'} {else} {l s='Create order after payment is initiated' mod='stripe_official'} {/if}
            </label>
            <br>
            <input type="radio" id="order_legacy" name="stripe_order_flow" value='1' class="child" {if $stripe_order_flow}checked{/if}>
            <label for="order_legacy">
              {if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Create order after payment is confirmed (legacy, not recommended)' d='Modules.Stripeofficial.Configuration'} {else} {l s='Create order after payment is confirmed (legacy, not recommended)' mod='stripe_official'} {/if}
            </label>
        </div>

      </div>

      <div class="form-group">
        <p><b>{if $use_new_ps_translation} {l s='Save payment method at customer level' d='Modules.Stripeofficial.Configuration'} {else} {l s='Save payment method at customer level' mod='stripe_official'} {/if} </b></p>
        <div class="button-spacing">
          <select name="save_payment_method" id="save_payment_method">
            <option value="off" {if $save_payment_method == 'off'}selected{/if}>
              {if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Off' d='Modules.Stripeofficial.Configuration'} {else} {l s='Off' mod='stripe_official'} {/if}
            </option>
            <option value="on_session" {if $save_payment_method == 'on_session'}selected{/if}>
              {if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='On session' d='Modules.Stripeofficial.Configuration'} {else} {l s='On session' mod='stripe_official'} {/if}
            </option>
            <option value="off_session" {if $save_payment_method == 'off_session'}selected{/if}>
              {if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Off session' d='Modules.Stripeofficial.Configuration'} {else} {l s='Off session' mod='stripe_official'} {/if}
            </option>
          </select>
        </div>
      </div>
    </div>
    <div class="panel-footer">
      <button type="submit" value="1" id="configuration_form_submit_btn" name="submit_login" class="btn btn-default pull-right button">
        <i class="process-icon-save"></i>
          {if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Save' d='Modules.Stripeofficial.Configuration'} {else} {l s='Save' mod='stripe_official'} {/if}
      </button>
    </div>
  </div>
</form>
