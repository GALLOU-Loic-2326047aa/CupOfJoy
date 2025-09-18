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
 * Contains code for the environment check class.
 */

namespace LaPoste\LaPosteProExpeditionsPrestashop\Init;

if (!defined('_PS_VERSION_')) {
    exit;
}

use LaPoste\LaPosteProExpeditionsPrestashop\Controllers\Misc\NoticeController;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\EnvironmentUtil;

/**
 * Environment check class.
 *
 * Display environment warning if needed.
 *
 * @class       EnvironmentCheck
 */
class EnvironmentCheck
{
    /**
     * Construct function.
     *
     * @param \LaPosteProExpeditions $plugin plugin array
     *
     * @void
     */
    public function __construct($plugin)
    {
        $environmentWarning = EnvironmentUtil::checkErrors($plugin);
        if (false !== $environmentWarning) {
            NoticeController::removeAllNotices();
            NoticeController::addNotice(
                NoticeController::$environmentWarning,
                null,
                null,
                [
                    'message' => $environmentWarning,
                ]
            );
        } elseif (NoticeController::hasNotice(NoticeController::$environmentWarning, null, null)) {
            NoticeController::removeNotice(NoticeController::$environmentWarning, null, null);
        }
    }
}
