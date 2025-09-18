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
<input type="hidden" name="stripe_id_product_attribute" id="stripe_product_attribute_info" value="{$id_product_attribute|escape:'htmlall':'UTF-8'}"/>
<input type="hidden" name="stripe_product_quantity" id="stripe_product_quantity" value="{$product_quantity|escape:'htmlall':'UTF-8'}"/>
<input type="hidden" name="stripe_product_out_of_stock" id="stripe_product_out_of_stock" value="{$product.out_of_stock|escape:'htmlall':'UTF-8'}"/>

<!-- Include the Stripe Express Checkout template -->
{include file="module:stripe_official/views/templates/front/express_checkout.tpl"}
