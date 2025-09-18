{**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    La Poste
 * @copyright 2007-2025 PrestaShop SA / 2024-2025 La Poste
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}<section class="box">
    <h4>{l s='Pick-up location selected for the order' mod='laposteproexpeditions'}</h4>
    <address>
    <p>{$parcelpoint->name|escape:'html':'UTF-8'}<br/>
    {$parcelpoint->address|escape:'html':'UTF-8'}<br/>
    {$parcelpoint->zipcode|escape:'html':'UTF-8'} {$parcelpoint->city|escape:'html':'UTF-8'} {$parcelpoint->country|escape:'html':'UTF-8'}</p>
    {if $hasOpeningHours}
    <h4>{l s='Opening hours' mod='laposteproexpeditions'}</h4>
<pre style="color: inherit; font-size: inherit; margin-top: 10px;">
{foreach $openingHours as $index => $openingHour}
{if $index % 2 === 1}<span style="background-color: #d8d8d8;">{/if}
{$openingHour|escape:'html':'UTF-8'}
{if $index % 2 === 1}</span>{/if}
{/foreach}
</pre>
    {/if}
    </address>
</section>
