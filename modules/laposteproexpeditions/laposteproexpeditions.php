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

require_once dirname(__FILE__) . '/autoloader.php';

/**
 * Class LaPosteProExpeditions
 *
 *  Main module class.
 */
class LaPosteProExpeditions extends Module
{
    /**
     * Instance.
     *
     * @var LaPosteProExpeditions
     */
    private static $instance;

    /**
     * File name.
     *
     * @var string
     */
    public $file;

    /**
     * Minimum php version.
     *
     * @var string
     */
    public $minPhpVersion;

    /**
     * Onboarding url.
     *
     * @var string
     */
    public $onboardingUrl;

    /**
     * Construct function.
     *
     * @void
     */
    public function __construct()
    {
        $this->name = 'laposteproexpeditions';
        $this->tab = 'shipping_logistics';
        $this->version = '1.1.0';
        $this->author = 'La Poste';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.5', 'max' => _PS_VERSION_];
        $this->bootstrap = true;
        $this->file = __FILE__;
        $this->module_key = '8d6cdc5758a3aebe3bb2570c4d7a79ff';
        $this::$instance = $this;
        parent::__construct();

        $this->displayName = 'La Poste Pro Expéditions';
        $this->description = $this->l('Your orders are synchronized with your La Poste Pro Expéditions account, where you can automate with shipping rules to generate your shipping labels.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->minPhpVersion = '5.6.0';
        $this->onboardingUrl = 'https://redirections.expeditions-pro.laposte.fr/onboarding';

        LaPoste\LaPosteProExpeditionsPrestashop\Util\ShopUtil::getShopContext();

        if ($this->active) {
            $this->initEnvironmentCheck($this);

            if (false === LaPoste\LaPosteProExpeditionsPrestashop\Util\EnvironmentUtil::checkErrors($this)) {
                $this->initSetupWizard();
                $this->initShopController($this);
                $this->initAdminAjaxController($this);

                if (LaPoste\LaPosteProExpeditionsPrestashop\Util\AuthUtil::canUsePlugin()) {
                    $this->initFrontAjaxController($this);
                    $this->initOrderController($this);
                }
            }
        }

        if (!LaPoste\LaPosteProExpeditionsPrestashop\Util\AuthUtil::isPluginPaired(
            LaPoste\LaPosteProExpeditionsPrestashop\Util\ShopUtil::$shopGroupId,
            LaPoste\LaPosteProExpeditionsPrestashop\Util\ShopUtil::$shopId
        )) {
            $this->updateConfigurationLink();
        }
    }

    /**
     * Get AdminParentShipping tab id
     *
     * @return int|null $id
     */
    private function getAdminParentShippingTabId()
    {
        $className = 'AdminParentShipping';
        $id = null;

        if (method_exists($this, 'get')) {
            $id = $this->getContainer()->get('prestashop.core.admin.tab.repository')->findOneIdByClassName($className);
        } else {
            $id = Tab::getIdFromClassName($className);
        }

        return $id;
    }

    /**
     * Remove all plugin's tabs from a parent tab
     *
     * @param number $parentId id of parent's tab
     */
    private function removeModuleTabs($parentId)
    {
        $tabsRow = Tab::getTabs(false, (int) $parentId);
        foreach ($tabsRow as $tabRow) {
            if (isset($tabRow['id_tab'])) {
                $tab = new Tab((int) $tabRow['id_tab']);
                if ($this->name === $tab->module) {
                    $tab->delete();
                }
            }
        }
    }

    /**
     * Install function.
     *
     * @return bool
     */
    public function install()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        if (!parent::install()
            || !$this->registerHook('displayBackOfficeHeader')
            || !$this->registerHook('header')
            || !$this->registerHook('displayHeader')
            || !$this->registerHook('displayCarrierList')
            || !$this->registerHook('displayAfterCarrier')
            || !$this->registerHook('newOrder')
            || !$this->registerHook('updateCarrier')
            || !$this->registerHook('adminOrder')
            || !$this->registerHook('displayAdminOrder')
            || !$this->registerHook('displayOrderDetail')) {
            echo "Hook install failed\n";

            return false;
        }

        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lp_notices` (
            `id_notice` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_shop_group` int(11) unsigned NULL,
            `id_shop` int(11) unsigned NULL,
            `key` varchar(255) NOT NULL,
            `value` text,
            PRIMARY KEY (`id_notice`),
            CONSTRAINT UC_lp_notices
            UNIQUE (`key`, `id_shop_group`, `id_shop`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8'
        );

        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lp_carrier` (
            `id_lp_carrier` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_carrier` int(10) unsigned NOT NULL,
            `id_shop_group` int(11) unsigned NULL,
            `id_shop` int(11) unsigned NULL,
            `parcel_point_networks` text,
            PRIMARY KEY (`id_lp_carrier`),
            CONSTRAINT UC_lp_carrier
            UNIQUE (`id_carrier`, `id_shop_group`, `id_shop`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8'
        );

        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lp_cart_storage` (
            `id_cart_storage` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_cart` int(10) unsigned NOT NULL,
            `id_shop_group` int(11) unsigned NULL,
            `id_shop` int(11) unsigned NULL,
            `key` varchar(255) NOT NULL,
            `value` mediumtext,
            PRIMARY KEY (`id_cart_storage`),
            CONSTRAINT UC_lp_cart_storage
            UNIQUE (`id_cart`, `id_shop_group`, `id_shop`, `key`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8'
        );

        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lp_order_storage` (
            `id_order_storage` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_order` int(10) unsigned NOT NULL,
            `id_shop_group` int(11) unsigned NULL,
            `id_shop` int(11) unsigned NULL,
            `key` varchar(255) NOT NULL,
            `value` mediumtext,
            PRIMARY KEY (`id_order_storage`),
            CONSTRAINT UC_lp_order_storage
            UNIQUE (`id_order`, `id_shop_group`, `id_shop`, `key`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8'
        );

        $added = $this->initAjaxTab();
        if (false === $added) {
            echo "Invisible tab install failed\n";

            return false;
        }

        $added = $this->initShippingMethodTab();
        if (false === $added) {
            echo "Tab install failed\n";

            return false;
        }

        $this->updateConfigurationLink();

        return true;
    }

