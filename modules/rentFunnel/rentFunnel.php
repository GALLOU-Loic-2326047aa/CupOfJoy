<?php

require_once __DIR__ . '/classes/RentFunnelObjectModel.php';

if (!defined('_PS_VERSION_')) exit;

class RentFunnel extends Module
{
    public const HOOKS = [
        'displayProductListFunctionalButtons',
        'displayProductActions',
        'displayNav2',
        'displayHeader',
    ];

    public function __construct()
    {
        $this->name = 'rentFunnel';
        $this->version = '1.0.0';
        $this->description = $this->trans('Handles the process by which customers can order coffee when renting a machine.'
            , []
            , 'Admin.Global');
        $this->author = 'Mathéo BERTIN';

        parent::__construct();

        $this->ps_versions_compliancy = [
            'min' => '1.6',
            'max' => _PS_VERSION_,
        ];
    }

    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "rentFunnel` (
                    `id_rentFunnel` INT(11) NOT NULL AUTO_INCREMENT,
                    `id_category` INT(11) NOT NULL,
                    `name` VARCHAR(255) NOT NULL,
                    `position` INT(11) NOT NULL,
                    `multiselect` BOOLEAN NOT NULL,
                    `skippable` BOOLEAN NOT NULL,
                    PRIMARY KEY (`id_rentFunnel`),
                    UNIQUE KEY `uniq_category` (`id_category`)
                ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        if(!(DB::getInstance()->execute($sql)))
        {
            return false;
        }

