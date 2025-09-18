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
 * Contains code for the abstract notice class.
 */

namespace LaPoste\LaPosteProExpeditionsPrestashop\Notice;

if (!defined('_PS_VERSION_')) {
    exit;
}

use LaPoste\LaPosteProExpeditionsPrestashop\Controllers\Misc\Notice;
use LaPoste\LaPosteProExpeditionsPrestashop\Controllers\Misc\NoticeController;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ShopUtil;

/**
 * Abstract notice class.
 *
 * Base methods for notices.
 *
 * @class       AbstractNotice
 */
abstract class AbstractNotice
{
    /**
     * Plugin's instance.
     *
     * @var \LaPosteProExpeditions
     */
    protected $instance;

    /**
     * Notice key, used for remove method.
     *
     * @var string
     */
    protected $key;

    /**
     * Notice type.
     *
     * @var string
     */
    public $type;

    /**
     * Notice template.
     *
     * @var string
     */
    public $template;

    /**
     * Notice autodestruct.
     *
     * @var bool
     */
    protected $autodestruct;

    /**
     * Notice shop group id.
     *
     * @var int
     */
    protected $shopGroupId;

    /**
     * Notice shop id.
     *
     * @var int
     */
    protected $shopId;

    /**
     * Notice template parameters
     *
     * @var array
     */
    protected $parameters;

    /**
     * Controller the notice should be visible on (null = visible everywhere)
     *
     * @var string|null
     */
    public $controller;

    /**
     * Construct function.
     *
     * @param string $key key for notice
     * @param int $shopGroupId shop group id
     * @param int $shopId shop id
     *
     * @void
     */
    public function __construct($key, $shopGroupId, $shopId)
    {
        $this->key = $key;
        $this->shopGroupId = $shopGroupId;
        $this->shopId = $shopId;
        $this->parameters = [];
    }

    /**
     * Render notice.
     *
     * @void
     */
    public function render()
    {
        $result = '';

        $notice = $this;
        if ($notice->isValid()) {
            $instance = \LaPosteProExpeditions::getInstance();
            $smarty = $instance->getSmarty();

            $smarty->assign($this->parameters);
            $smarty->assign('noticeTemplate', $this->template . '.tpl');

            $smarty->assign(
                'ajaxLink',
                $instance->getContext()->link->getAdminLink('{classPrefix}AdminAjax')
            );
            $smarty->assign('shopName', ShopUtil::getShopName($notice->shopGroupId, $notice->shopId));
            $result = $instance->displayTemplate(
                'admin' . DIRECTORY_SEPARATOR . 'notice' . DIRECTORY_SEPARATOR . 'wrapper.tpl'
            );

            if ($notice->autodestruct) {
                $notice->remove();
            }
        } else {
            $notice->remove();
        }

        return $result;
    }

    /**
     * Remove notice.
     *
     * @void
     */
    public function remove()
    {
        NoticeController::removeNotice($this->key, $this->shopGroupId, $this->shopId);
    }

    /**
     * Check if notice is still valid.
     *
     * @return bool
     */
    public function isValid()
    {
        return true;
    }
}
