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
 * Contains code for the custom notice class.
 */

namespace LaPoste\LaPosteProExpeditionsPrestashop\Notice;

use LaPoste\LaPosteProExpeditionsPrestashop\Util\ShopUtil;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Custom notice class.
 *
 * Custom notice where message and status determine display.
 *
 * @class       CustomNotice
 */
class CustomNotice extends AbstractNotice
{
    /**
     * Construct function.
     *
     * @param string $key key for notice
     * @param int $shopGroupId shop group id
     * @param int $shopId shop id
     * @param mixed $args additional args
     *
     * @void
     */
    public function __construct($key, $shopGroupId, $shopId, $args)
    {
        parent::__construct($key, $shopGroupId, $shopId);

        $this->type = 'custom';
        $this->autodestruct = isset($args->autodestruct) ? $args->autodestruct : true;
        $this->template = 'custom';
        $this->controller = null;
        $this->parameters['message'] = isset($args->message) ? $args->message : '';
        $this->parameters['classes'] = $this->getNoticeClasses(isset($args->status) ? $args->status : 'info');
        $this->parameters['shopName'] = ShopUtil::getShopName($this->shopGroupId, $this->shopId);
        $this->parameters['key'] = $key;
        $this->parameters['shopId'] = $this->shopId;
        $this->parameters['shopGroupId'] = $this->shopGroupId;
    }

    /**
     * Get css classes to add to the notice
     *
     * @return string
     */
    private function getNoticeClasses($status)
    {
        $classes = '';
        switch ($status) {
            case 'warning':
                $classes .= 'module_error alert alert-danger';
                break;

            case 'info':
                $classes .= 'module_warning alert alert-warning';
                break;

            case 'success':
                $classes .= 'module_confirmation conf confirm alert alert-success';
                break;

            default:
                break;
        }

        return $classes;
    }
}
