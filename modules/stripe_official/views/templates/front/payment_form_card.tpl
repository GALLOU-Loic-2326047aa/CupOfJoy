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
<form id="js-stripe-payment-form">
  <div id="js-stripe-payment-element">
    <!--Stripe.js injects the Payment Element-->
  </div>
</form>

<!-- Spinner wrapper -->
<div id="stripeSpinnerWrapper" class="stripe-spinner-wrapper" style="display: none;">
  <div class="stripe-spinner"></div>
  <span class="processing-order-text">{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='Processing your order...' d='Modules.Stripeofficial.PaymentFormCard'} {else} {l s='Processing your order...' mod='stripe_official'} {/if}</span>
</div>
<style>
  .stripe-spinner-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
  }

  .stripe-spinner {
    width: 20px;
    height: 20px;
    border: 3px solid #00bcd4; /* light blue */
    border-top: 2px solid transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
  }

  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }

  .processing-order-text {
    color: #00bcd4;
    font-size: 20px;
  }
</style>
