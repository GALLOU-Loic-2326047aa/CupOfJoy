<?php

use PrestaShop\PrestaShop\Core\Language\LanguageRepository;
use PrestaShop\PrestaShop\Core\Module\ConfigurationInterface;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

if(!defined('_PS_VERSION_')){
    exit;
}

class OfferBlock extends Module implements WidgetInterface
{
    public function __construct()
    {
        $this->name = 'offerBlock';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Mathéo BERTIN';

        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Offer Block', [], 'Modules.OfferBlock.Admin');
        $this->description = $this->trans('Displays a block with special offers.', [], 'Modules.OfferBlock.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.OfferBlock.Admin');

        if(!Configuration::get('OFFERBLOCK_NAME'))
        {
            $this->warning = $this->trans('No name provided.', [], 'Modules.OfferBlock.Admin');
        }

        $this->templateFile = 'module:offerBlock/views/templates/hook/offerblock.tpl';
    }

    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "offer_block` (
        `id_offer_block` int(10) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        `image` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        `product1_id` int(10) unsigned NOT NULL,
        `product2_id` int(10) unsigned NOT NULL,
        `product3_id` int(10) unsigned NOT NULL,
        `product4_id` int(10) unsigned NOT NULL,
        
        PRIMARY KEY (`id_offer_block`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";

        $createTable = DB::getInstance()->execute($sql);

        if(!$createTable)
        {
            return false;
        }

        Configuration::updateValue('id_product_1', 0);
        Configuration::updateValue('id_product_2', 0);
        Configuration::updateValue('id_product_3', 0);
        Configuration::updateValue('id_product_4', 0);

        return parent::install() &&
            $this->registerHook('displayHome');
    }

    public function uninstall()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'offer_block`';
        Db::getInstance()->execute($sql);

