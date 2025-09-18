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
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

if (!defined('_PS_VERSION_')) {
    exit;
}

/*
 * Contains code for the shipping method admin controller.
 */
use LaPoste\LaPosteProExpeditionsPrestashop\Controllers\Misc\NoticeController;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\AuthUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ConfigurationUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\EncodeUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\OrderUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ShippingMethodUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ShopUtil;

/**
 * Shipping method admin controller class.
 */
class LaPosteProExpeditionsAdminShippingMethodController extends ModuleAdminController
{
    /**
     * Construct function.
     *
     * @void
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'LaPosteProExpeditionsAdminShippingMethodController';
        parent::__construct();
    }

    /**
     * Controller init.
     *
     * @void
     */
    public function init()
    {
        NoticeController::removeNotice(
            NoticeController::$pairing,
            ShopUtil::$shopGroupId,
            ShopUtil::$shopId
        );

        parent::init();
        if (Tools::isSubmit('submitParcelPointNetworks')) {
            $this->handleParcelPointNetworksForm();
        }
        if (Tools::isSubmit('submitPluginParameters')) {
            $this->handlePluginParametersForm();
        }
        $instance = LaPosteProExpeditions::getInstance();
        $smarty = $instance->getSmarty();
        $smarty->assign('title', 'La Poste Pro Expéditions');
        $smarty->assign('notices', NoticeController::renderNotices($instance->getContext()->controller));
        if (true === ShopUtil::$multistore && null === ShopUtil::$shopId) {
            $this->content = $instance->displayTemplate('admin/multistoreAccessDenied.tpl');

            return;
        } elseif (!AuthUtil::canUsePlugin()) {
            $shopGroupId = ShopUtil::$shopGroupId;
            $shopId = ShopUtil::$shopId;
            $onboardingLink = ConfigurationUtil::getOnboardingLink($shopGroupId, $shopId);
            $helpCenterLink = $instance->getContext()->language->locale == 'fr-FR'
                ? 'https://aide.expeditions-pro.laposte.fr/fr/fr/article/getting-started-bc-prestashop'
                : 'https://aide.expeditions-pro.laposte.fr/fr/en/article/getting-started-bc-prestashop';
            $smarty->assign('onboardingLink', $onboardingLink);
            $smarty->assign('moduleName', $instance->displayName);
            $smarty->assign('companyName', 'La Poste');
            $smarty->assign('pluginName', 'La Poste Pro Expéditions');
            $smarty->assign('helpCenterLink', $helpCenterLink);
            $this->content = $instance->displayTemplate('admin/onboarding.tpl');

            return;
        }

        $parcelPointNetworks = EncodeUtil::decode(
            ConfigurationUtil::get('LP_PP_NETWORKS')
        );
        $smarty->assign('parcelPointNetworks', $parcelPointNetworks);
        $carriers = ShippingMethodUtil::getShippingMethods();
        foreach ((array) $carriers as $c => $carrier) {
            if (file_exists(_PS_SHIP_IMG_DIR_ . (int) $carrier['id_carrier'] . '.jpg')) {
                $carriers[$c]['logo'] = _THEME_SHIP_DIR_ . (int) $carrier['id_carrier'] . '.jpg';
            }
            $carriers[$c]['parcel_point_networks'] = EncodeUtil::decode($carriers[$c]['parcel_point_networks']);
        }
        $smarty->assign('carriers', $carriers);

        $langId = $instance->getContext()->language->id;
        $orderStatuses = OrderUtil::getOrderStatuses($langId);
        $orderPrepared = ConfigurationUtil::getAsInt('LP_ORDER_PREPARED');
        $orderShipped = ConfigurationUtil::getAsInt('LP_ORDER_SHIPPED');
        $orderDelivered = ConfigurationUtil::getAsInt('LP_ORDER_DELIVERED');
        $logging = ConfigurationUtil::getAsInt('LP_LOGGING');

        if (null !== $orderPrepared) {
            $isValidOrderPrepared = false;
            foreach ($orderStatuses as $status) {
                if ($status['id_order_state'] === $orderPrepared) {
                    $isValidOrderPrepared = true;
                }
            }

            if (false === $isValidOrderPrepared) {
                $orderPrepared = null;
                ConfigurationUtil::set('LP_ORDER_PREPARED', $orderPrepared);
            }
        }

        if (null !== $orderShipped) {
            $isValidOrderShipped = false;
            foreach ($orderStatuses as $status) {
                if ($status['id_order_state'] === $orderShipped) {
                    $isValidOrderShipped = true;
                }
            }

            if (false === $isValidOrderShipped) {
                $orderShipped = null;
                ConfigurationUtil::set('LP_ORDER_SHIPPED', $orderShipped);
            }
        }

        if (null !== $orderDelivered) {
            $isValidOrderDelivered = false;
            foreach ($orderStatuses as $status) {
                if ($status['id_order_state'] === $orderDelivered) {
                    $isValidOrderDelivered = true;
                }
            }

            if (false === $isValidOrderDelivered) {
                $orderDelivered = null;
                ConfigurationUtil::set('LP_ORDER_DELIVERED', $orderDelivered);
            }
        }

        $trackingUrlPattern = ConfigurationUtil::getTrackingUrlPattern();
        $helpCenterUrl = ConfigurationUtil::getHelpCenterUrl();
        $shippingRulesUrl = ConfigurationUtil::getShippingRulesUrl();
        $smarty->assign('trackingUrlPattern', str_replace('%s', '@', $trackingUrlPattern));
        $smarty->assign('shippingRulesUrl', $shippingRulesUrl);
        $smarty->assign('helpCenterUrl', $helpCenterUrl);
        $smarty->assign('carriersUrl', $instance->getContext()->link->getAdminLink('AdminCarriers'));
        $smarty->assign('zonesSettingsUrl', $instance->getContext()->link->getAdminLink('AdminZones'));
        $smarty->assign('companyName', 'La Poste');
        $smarty->assign('orderStatuses', $orderStatuses);
        $smarty->assign('orderPrepared', $orderPrepared);
        $smarty->assign('orderShipped', $orderShipped);
        $smarty->assign('orderDelivered', $orderDelivered);
        $smarty->assign('logging', $logging);

        // fix 1.2.7 => 1.2.8 update cache issues
        $smarty->assign('tutoUrl', $helpCenterUrl);

        $this->content = $instance->displayTemplate('admin/configuration/settings.tpl');
    }

