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
{if $isSaveCard }
  {if $prestashop_version == '1.7'}
    <a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" href="{$link->getModuleLink('stripe_official', 'stripeCards')|escape:'html':'UTF-8'}" title="{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='My cards' d='Modules.Stripeofficial.Myaccountstripecards'} {else} {l s='My cards' mod='stripe_official'} {/if}">
          <span class="link-item">
              <i class="material-icons md-36">payment</i>
              {if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='My cards' d='Modules.Stripeofficial.Myaccountstripecards'} {else} {l s='My cards' mod='stripe_official'} {/if}
          </span>
      </a>
  {else}
      <li>
          <a href="{$link->getModuleLink('stripe_official', 'stripeCards')|escape:'html':'UTF-8'}" title="{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='My cards' d='Modules.Stripeofficial.Myaccountstripecards'} {else} {l s='My cards' mod='stripe_official'} {/if}">
              <i class="icon-credit-card"></i>
            <span>{if isset($use_new_ps_translation) && $use_new_ps_translation} {l s='My cards' d='Modules.Stripeofficial.Myaccountstripecards'} {else} {l s='My cards' mod='stripe_official'} {/if}</span>
          </a>
      </li>
  {/if}
{/if}
