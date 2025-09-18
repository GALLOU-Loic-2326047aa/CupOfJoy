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
 *}<div class="module_warning alert alert-warning">
    <h4>{l s='Welcome to %1$s!' mod='laposteproexpeditions' sprintf=[$companyName]}</h4>
    <p>
        {l s='The adventure begins in a few clicks' mod='laposteproexpeditions'}
    </p>   
    <p>
        <a href="{$onboardingLink|escape:'htmlall':'UTF-8'}"
           class="btn btn-warning btn-warning-reverse"
           style="margin-top: 15px;text-decoration: none; color: white;"
           target="_blank">
           {l s='Connect my shop' mod='laposteproexpeditions'}
        </a>
    </p>
</div>
