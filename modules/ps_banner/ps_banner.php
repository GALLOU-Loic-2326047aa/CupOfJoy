<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

require_once __DIR__ .'/classes/PsBannerItem.php';

class Ps_Banner extends Module implements WidgetInterface
{
    /**
     * @var string Name of the module running on PS 1.6.x. Used for data migration.
     */
    const PS_16_EQUIVALENT_MODULE = 'blockbanner';

    private $templateFile;

    public function __construct()
    {
        $this->name = 'ps_banner';
        $this->tab = 'front_office_features';
        $this->version = '2.1.2';
        $this->author = 'PrestaShop';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Banner', [], 'Modules.Banner.Admin');
        $this->description = $this->trans('Add a banner to the homepage of your store to highlight your sales and new products in a visual and friendly way.', [], 'Modules.Banner.Admin');

        $this->ps_versions_compliancy = ['min' => '1.7.1.0', 'max' => _PS_VERSION_];

        $this->templateFile = 'module:ps_banner/ps_banner.tpl';

        $bannerId = 0;
    }

    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . 'ps_banner_item` (
        `id_ps_banner_item` INT NOT NULL AUTO_INCREMENT,
        `image` VARCHAR(255),
        `link` VARCHAR(255),
        `description` VARCHAR(255),
        PRIMARY KEY (`id_ps_banner_item`)
        ) ENGINE='. _MYSQL_ENGINE_ . 'DEFAULT CHARSET=utf8;';

        $createTable = Db::getInstance()->execute($sql);

        if (!$createTable) {
            return false;
        }

        return parent::install() &&
            $this->registerHook('actionObjectLanguageAddAfter') &&
            $this->installFixtures() &&
            $this->uninstallPrestaShop16Module() &&
            $this->disableDevice(Context::DEVICE_MOBILE);
    }

    /**
     * Migrate data from 1.6 equivalent module (if applicable), then uninstall
     */
    public function uninstallPrestaShop16Module()
    {
        if (!Module::isInstalled(self::PS_16_EQUIVALENT_MODULE)) {
            return true;
        }

        // Data migration
        Configuration::updateValue('BANNER_IMG', Configuration::getInt('BLOCKBANNER_IMG'));
        Configuration::updateValue('BANNER_LINK', Configuration::getInt('BLOCKBANNER_LINK'));
        Configuration::updateValue('BANNER_DESC', Configuration::getInt('BLOCKBANNER_DESC'));

        $oldModule = Module::getInstanceByName(self::PS_16_EQUIVALENT_MODULE);
        if ($oldModule) {
            $oldModule->uninstall();
        }

        return true;
    }

    public function hookActionObjectLanguageAddAfter($params)
    {
        return $this->installFixture((int) $params['object']->id, Configuration::get('BANNER_IMG', (int) Configuration::get('PS_LANG_DEFAULT')));
    }

    protected function installFixtures()
    {
        $languages = Language::getLanguages(false);

        foreach ($languages as $lang) {
            $this->installFixture((int) $lang['id_lang'], 'sale70.png');
        }

        return true;
    }

    protected function installFixture($id_lang, $image = null)
    {
        $values['BANNER_IMG'][(int) $id_lang] = $image;
        $values['BANNER_LINK'][(int) $id_lang] = '';
        $values['BANNER_DESC'][(int) $id_lang] = '';

        Configuration::updateValue('BANNER_IMG', $values['BANNER_IMG']);
        Configuration::updateValue('BANNER_LINK', $values['BANNER_LINK']);
        Configuration::updateValue('BANNER_DESC', $values['BANNER_DESC']);
    }

    public function uninstall()
    {
        // Suppression de la table personnalisée lors de la désinstallation
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ps_banner_item`';
        Db::getInstance()->execute($sql);

        Configuration::deleteByName('BANNER_IMG');
        Configuration::deleteByName('BANNER_LINK');
        Configuration::deleteByName('BANNER_DESC');

        return parent::uninstall();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitStoreConf')) {
            $languages = Language::getLanguages(false);
            $values = [];
            $update_images_values = false;

            foreach ($languages as $lang) {
                if (isset($_FILES['BANNER_IMG_' . $lang['id_lang']])
                    && isset($_FILES['BANNER_IMG_' . $lang['id_lang']]['tmp_name'])
                    && !empty($_FILES['BANNER_IMG_' . $lang['id_lang']]['tmp_name'])) {
                    if ($error = ImageManager::validateUpload($_FILES['BANNER_IMG_' . $lang['id_lang']], 4000000)) {
                        return $this->displayError($error);
                    } else {
                        $ext = substr($_FILES['BANNER_IMG_' . $lang['id_lang']]['name'], strrpos($_FILES['BANNER_IMG_' . $lang['id_lang']]['name'], '.') + 1);
                        $file_name = md5($_FILES['BANNER_IMG_' . $lang['id_lang']]['name']) . '.' . $ext;

                        if (!move_uploaded_file($_FILES['BANNER_IMG_' . $lang['id_lang']]['tmp_name'], dirname(__FILE__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $file_name)) {
                            return $this->displayError($this->trans('An error occurred while attempting to upload the file.', [], 'Admin.Notifications.Error'));
                        } else {
                            if (Configuration::hasContext('BANNER_IMG', $lang['id_lang'], Shop::getContext())
                                && Configuration::get('BANNER_IMG', $lang['id_lang']) != $file_name) {
                                @unlink(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . Configuration::get('BANNER_IMG', $lang['id_lang']));
                            }

                            $values['BANNER_IMG'][$lang['id_lang']] = $file_name;
                        }
                    }

                    $update_images_values = true;
                }

                $values['BANNER_LINK'][$lang['id_lang']] = Tools::getValue('BANNER_LINK_' . $lang['id_lang']);
                $values['BANNER_DESC'][$lang['id_lang']] = Tools::getValue('BANNER_DESC_' . $lang['id_lang']);
            }

            if ($update_images_values && isset($values['BANNER_IMG'])) {
                Configuration::updateValue('BANNER_IMG', $values['BANNER_IMG']);
            }

            Configuration::updateValue('BANNER_LINK', $values['BANNER_LINK']);
            Configuration::updateValue('BANNER_DESC', $values['BANNER_DESC']);

            $this->_clearCache($this->templateFile);

            return $this->displayConfirmation($this->trans('The settings have been updated.', [], 'Admin.Notifications.Success'));
        }

        return '';
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminPsBanner'));
    }

    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Settings', [], 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'file_lang',
                        'label' => $this->trans('Banner image', [], 'Modules.Banner.Admin'),
                        'name' => 'BANNER_IMG',
                        'desc' => $this->trans('Upload an image for your top banner. The recommended dimensions are 1110 x 214px if you are using the default theme.', [], 'Modules.Banner.Admin'),
                        'lang' => true,
                    ],
                    [
                        'type' => 'text',
                        'lang' => true,
                        'label' => $this->trans('Banner Link', [], 'Modules.Banner.Admin'),
                        'name' => 'BANNER_LINK',
                        'desc' => $this->trans('Enter the link associated to your banner. When clicking on the banner, the link opens in the same window. If no link is entered, it redirects to the homepage.', [], 'Modules.Banner.Admin'),
                    ],
                    [
                        'type' => 'text',
                        'lang' => true,
                        'label' => $this->trans('Banner description', [], 'Modules.Banner.Admin'),
                        'name' => 'BANNER_DESC',
                        'desc' => $this->trans('Please enter a short but meaningful description for the banner.', [], 'Modules.Banner.Admin'),
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitStoreConf';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'uri' => $this->getPathUri(),
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigFieldsValues()
    {
        $languages = Language::getLanguages(false);
        $fields = [];

        foreach ($languages as $lang) {
            $fields['BANNER_IMG'][$lang['id_lang']] = Tools::getValue('BANNER_IMG_' . $lang['id_lang'], Configuration::get('BANNER_IMG', $lang['id_lang']));
            $fields['BANNER_LINK'][$lang['id_lang']] = Tools::getValue('BANNER_LINK_' . $lang['id_lang'], Configuration::get('BANNER_LINK', $lang['id_lang']));
            $fields['BANNER_DESC'][$lang['id_lang']] = Tools::getValue('BANNER_DESC_' . $lang['id_lang'], Configuration::get('BANNER_DESC', $lang['id_lang']));
        }

        return $fields;
    }

    public function renderWidget($hookName, array $params)
    {
        $this->smarty->assign($this->getWidgetVariables($hookName, $params));

        return $this->fetch($this->templateFile, $this->getCacheId('ps_banner'));
    }

    public function getWidgetVariables($hookName, array $params)
    {
        $this->bannerId = isset($params['banner_id']) ? (int) $params['banner_id'] : null;

        if ($this->bannerId) {
            $banner = $this->getBannerById($this->bannerId);
        } else {
            // Cas par défaut, chargement de la première bannière ou une bannière "par défaut"
            $banners = $this->getBanners();
            $banner = !empty($banners) ? $banners[0] : null;
        }

        return [
            'banner' => $banner,
        ];
    }

    public function getBannerById($bannerId){
        $banner = PsBannerItem::getBannerById($bannerId);
        return $banner;
    }

    public function getBanners(){
        $banners = PsBannerItem::getBanners();
        return $banners;
    }

    private function updateUrl($link)
    {
        if (substr($link, 0, 7) !== 'http://' && substr($link, 0, 8) !== 'https://') {
            $link = 'http://' . $link;
        }

        return $link;
    }
}