    public function initAjaxTab()
    {
        // remove previous tab
        $this->removeModuleTabs(-1);

        // add invisible tab for admin ajax controller
        $tab = new Tab();
        $tab->active = true;
        $tab->class_name = 'LaPosteProExpeditionsAdminAjax';
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Ajax route';
        }
        $tab->id_parent = -1;
        $tab->module = $this->name;

        return $tab->add();
    }

    public function initShippingMethodTab()
    {
        // remove previous tab
        $adminParentShippingTabId = $this->getAdminParentShippingTabId();
        $this->removeModuleTabs($adminParentShippingTabId);

        // add the new tab
        $tab = new Tab();
        $tab->class_name = 'LaPosteProExpeditionsAdminShippingMethod';
        $tab->id_parent = $adminParentShippingTabId;
        $tab->module = $this->name;
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $this->displayName;
        }

        return $tab->add();
    }

    /**
     * Uninstall function.
     *
     * @return bool
     */
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }
        LaPoste\LaPosteProExpeditionsPrestashop\Util\ConfigurationUtil::deleteConfiguration();
        $db = Db::getInstance();
        $db->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'lp_notices`;');
        $db->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'lp_carrier`');
        $db->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'lp_cart_storage`');
        $db->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'lp_order_storage`');
        // phpcs:ignore Generic.Files.LineLength
        $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'configuration` WHERE name like "LP_%";');

        $this->removeModuleTabs($this->getAdminParentShippingTabId());
        $this->removeModuleTabs(-1);

        return true;
    }

    public function reset()
    {
        $this->uninstall();
        $this->install();
    }

    /**
     * Update configuration link.
     */
    public function updateConfigurationLink()
    {
        $url = $this->getContext()->link->getAdminLink('LaPosteProExpeditionsAdminShippingMethod');

        if (null !== $url && '' !== $url) {
            LaPoste\LaPosteProExpeditionsPrestashop\Util\ConfigurationUtil::setConfigurationUrl($url);
        }
    }

    /**
     * Adds configure link to module page.
     */
    public function getContent()
    {
        $link = new Link();
        $shippingMethodConfiguration = $link->getAdminLink('LaPosteProExpeditionsAdminShippingMethod');
        Tools::redirectAdmin($shippingMethodConfiguration);
    }

    /**
     * Get module instance.
     *
     * @return LaPosteProExpeditions
     */
    public static function getInstance()
    {
        return self::$instance;
    }

    /**
     * Add JS from module context
     */
    public function addJs($tag, $pathFromModule, $priority)
    {
        $is_smart_cache_enabled = Configuration::get('PS_JS_THEME_CACHE') === '1';
        $controller = $this->getContext()->controller;
        $use_prestashop_8_method = version_compare(_PS_VERSION_, '8') >= 0
            && method_exists($controller, 'registerJavascript');
        $path = 'modules/' . $this->name . '/' . $pathFromModule;

        if (method_exists($controller, 'registerJavascript')) {
            $controller->registerJavascript($tag, $path, [
                'priority' => $priority,
                'server' => 'local',
                'version' => $is_smart_cache_enabled ? null : '1.1.0',
            ]);
        }
        if (method_exists($controller, 'addJs') && !$use_prestashop_8_method) {
            $controller->addJs($path);
        }
    }

    /**
     * Add CSS from module context
     */
    public function addCss($tag, $pathFromModule, $priority)
    {
        $is_smart_cache_enabled = Configuration::get('PS_CSS_THEME_CACHE') === '1';
        $controller = $this->getContext()->controller;
        $use_prestashop_8_method = version_compare(_PS_VERSION_, '8') >= 0
            && method_exists($controller, 'registerStylesheet');
        $path = 'modules/' . $this->name . '/' . $pathFromModule;

        if (method_exists($controller, 'registerStylesheet')) {
            $controller->registerStylesheet($tag, $path, [
                'priority' => $priority,
                'server' => 'local',
                'version' => $is_smart_cache_enabled ? null : '1.1.0',
            ]);
        }
        if (method_exists($controller, 'addCss') && !$use_prestashop_8_method) {
            $controller->addCss($path);
        }
    }

    /**
     * DisplayBackOfficeHeader hook. Used to display relevant css & js.
     *
     * @void
     */
    public function hookDisplayBackOfficeHeader()
    {
        $controller = get_class($this->getContext()->controller);

        if (LaPoste\LaPosteProExpeditionsPrestashop\Controllers\Misc\NoticeController::hasNotices()) {
            $this->addJs('lp-polyfills', 'views/js/polyfills.min.js', 99);
            $this->addJs('lp-notices', 'views/js/notices.min.js', 100);
            $this->addCss('lp-notices', 'views/css/notices.css', 100);
        }

        if ('AdminOrdersController' === $controller && false !== Tools::getValue('id_order')) {
            $this->addCss('lp-tracking', 'views/css/tracking.css', 100);
        }

        if ('LaPosteProExpeditionsAdminShippingMethodController' === $controller) {
            $this->addCss('lp-tracking', 'views/css/settings.css', 100);
        }
    }

    /**
     * Header hook. Display includes JavaScript for maps.
     *
     * @param mixed $params context values
     *
     * @return string|null html
     */
    public function hookHeader($params)
    {
        if (!LaPoste\LaPosteProExpeditionsPrestashop\Util\AuthUtil::canUsePlugin()) {
            return null;
        }

        return LaPoste\LaPosteProExpeditionsPrestashop\Controllers\Front\ParcelPointController::addScripts();
    }

    /**
     * Display header hook. Display includes JavaScript for maps.
     *
     * @param mixed $params context values
     *
     * @return string|null html
     */
    public function hookDisplayHeader($params)
    {
        if (!LaPoste\LaPosteProExpeditionsPrestashop\Util\AuthUtil::canUsePlugin()) {
            return null;
        }

        return LaPoste\LaPosteProExpeditionsPrestashop\Controllers\Front\ParcelPointController::addScripts();
    }

    /**
     * Prestashop < 1.7. Used to display front-office relay point list.
     *
     * @param array $params Parameters array (cart object, address information)
     *
     * @return string|null html
     */
    public function hookDisplayCarrierList($params)
    {
        if (!LaPoste\LaPosteProExpeditionsPrestashop\Util\AuthUtil::canUsePlugin()) {
            return null;
        }

        return LaPoste\LaPosteProExpeditionsPrestashop\Controllers\Front\ParcelPointController::initPoints($params);
    }

    /**
     * Prestashop > 1.7. Used to display front-office relay point list.
     *
     * @param array $params Parameters array (cart object, address information)
     *
     * @return string|null html
     */
    public function hookDisplayAfterCarrier($params)
    {
        if (!LaPoste\LaPosteProExpeditionsPrestashop\Util\AuthUtil::canUsePlugin()) {
            return null;
        }

        return LaPoste\LaPosteProExpeditionsPrestashop\Controllers\Front\ParcelPointController::initPoints($params);
    }

    /**
     * Order creation hook.
     *
     * @param array $params list of order params
     *
     * @void
     */
    public function hooknewOrder($params)
    {
        if (!LaPoste\LaPosteProExpeditionsPrestashop\Util\AuthUtil::canUsePlugin()) {
            return;
        }

        LaPoste\LaPosteProExpeditionsPrestashop\Controllers\Front\ParcelPointController::orderCreated($params);
    }

    /**
     * Update carrier hook. Used to update carrier id.
     *
     * @param array $params list of params used in the operation
     *
     * @void
     */
    public function hookUpdateCarrier($params)
    {
        $idCarrierOld = (int) $params['id_carrier'];
        $idCarrierNew = (int) $params['carrier']->id;

        $data = ['id_carrier' => $idCarrierNew];
        Db::getInstance()->update(
            'lp_carrier',
            $data,
            'id_carrier = ' . $idCarrierOld,
            0,
            true
        );
    }

    /**
     * Hook no longer used since 1.3.0.
     *
     * @void
     */
    public function hookDisplayAdminAfterHeader()
    {
    }

    /**
     * adminOrder hook. Used to display tracking and parcelpoint in admin orders.
     *
     * @param array $params list of params used in the operation
     *
     * @return string html
     */
    public function hookAdminOrder($params)
    {
        return LaPoste\LaPosteProExpeditionsPrestashop\Controllers\Hook\AdminOrderController::trigger($params);
    }

    /**
     * displayAdminOrder hook. Used to display tracking and parcelpoint in admin orders.
     *
     * @param array $params list of params used in the operation
     *
     * @return string html
     */
    public function hookDisplayAdminOrder($params)
    {
        return LaPoste\LaPosteProExpeditionsPrestashop\Controllers\Hook\AdminOrderController::trigger($params);
    }

    /**
     * displayOrderDetail hook. Used to display parcelpoint in user orders.
     *
     * @param array $params list of params used in the operation
     *
     * @return string html
     */
    public function hookDisplayOrderDetail($params)
    {
        return LaPoste\LaPosteProExpeditionsPrestashop\Controllers\Hook\DisplayOrderDetailController::trigger($params);
    }

    /**
     * Get context.
     *
     * @return Context context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Check PHP version.
     *
     * @param LaPosteProExpeditions $plugin plugin array
     *
     * @return object $object static environment check instance
     */
    public function initEnvironmentCheck($plugin)
    {
        static $object;

        if (null !== $object) {
            return $object;
        }

        $object = new LaPoste\LaPosteProExpeditionsPrestashop\Init\EnvironmentCheck($plugin);

        return $object;
    }

    /**
     * Init setup wizard.
     *
     * @return object $object static setup wizard instance
     */
    public function initSetupWizard()
    {
        static $object;

        if (null !== $object) {
            return $object;
        }

        $object = new LaPoste\LaPosteProExpeditionsPrestashop\Init\SetupWizard();

        return $object;
    }

    /**
     * Init shop controller.
     *
     * @param LaPosteProExpeditions $plugin plugin array
     *
     * @void
     */
    public function initShopController($plugin)
    {
        require_once dirname(__FILE__) . '/controllers/front/shop.php';
    }

    /**
     * Init admin ajax controller.
     *
     * @param LaPosteProExpeditions $plugin plugin array
     *
     * @void
     */
    public function initAdminAjaxController($plugin)
    {
        require_once dirname(__FILE__) . '/controllers/admin/LaPosteProExpeditionsAdminAjaxController.php';
    }

    /**
     * Init front ajax controller.
     *
     * @param LaPosteProExpeditions $plugin plugin array
     *
     * @void
     */
    public function initFrontAjaxController($plugin)
    {
        if (!LaPoste\LaPosteProExpeditionsPrestashop\Util\AuthUtil::canUsePlugin()) {
            return;
        }

        require_once dirname(__FILE__) . '/controllers/front/ajax.php';
    }

    /**
     * Init order controller.
     *
     * @param LaPosteProExpeditions $plugin plugin array
     *
     * @void
     */
    public function initOrderController($plugin)
    {
        if (!LaPoste\LaPosteProExpeditionsPrestashop\Util\AuthUtil::canUsePlugin()) {
            return;
        }

        require_once dirname(__FILE__) . '/controllers/front/order.php';
    }

    /**
     * Get smarty.
     *
     * @return object
     */
    public function getSmarty()
    {
        return $this->getContext()->smarty;
    }

    /**
     * Get current controller.
     *
     * @return object
     */
    public function getCurrentController()
    {
        return $this->getContext()->controller;
    }

    /**
     * Display template.
     *
     * @param string $templatePath path to template from module folder
     *
     * @return string html
     */
    public function displayTemplate($templatePath)
    {
        return $this->display(__FILE__, 'views/templates/' . $templatePath);
    }
}
