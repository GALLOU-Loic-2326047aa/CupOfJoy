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
 * Contains code for the pairing notice class.
 */

namespace LaPoste\LaPosteProExpeditionsPrestashop\Notice;

if (!defined('_PS_VERSION_')) {
    exit;
}

use LaPoste\LaPosteProExpeditionsPrestashop\Util\ShopUtil;

/**
 * Pairing notice class.
 *
 * Successful pairing notice.
 *
 * @class       PairingNotice
 */
class PairingNotice extends AbstractNotice
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

        $pairingSuccess = $args->result;

        $this->type = 'pairing';
        $this->autodestruct = false;
        $this->template = $pairingSuccess ? 'pairingSuccess' : 'pairingFailure';
        $this->controller = null;
        $this->parameters['result'] = $pairingSuccess;

        if ($pairingSuccess) {
            // phpcs:ignore Generic.Files.LineLength
            $this->parameters['adminLink'] = \LaPosteProExpeditions::getInstance()->getContext()->link->getAdminLink('LaPosteProExpeditionsAdminShippingMethod');
        } else {
            $this->parameters['shopName'] = ShopUtil::getShopName($this->shopGroupId, $this->shopId);
            $this->parameters['companyName'] = 'La Poste';
        }
    }
}