        return parent::install()
            && $this->registerHook(static::HOOKS);
    }

    public function uninstall()
    {
        $sql = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "rentFunnel`";

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        return parent::uninstall();
    }

    public function hookDisplayProductListFunctionalButtons()
    {
        return $this->display(__FILE__, 'rentFunnel_functional_buttons.tpl');
    }

    public function hookDisplayProductActions()
    {
        $id_category = $this->context->controller->getCategory()->id;
        $category = new Category($id_category, $this->context->language->id);
        $categoryName = $category->name;

        $rentFunnel = RentFunnelObjectModel::getRentFunnel();
        $categoryList = [];
        Configuration::updateValue("RENTFUNNEL_SELECTED_PRODUCTS", json_encode([]));

        foreach ($rentFunnel as $rentFunnelItem)
        {
            $categoryList[] = $rentFunnelItem;
        }
        $firstCategoryName = $categoryList[0]['name'];

        Configuration::updateValue("RENTFUNNEL_CATEGORYLIST", json_encode($categoryList));
        $this->context->smarty->assign('categoryList', $categoryList);

        if ($categoryName == $firstCategoryName) {
            return $this->display(__FILE__, 'rentFunnel_product_actions.tpl');
        }

        return false;
    }

    public function hookDisplayNav2($params)
    {
        $current_page = $this->context->controller->getPageName();
        if($current_page == 'module-rentFunnel-chooseProductSimple'
            || $current_page == 'module-rentFunnel-chooseProductMultiple') {
            if ($current_page != 'module-rentFunnel-recap') {
                return $this->display(__FILE__, 'views/templates/hook/header.tpl');
            }
        }
        return false;
    }

    public function hookDisplayHeader($params)
    {
        $this->context->controller->addCSS($this->_path . 'views/css/header.css', 'all');
        $this->context->controller->addCSS($this->_path . "views/css/page.css", "all");
    }

    public function getContent()
    {
        return $this->postProcess() . $this->renderForm();
    }

    private function postProcess()
    {
        if(Tools::isSubmit('submitRentFunnel'))
        {
            $sqlDelete = "DELETE FROM " . _DB_PREFIX_ . "rentFunnel";
            Db::getInstance()->execute($sqlDelete);
            foreach (RentFunnelObjectModel::getCategories() as $category)
            {
                $enabled_key = 'category_' . $category['id_category'];
                $order_key = 'category_order_' . $category['id_category'];
                $multiselect_key = 'category_multiselect_' . $category['id_category'];
                $skippable_key = 'category_skippable_' . $category['id_category'];

                $enabled = Tools::getValue($enabled_key);
                $order = (int)Tools::getValue($order_key, 0);
                $multiselect = Tools::getValue($multiselect_key);
                $skippable = Tools::getValue($skippable_key);

                Configuration::updateValue(strtoupper($enabled_key), (bool)$enabled);
                Configuration::updateValue('RENT_FUNNEL_CATEGORY_ORDER_' . $category['id_category'], $order);
                Configuration::updateValue('RENT_FUNNEL_CATEGORY_MULTISELECT' . $category['id_category'], $multiselect);
                Configuration::updateValue('RENT_FUNNEL_CATEGORY_SKIPPABLE' . $category['id_category'], $skippable);

                if(!$enabled)
                {
                    continue;
                }

                $id_category = (int)$category['id_category'];
                $name = pSQL($category['name']);

                $sqlInsert = "INSERT INTO " . _DB_PREFIX_ . "rentFunnel (id_category, name, position, multiselect, skippable)
                            VALUES($id_category, '$name', $order, $multiselect, $skippable)
                            ON DUPLICATE KEY UPDATE name='$name', position=$order, multiselect=$multiselect, skippable=$skippable";

                Db::getInstance()->execute($sqlInsert);
            }
        }
    }

    private function renderForm()
    {
        $category_inputs = [];
        $categories = RentFunnelObjectModel::getCategories();
        foreach ($categories as $category)
        {
            $id_field = $category['id_category'];
            $name_field = $category['name'];

            $category_inputs[] = [
                'type' => 'html',
                'name' => 'category_title_' . $id_field,
                'html_content' => '<h3 style="margin-top:2em;">' . $this->trans("Catégorie : ", [], 'Admin.Global') . $name_field . '</h3>',
            ];

            $category_inputs[] = [
                'type' => 'switch',
                'label' => $this->trans('Activer ?', [], 'Admin.Global'),
                'name' => 'category_' . $id_field,
                'required' => false,
                'desc' => $this->trans('Choisissez si la catégorie fera partie de l\'entonnoir ou non.'),
                'values' => [
                    [
                        'id' => 'active_on',
                        'value' => 1,
                        'label' => $this->trans('Yes', [], 'Admin.Global')
                    ],
                    [
                        'id' => 'active_off',
                        'value' => 0,
                        'label' => $this->trans('No', [], 'Admin.Global')
                    ]
                ]
            ];

            $category_inputs[] = [
                'type' => 'text',
                'label' => $this->trans('Place de la catégorie dans l\'entonnoir : ', [], 'Admin.Global'),
                'name' => 'category_order_' . $id_field,
                'size' => 3,
                'desc' => $this->trans('Postion de la catégorie dans l\'entonnoir (1 pour la 1ère page, etc...) - La 1ère catégorie sera celle dont les produits permettront d\'accéder à l\'entonnoir', [], 'Admin.Global')
            ];

            $category_inputs[] = [
                'type' => 'radio',
                'label' => $this->trans('Sélection de plusieurs articles possible ?', [], 'Admin.Global'),
                'name' => 'category_multiselect_' . $id_field,
                'required' => true,
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'active_on',
                        'value' => 1,
                        'label' => $this->trans('Yes', [], 'Admin.Global'),
                    ],
                    [
                        'id' => 'active_off',
                        'value' => 0,
                        'label' => $this->trans('No', [], 'Admin.Global')
                    ],
                ],
            ];

            $category_inputs[] = [
                'type' => 'radio',
                'label' => $this->trans('Obligatoire ?', [], 'Admin.Global'),
                'name' => 'category_skippable_' . $id_field,
                'required' => true,
                'desc' => $this->trans('Les clients peuvent-ils passer cette étape ?', [], 'Admin.Global'),
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'active_on',
                        'value' => 0,
                        'label' => $this->trans('Yes', [], 'Admin.Global'),
                    ],
                    [
                        'id' => 'active_off',
                        'value' => 1,
                        'label' => $this->trans('No', [], 'Admin.Global')
                    ],
                ],
            ];
        }

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Settings', [], 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ],
                'input' => $category_inputs,
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
        $helper->submit_action = 'submitRentFunnel';
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

    private function getConfigFieldsValues()
    {
        $fields = [];
        foreach (RentFunnelObjectModel::getCategories() as $category)
        {
            $fields['category_' . $category['id_category']] = Tools::getValue('category_' . $category['id_category'], Configuration::get('RENTFUNNEL_CATEGORY_ENABLED_'.$category['id_category'], false) ?? '');
            $fields['category_order_' . $category['id_category']] = Tools::getValue('category_order_' . $category['id_category'], Configuration::get('RENTFUNNEL_CATEGORY_ORDER_'.$category['id_category'], 0) ?? '');
            $fields['category_multiselect_' . $category['id_category']] = Tools::getValue('category_multiselect_' . $category['id_category'], Configuration::get('RENTFUNNEL_CATEGORY_ORDER_'.$category['id_category'], 0) ?? '');
            $fields['category_skippable_' . $category['id_category']] = Tools::getValue('category_skippable_' . $category['id_category'], Configuration::get('RENTFUNNEL_CATEGORY_SKIPPABLE_'.$category['id_category'], 0) ?? '');
        }
        return $fields;
    }

}