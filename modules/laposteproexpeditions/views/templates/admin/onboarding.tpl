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
 *}{if null !== $notices}
    {html_entity_decode($notices|escape:'htmlall':'UTF-8')}
{/if}
<div class="panel">
  <div class="panel-heading">
    {l s='Congratulations, your %s plugin is installed! You\'re ready to grow your business through great shipping.' mod='laposteproexpeditions' sprintf=[$moduleName]}
  </div>
  <div class="table-responsive-row clearfix">
    <p>{l s='First, [1]connect your shop to %s[/1] (it\'s free!)'
      tags=["<a href=\"{$onboardingLink|escape:'htmlall':'UTF-8'}\" target=\"_blank\">"]
      sprintf=[$pluginName, $pluginName]
      mod='laposteproexpeditions'}</p>
    <p>{l s='If you want to know more, our [1]Help Center[/1] is here for you.'
      tags=["<a href=\"{$helpCenterLink|escape:'htmlall':'UTF-8'}\" target=\"_blank\">"]
      mod='laposteproexpeditions'}</p>

    <p>{l s='Then configure your shipping policy in PrestaShop:' mod='laposteproexpeditions'}</p>
    <ul>
      <li>{l s='Create your shipping methods in Shipping > Carriers' mod='laposteproexpeditions'}</li>
      <li>{l s='If you want to display a parcel point map for a given method, you can set it up in Shipping > %s' mod='laposteproexpeditions' sprintf=[$moduleName]}</li>
      <li>{l s='Associate your order statuses to tracking events in the %s menu' mod='laposteproexpeditions' sprintf=[$moduleName]}</li>
      <li>{l s='Copy the tracking URL provided by %s and add it in your Shipping methods' mod='laposteproexpeditions' sprintf=[$moduleName]}</li>
    </ul>

    <p>{l s='You\'re ready to ship your first orders! Happy shipping with %s.' mod='laposteproexpeditions' sprintf=[$companyName]}</p>
  </div>
</div>