    /**
     * Handle parcel point networks form.
     *
     * @void
     */
    private function handleParcelPointNetworksForm()
    {
        $carriers = ShippingMethodUtil::getShippingMethods();
        foreach ((array) $carriers as $carrier) {
            $parcelPointNetworks = Tools::isSubmit('parcelPointNetworks_' . (int) $carrier['id_carrier']) ?
                Tools::getValue('parcelPointNetworks_' . (int) $carrier['id_carrier']) : [];
            ShippingMethodUtil::setSelectedParcelPointNetworks((int) $carrier['id_carrier'], $parcelPointNetworks);
        }
    }

    /**
     * Handle tracking events form.
     *
     * @void
     */
    private function handlePluginParametersForm()
    {
        if (Tools::isSubmit('orderPrepared')) {
            $status = Tools::getValue('orderPrepared');
            if ('' === $status) {
                ConfigurationUtil::set('LP_ORDER_PREPARED', null);
            } else {
                ConfigurationUtil::set('LP_ORDER_PREPARED', (int) $status);
            }
        }

        if (Tools::isSubmit('orderShipped')) {
            $status = Tools::getValue('orderShipped');
            if ('' === $status) {
                ConfigurationUtil::set('LP_ORDER_SHIPPED', null);
            } else {
                ConfigurationUtil::set('LP_ORDER_SHIPPED', (int) $status);
            }
        }

        if (Tools::isSubmit('orderDelivered')) {
            $status = Tools::getValue('orderDelivered');
            if ('' === $status) {
                ConfigurationUtil::set('LP_ORDER_DELIVERED', null);
            } else {
                ConfigurationUtil::set('LP_ORDER_DELIVERED', (int) $status);
            }
        }

        $logging = Tools::isSubmit('logging') && Tools::getValue('logging') === '1';
        ConfigurationUtil::set('LP_LOGGING', $logging);
    }
}
