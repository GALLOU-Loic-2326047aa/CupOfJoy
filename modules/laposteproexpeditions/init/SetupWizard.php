<?php
/**
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
 */
/**
 * Contains code for the setup wizard class.
 */

namespace LaPoste\LaPosteProExpeditionsPrestashop\Init;

if (!defined('_PS_VERSION_')) {
    exit;
}

use LaPoste\LaPosteProExpeditionsPrestashop\Controllers\Misc\NoticeController;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\AuthUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ConfigurationUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ShopUtil;

/**
 * Setup wizard class.
 *
 * Display setup wizard if needed.
 *
 * @class       SetupWizard
 */
class SetupWizard
{
    /**
     * Construct function.
     *
     * @void
     */
    public function __construct()
    {
        $shops = ShopUtil::getShops();
        foreach ($shops as $shop) {
            if (AuthUtil::isPluginPaired($shop['id_shop_group'], $shop['id_shop'])) {
                if (NoticeController::hasNotice(
                    NoticeController::$setupWizard,
                    $shop['id_shop_group'],
                    $shop['id_shop']
                )) {
                    NoticeController::removeNotice(
                        NoticeController::$setupWizard,
                        $shop['id_shop_group'],
                        $shop['id_shop']
                    );
                }
                if (ConfigurationUtil::hasConfiguration($shop['id_shop_group'], $shop['id_shop'])
                    && NoticeController::hasNotice(
                        NoticeController::$configurationFailure,
                        $shop['id_shop_group'],
                        $shop['id_shop']
                    )) {
                    NoticeController::removeNotice(
                        NoticeController::$configurationFailure,
                        $shop['id_shop_group'],
                        $shop['id_shop']
                    );
                } elseif (!ConfigurationUtil::hasConfiguration($shop['id_shop_group'], $shop['id_shop'])
                    && !NoticeController::hasNotice(
                        NoticeController::$configurationFailure,
                        $shop['id_shop_group'],
                        $shop['id_shop']
                    )) {
                    NoticeController::addNotice(
                        NoticeController::$configurationFailure,
                        $shop['id_shop_group'],
                        $shop['id_shop']
                    );
                }
            } elseif (!NoticeController::hasNotice(
                NoticeController::$setupWizard,
                $shop['id_shop_group'],
                $shop['id_shop']
            )) {
                NoticeController::addNotice(NoticeController::$setupWizard, $shop['id_shop_group'], $shop['id_shop']);
            }
        }
    }
}
