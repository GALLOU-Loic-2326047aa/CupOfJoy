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
 *}<div class="{$classes|escape:'htmlall':'UTF-8'}">
    {l s='%s: %s' mod='laposteproexpeditions' sprintf=[$shopName, $message]}
    <p>
        <button class="lp-hide-notice btn btn-secondary"
            data-key="{$key|escape:'htmlall':'UTF-8'}"
            data-shop-group-id="{$shopGroupId|escape:'htmlall':'UTF-8'}"
            data-shop-id="{$shopId|escape:'htmlall':'UTF-8'}">
            {l s='Hide this notice' mod='laposteproexpeditions'}
        </button>
    </p>
</div>