        return parent::uninstall() &&
            $this->unregisterHook('displayHome');
    }

    public function getContent()
    {
        $this->postProcess();
        return($this->renderForm());
    }

    public function renderForm()
    {
        // Récupérer la liste des produits contenus dans la BD avec la langue actuelle.
        $products = Db::getInstance()->executeS('SELECT id_product, name 
                                                    FROM '._DB_PREFIX_.'product_lang 
                                                    WHERE id_lang = '.(int)$this->context->language->id);

        // Préparer les options des produits pour les menu déroulant
        $product_options = array_map(fn($product) => ['id' => $product['id_product'], 'name' => $product['name']], $products);

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Settings', [], 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Offer block name.', [], 'Modules.OfferBlock.Admin'),
                        'name' => 'OFFERBLOCK_NAME',
                        'desc' => $this->trans('Name of the offer block.', [], 'Modules.OfferBlock.Admin'),
                        'required' => true,
                        'lang' => true
                    ],
                    [
                        'type' => 'file_lang',
                        'label' => $this->trans('Offer block image', [], 'Modules.OfferBlock.Admin'),
                        'name' => 'OFFERBLOCK_IMG',
                        'desc' => $this->trans('Upload an image for your offer block. It will be displayed to the left of the selected products.', [], 'Modules.OfferBlock.Admin'),
                        'required' => false,
                        'lang' => true
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Product 1', [], 'Modules.OfferBlock.Admin'),
                        'name' => 'id_product_1',
                        'options' => [
                            'query' => $product_options,
                            'id' => 'id',
                            'name' => 'name'
                        ],
                        'desc' => $this->trans('Select which of your products will be shown in the up left corner of the offer block.', [], 'Modules.OfferBlock.Admin'),
                        'required' => true,
                        'lang' => false
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Product 2', [], 'Modules.OfferBlock.Admin'),
                        'name' => 'id_product_2',
                        'options' => [
                            'query' => $product_options,
                            'id' => 'id',
                            'name' => 'name'
                        ],
                        'desc' => $this->trans('Select which of your products will be shown in the down left corner of the offer block.', [], 'Modules.OfferBlock.Admin'),
                        'required' => true,
                        'lang' => false
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Product 3', [], 'Modules.OfferBlock.Admin'),
                        'name' => 'id_product_3',
                        'options' => [
                            'query' => $product_options,
                            'id' => 'id',
                            'name' => 'name'
                        ],
                        'desc' => $this->trans('Select which of your products will be shown in the up right corner of the offer block.', [], 'Modules.OfferBlock.Admin'),
                        'required' => true,
                        'lang' => false
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Product 4', [], 'Modules.OfferBlock.Admin'),
                        'name' => 'id_product_4',
                        'options' => [
                            'query' => $product_options,
                            'id' => 'id',
                            'name' => 'name'
                        ],
                        'desc' => $this->trans('Select which of your products will be shown in the down right corner of the offer block.', [], 'Modules.OfferBlock.Admin'),
                        'required' => true,
                        'lang' => false
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        $helper = new HelperForm();

        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->show_cancel_button = false;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmitOfferBlock';
        $helper->enctype = true;

        $helper->field_values = $this->getConfigFieldsValues();
        $helper->tpl_vars = [
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigFieldsValues()
    {
        $languages = Language::getLanguages(false);
        $name_values = [];
        $img_values = [];

        foreach ($languages as $lang) {
            $name_values[$lang['id_lang']] = Configuration::get('OFFERBLOCK_NAME_' . $lang['id_lang']);
            $img_values[$lang['id_lang']] = Configuration::get('OFFERBLOCK_IMG', $lang['id_lang']);
        }

        return [
            'OFFERBLOCK_NAME' => $name_values,
            'OFFERBLOCK_IMG' => $img_values,
            'id_product_1' => Configuration::get('id_product_1') ?: 0,
            'id_product_2' => Configuration::get('id_product_2') ?: 0,
            'id_product_3' => Configuration::get('id_product_3') ?: 0,
            'id_product_4' => Configuration::get('id_product_4') ?: 0
        ];
    }

    private function postProcess()
    {
        if(Tools::isSubmit('btnSubmitOfferBlock'))
        {
            $languages = Language::getLanguages(false);

            foreach($languages as $lang) {
                $OFFERBLOCK_NAME = Tools::getValue('OFFERBLOCK_NAME', $lang['id_lang']);
                if (empty($OFFERBLOCK_NAME)) {
                    $this->context->controller->errors[] = $this->trans("Please chose a name.");
                    return;
                }
                Configuration::updateValue('OFFERBLOCK_NAME', $OFFERBLOCK_NAME, false, 0, $lang['id_lang']);
            }

            $upload_dir = _PS_MODULE_DIR_ . $this->name . '/img/';
            if(!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            foreach ($languages as $lang) {
                $file_field = 'OFFERBLOCK_IMG_' . $lang['id_lang'];
                if (isset($_FILES[$file_field])
                    && !empty($_FILES[$file_field]['name'])) {
                    if ($error = ImageManager::validateUpload($_FILES[$file_field], 5000000)) {
                        return $this->displayError($error);
                    }
                    $file_name = md5($_FILES[$file_field]['name']) . '.' . pathinfo($_FILES[$file_field]['name'], PATHINFO_EXTENSION);

                    if (!move_uploaded_file($_FILES[$file_field]['tmp_name'], $upload_dir . $file_name)) {
                        return $this->displayError($this->trans('An error occurred while attempting to upload the file.', [], 'Admin.Notifications.Error'));
                    } else {
                        Configuration::updateValue('OFFERBLOCK_IMG', $file_name, false, 0, $lang['id_lang']);
                    }
                }
            }

            $product_ids = [];
            for($i = 1; $i < 5; $i++)
            {
                $product_id = (int)Tools::getValue('id_product_'.$i);
                if($product_id <= 0) {
                    $this->context->controller->errors[] = $this->trans("Please select product $i.");
                    return;
                }
                $product_ids[$i] = $product_id;
                Configuration::updateValue('id_product_'.$i, $product_id);
            }

            $table = 'offer_block';

            $insertData = [
                'name' => pSQL(Tools::getValue('OFFERBLOCK_NAME' . $lang['id_lang'])),
                'image' => pSQL(Tools::getValue('OFFERBLOCK_IMG' . $lang['id_lang']) ?? ''), // vide si aucune image uploadée
                'product1_id' => $product_ids[1],
                'product2_id' => $product_ids[2],
                'product3_id' => $product_ids[3],
                'product4_id' => $product_ids[4],
            ];

            if(Db::getInstance()->insert($table, $insertData)) {
                $this->context->controller->confirmations[] = $this->trans('Offer block saved successfully.', [], 'Modules.OfferBlock.Admin');
            } else {
                $this->context->controller->errors[] = $this->trans('Failed to save offer block in the database.', [], 'Modules.OfferBlock.Admin');
            }
        }
    }

    public function renderWidget($hookName, array $params)
    {
        $this->smarty->assign($this->getWidgetVariables($hookName, $params));

        return $this->fetch($this->templateFile, $this->getCacheId('offerBlock'));
    }

    public function getWidgetVariables($hookName, array $params)
    {
        return([
            'test' => 'test1 var',
            'test2' => 'test2 var'
        ]);
    }
}