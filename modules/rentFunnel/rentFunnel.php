<?php

require_once __DIR__ . '/classes/RentFunnelObjectModel.php';

if (!defined('_PS_VERSION_')) exit;

class RentFunnel extends Module
{
    public const HOOKS = [
        'displayHome',
        'displayProductListFunctionalButtons',
        'displayProductActions',
        'displayNav2',
        'actionPresentProductListing',
        'actionProductListOverride',
        'actionProductListModifier',
        'actionSearch',
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

        $this->id_customer = null;
    }

    public function install()
    {
        $sql_order = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "rentFunnel_order` (
                    `id_rentFunnel_order` INT(11) NOT NULL AUTO_INCREMENT,
                    `id_category` INT(11) NOT NULL,
                    `name` VARCHAR(255) NOT NULL,
                    `position` INT(11) NOT NULL,
                    `multiselect` BOOLEAN NOT NULL,
                    `skippable` BOOLEAN NOT NULL,
                    PRIMARY KEY (`id_rentFunnel_order`),
                    UNIQUE KEY `uniq_category` (`id_category`)
                ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $sql_company_info = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "rentFunnel_company_info` (
                                `id_rentFunnel_company_info` INT(11) NOT NULL AUTO_INCREMENT,
                                `company_id` INT(11) NOT NULL,
                                `company_size` VARCHAR(30) NOT NULL,
                                `consumption` VARCHAR(30) NOT NULL,
                                `additional_drinks` MEDIUMTEXT NOT NULL,
                                `dynamic_answers` MEDIUMTEXT NOT NULL,
                                PRIMARY KEY (`id_rentFunnel_company_info`),
                                UNIQUE KEY `uniq_company` (`company_id`)
                            ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        if(!(DB::getInstance()->execute($sql_order)))
        {
            $this->_errors[] = $this->trans('Erreur création table rentFunnel_order', [], 'Admin.Notifications.Error');
            return false;
        }

        if(!(DB::getInstance()->execute($sql_company_info)))
        {
            $this->_errors[] = $this->trans('Erreur création table rentFunnel_company_info', [], 'Admin.Notifications.Error');
            return false;
        }

        return parent::install()
            && $this->registerHook(static::HOOKS);
    }

    public function uninstall()
    {
        $sqlOrder = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "rentFunnel_order`";

        if (!Db::getInstance()->execute($sqlOrder)) {
            return false;
        }

        $sqlCompanyInfo = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "rentFunnel_company_info`";

        if (!Db::getInstance()->execute($sqlCompanyInfo)) {
            return false;
        }

        return parent::uninstall();
    }

    public function hookDisplayHome()
    {
        $id_customer = (int)$this->context->customer->id;
        $sql = new DbQuery();
        $sql->select('id_customer');
        $sql->from('customer_pro_data');
        $sql->where('id_customer = ' . (int)$id_customer);

        $is_pro = (bool)Db::getInstance()->getValue($sql);

        //if($is_pro) {
            $this->id_customer = $id_customer;
            $dropdowns = json_decode(Configuration::get('RENTFUNNEL_DROPDOWNS'), true) ?: [];
            $drinks = RentFunnelObjectModel::getDrinkTypes();

            $drinksOptions = [];
            foreach ($drinks as $drink) {
                $drinksOptions[] = $drink['name'];
            }

            $mainQuestions = [
                [
                    'name' => 'company_size',
                    'label' => 'Quelle est la taille de votre entreprise ?',
                    'options' => ['1-10 employés', '11-50 employés', '51-200 employés', '201-500 employés', '+ de 500 employés']
                ],
                [
                    'name' => 'consumption',
                    'label' => 'Quelle est la consommation de café de votre entreprise par jour ?',
                    'options' => ['Moins de 50 tasses', '50 à 100 tasses', '100 à 200 tasses', '200 à 500 tasses', '+ de 500 tasses']
                ],
                [
                    'name' => 'additional_drinks',
                    'label' => 'Quelle(s) autre(s) boisson(s) voulez-vous pouvoir proposer à vos collaborateurs ?',
                    'options' => $drinksOptions
                ]
            ];

            $processedDropdowns = [];
            foreach ($dropdowns as $dropdown) {
                $categoryId = (int)$dropdown['question_category'];
                $category = new Category($categoryId, $this->context->language->id);

                // Récupérer les sous-catégories
                $subCategories = $category->getSubCategories($this->context->language->id, true);
                $subCategoryOptions = [];

                foreach ($subCategories as $subCat) {
                    $subCategoryOptions[] = [
                        'id' => $subCat['id_category'],
                        'name' => $subCat['name']
                    ];
                }

                // Traduire le type de question
                $questionTypeLabel = ($dropdown['question_type'] === 'preference') ? 'préférence' : 'consommation';

                $processedDropdowns[] = [
                    'name' => 'dynamic_' . $dropdown['question_type'] . '_' . $categoryId,
                    'question_type' => $dropdown['question_type'],
                    'question_type_label' => $questionTypeLabel,
                    'category_id' => $categoryId,
                    'category_name' => $category->name,
                    'label' => 'Quelle est votre ' . $questionTypeLabel . ' de ' . $category->name . ' ?',
                    'sub_categories' => $subCategoryOptions,
                    'select_name' => 'dynamic_category_' . $categoryId
                ];
            }

            if (Tools::isSubmit('submitCompanyInfo')) {
                $data = $this->processCompanyInfoForm($mainQuestions, $processedDropdowns);

                if ($data) {
                    RentFunnelObjectModel::setCompanyInfo($id_customer, $data);

                    Tools::redirect($this->context->link->getPageLink('index'));
                }
            }

            $this->context->smarty->assign([
                'mainQuestions' => $mainQuestions,
                'dynamicQuestions' => $processedDropdowns,
                'module_dir' => $this->getPathUri(),
                'form_errors' => $this->context->controller->errors ?? [],
            ]);

            return $this->display(__FILE__, '/views/templates/front/company_info.tpl');
        //} else return;
    }

    private function processCompanyInfoForm($mainQuestions, $dynamicQuestions)
    {
        $data = [];
        $errors = [];

        foreach($mainQuestions as $question)
        {
            $value = Tools::getValue($question['name']);

            if(empty($value))
            {
                $errors[] = '"' . $question['label'] . '" est obligatoire';
            } else {
                if ($question['name'] === 'additional_drinks' && is_array($value)) {
                    $data[$question['name']] = array_map('pSQL', $value);
                } else {
                    $data[$question['name']] = pSQL($value);
                }
            }
        }

        // Traiter les questions dynamiques
        $dynamicAnswers = [];
        foreach ($dynamicQuestions as $question)
        {
            // Récupérer la sous-catégorie sélectionnée
            $selectedSubCategory = Tools::getValue($question['select_name']);

            if (!empty($selectedSubCategory)) {
                $dynamicAnswers[] = [
                    'question_type' => $question['question_type'],
                    'category_id' => $question['category_id'],
                    'category_name' => $question['category_name'],
                    'selected_sub_category_id' => (int)$selectedSubCategory,
                    'question_label' => $question['label']
                ];
            }
        }

        // Ajouter les réponses dynamiques au tableau de données
        if (!empty($dynamicAnswers)) {
            $data['dynamic_answers'] = json_encode($dynamicAnswers);
        }

        if(!empty($errors))
        {
            $this->context->controller->errors = array_merge(
                $this->context->controller->errors ?? [],
                $errors
            );
            return false;
        }

        return $data;
    }

    public function hookDisplayProductListFunctionalButtons()
    {
        return $this->display(__FILE__, 'rentFunnel_functional_buttons.tpl');
    }

    public function hookDisplayProductActions()
    {
        $moduleBooking = Module::getInstanceByName('rentalroute');
        if(!($moduleBooking) || !($moduleBooking->active))
        {
            $id_category = $this->context->controller->getCategory()->id;
            $category = new Category($id_category, $this->context->language->id);
            $categoryName = $category->name;

            $rentFunnelOrder = RentFunnelObjectModel::getRentFunnelOrder();
            $categoryList = [];
            Configuration::updateValue("RENTFUNNEL_SELECTED_PRODUCTS", json_encode([]));

            foreach ($rentFunnelOrder as $rentFunnelItem)
            {
                $categoryList[] = $rentFunnelItem;
            }
            $firstCategoryName = $categoryList[0]['name'];

            Configuration::updateValue("RENTFUNNEL_CATEGORYLIST", json_encode($categoryList));
            $this->context->smarty->assign('categoryList', $categoryList);

            if ($categoryName == $firstCategoryName) {
                return $this->display(__FILE__, 'rentFunnel_product_actions.tpl');
            }
        }
        return false;
    }

    public function hookDisplayNav2($params)
    {
        $current_page = $this->context->controller->getPageName();
        if(str_starts_with($current_page, 'module-rentFunnel-')
        && $current_page != 'module-rentFunnel-recap') {
            $categoryList = [];
            $total_price = 0;

            $totalSelectedProducts = json_decode(Configuration::get("RENTFUNNEL_SELECTED_PRODUCTS"), true);

            foreach ($totalSelectedProducts as $categoryName => $products)
            {
                $categoryList[$categoryName] = [
                    'name' => $categoryName,
                    'products' => []
                ];

                foreach ($products as $productId => $product)
                {
                    $categoryList[$categoryName]['products'][$productId] = $product;

                    if(isset($product['price']) && isset($product['quantity']))
                    {
                        $total_price += floatval($product['price']) * intval($product['quantity']);
                    }
                    else if(isset($product['price']))
                    {
                        $total_price += floatval($product['price']);
                    }
                }
            }

            $this->context->smarty->assign([
                'category_list' => $categoryList,
                'total_price' => $total_price,
                'shop_url' => $this->context->shop->getBaseURL(),
                'shop_currency' => $this->context->currency->symbol,
            ]);
            return $this->display(__FILE__, 'views/templates/hook/header.tpl');
        }
        return false;
    }

    private function getCustomerProductPriorityScore($id_customer)
    {
        $companyInfo = RentFunnelObjectModel::getCompanyInfo($id_customer);
        if (empty($companyInfo['company_size']) && empty($companyInfo['consumption']) && empty($companyInfo['additional_drinks'])) {
            return null;
        }

        $priorityScore = [
            'company_size_score' => 0,
            'consumption_score' => 0,
            'additional_drinks_boost' => []
        ];

        // Score par taille de l'entreprise
        $sizeScores = [
            '1-10 employés' => 1,
            '11-50 employés' => 2,
            '51-200 employés' => 3,
            '201-500 employés' => 4,
            '+ de 500 employés' => 5
        ];
        $priorityScore['company_size_score'] = $sizeScores[$companyInfo['company_size']] ?? 0;

        // Score par consommation
        $consumptionScores = [
            'Moins de 50 tasses' => 1,
            '50 à 100 tasses' => 2,
            '100 à 200 tasses' => 3,
            '200 à 500 tasses' => 4,
            '+ de 500 tasses' => 5
        ];
        $priorityScore['consumption_score'] = $consumptionScores[$companyInfo['consumption']] ?? 0;

        // Parser les boissons additionnelles
        if (!empty($companyInfo['additional_drinks']) && is_array($companyInfo['additional_drinks'])) {
            foreach ($companyInfo['additional_drinks'] as $drinkName) {
                $priorityScore['additional_drinks_boost'][trim($drinkName)] = 50;
            }
        }

        return $priorityScore;
    }

    public function hookActionPresentProductListing($params)
    {
        if (!isset($params['productListingLazyArray']) || !is_object($params['productListingLazyArray'])) {
            return;
        }

        $id_customer = (int)$this->context->customer->id;
        $priorityRules = $this->getCustomerProductPriorityScore($id_customer);

        if (!$priorityRules) {
            return;
        }

        // Récupérer les produits via le presenter
        $products = $params['productListingLazyArray']->getProducts();
        if (empty($products)) {
            return;
        }

        $sortedProducts = [];
        foreach ($products as $product) {
            $score = $this->calculateProductPriorityScore($product, $priorityRules);
            $product->rentfunnel_priority_score = $score; // Stocker le score
            $sortedProducts[$score . '_' . $product->id] = $product;
        }

        // Trier par score décroissant
        krsort($sortedProducts);
        $params['productListingLazyArray']->setProducts(array_values($sortedProducts));
    }

    public function hookActionProductListOverride($params)
    {
        // Ne prioriser que pour les clients pro ayant rempli le formulaire
        $id_customer = (int)$this->context->customer->id;
        $companyInfo = RentFunnelObjectModel::getCompanyInfo($id_customer);

        if (empty($companyInfo['company_size'])) {
            return;
        }

        $priorityRules = $this->getCustomerProductPriorityScore($id_customer);

        // Récupérer les produits de la liste
        if (!$priorityRules || !isset($params['products']) || empty($params['products'])) {
            return;
        }

        $products = $params['products'];
        $sortedProducts = [];

        foreach ($products as $product) {
            $productCategories = $this->getProductCategoryNames($product['id_product']);

            $score = 0;

            foreach ($productCategories as $catName) {
                $catNameClean = trim($catName);
                if (isset($priorityRules['additional_drinks_boost'][$catNameClean])) {
                    $score += $priorityRules['additional_drinks_boost'][$catNameClean];
                    error_log("BOOST BOISSON: $catNameClean -> Score: $score pour produit {$product['id_product']}");
                }
            }

            $score += $priorityRules['company_size_score'];
            $score += $priorityRules['consumption_score'];

            $product['rentfunnel_priority_score'] = $score;
            $sortedProducts[$score . '_' . $product['id_product']] = $product;
        }

        // Trier par score décroissant
        krsort($sortedProducts);
        $params['products'] = array_values($sortedProducts);
    }

    public function hookActionProductListModifier($params)
    {
        $this->hookActionProductListOverride($params);
    }

    private function getProductCategoryNames($id_product, $id_lang = null)
    {
        if ($id_lang === null) {
            $id_lang = $this->context->language->id;
        }

        $sql = "SELECT DISTINCT cl.name
                FROM " . _DB_PREFIX_ . "category_product cp
                JOIN " . _DB_PREFIX_ . "category_lang cl ON cp.id_category = cl.id_category
                WHERE cp.id_product = " . (int)$id_product . "
                AND cl.id_lang = " . (int)$id_lang;

        $categories = Db::getInstance()->executeS($sql);
        $names = [];

        foreach ($categories as $cat) {
            $names[] = $cat['name'];
        }

        return $names;
    }

    private function calculateProductPriorityScore($product, $priorityRules)
    {
        $score = $priorityRules['company_size_score'] + $priorityRules['consumption_score'];

        // Récupérer les catégories du produit
        $productCategories = [];
        if (isset($product->id)) {
            $productCategories = $this->getProductCategoryNames($product->id);
        } elseif (isset($product['id_product'])) {
            $productCategories = $this->getProductCategoryNames($product['id_product']);
        }

        // BOOST MAXIMAL pour les boissons sélectionnées
        foreach ($productCategories as $catName) {
            if (isset($priorityRules['additional_drinks_boost'][$catName])) {
                $score += $priorityRules['additional_drinks_boost'][$catName];
            }
        }

        return $score;
    }

    public function getContent()
    {
        $this->context->controller->addJS($this->_path . 'views/js/back_office_form.js');

        $output = '';

        if(Tools::isSubmit('submitRentFunnelOrder'))
        {
            $this->postProcessOrder();
            $output .= $this->displayConfirmation($this->l('Configuration de l\'ordre des commandes sauvegardé'));
        }

        if(Tools::isSubmit('submitRentFunnelCompanyInfo'))
        {
            $this->postProcessCompanyInfo();
            $output .= $this->displayConfirmation($this->l('Configuration des questions sauvegardée'));
        }

        return $output . $this->renderFormOrder() . $this->renderFormCompanyInfo();
    }

    private function postProcessOrder()
    {
        if(Tools::isSubmit('submitRentFunnelOrder'))
        {
            $sqlDelete = "DELETE FROM " . _DB_PREFIX_ . "rentFunnel_order";
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

                $sqlInsert = "INSERT INTO " . _DB_PREFIX_ . "rentFunnel_order (id_category, name, position, multiselect, skippable)
                            VALUES($id_category, '$name', $order, $multiselect, $skippable)
                            ON DUPLICATE KEY UPDATE name='$name', position=$order, multiselect=$multiselect, skippable=$skippable";

                Db::getInstance()->execute($sqlInsert);
            }
        }
    }

    private function renderFormOrder()
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
                ],
                'form_group_class' => 'toggle-parent-category-' . $id_field,
            ];

            $category_inputs[] = [
                'type' => 'text',
                'label' => $this->trans('Place de la catégorie dans l\'entonnoir : ', [], 'Admin.Global'),
                'name' => 'category_order_' . $id_field,
                'size' => 3,
                'desc' => $this->trans('Postion de la catégorie dans l\'entonnoir (1 pour la 1ère page, etc...) - La 1ère catégorie sera celle dont les produits permettront d\'accéder à l\'entonnoir', [], 'Admin.Global'),
                'form_group_class' => 'toggle-child-category-' . $id_field,
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
                'form_group_class' => 'toggle-child-category-' . $id_field,
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
                'form_group_class' => 'toggle-child-category-' . $id_field,
            ];
        }

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Paramètres de l\'entonnoir des commandes', [], 'Admin.Global'),
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
        $helper->submit_action = 'submitRentFunnelOrder';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'uri' => $this->getPathUri(),
            'fields_value' => $this->getOrderConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];
        
        return $helper->generateForm([$fields_form]);
    }

    private function getOrderConfigFieldsValues()
    {
        $fields = [];

        $rentFunnelOrderData = RentFunnelObjectModel::getRentFunnelOrder();
        $dbCategories = [];
        foreach ($rentFunnelOrderData as $item) {
            $dbCategories[$item['id_category']] = $item;
        }

        foreach (RentFunnelObjectModel::getCategories() as $category)
        {
            $id_cat = $category['id_category'];

            if (isset($dbCategories[$id_cat])) {
                $fields['category_' . $id_cat] = 1;
                $fields['category_order_' . $id_cat] = $dbCategories[$id_cat]['position'];
                $fields['category_multiselect_' . $id_cat] = $dbCategories[$id_cat]['multiselect'];
                $fields['category_skippable_' . $id_cat] = $dbCategories[$id_cat]['skippable'];
            } else {
                $fields['category_' . $id_cat] = 0;
                $fields['category_order_' . $id_cat] = 0;
                $fields['category_multiselect_' . $id_cat] = 0;
                $fields['category_skippable_' . $id_cat] = 0;
            }
        }
        return $fields;
    }


    private function postProcessCompanyInfo()
    {
        if(Tools::isSubmit('submitRentFunnelCompanyInfo'))
        {
            $dropdowns = [];
            $index = 0;

            while (Tools::getIsset('dropdown_question_type_' . $index) ||
                    Tools::getIsset('dropdown_categories_' . $index)) {
                $questionType = Tools::getValue('dropdown_question_type_' . $index);
                $questionCategory = Tools::getValue('dropdown_categories_' . $index);

                if (!empty($questionType) && !empty($questionCategory)) {
                    $dropdowns[] = [
                        'question_type' => pSQL($questionType),
                        'question_category' => (int)$questionCategory,
                    ];
                }
                $index++;

                if ($index > 100) { // Protection
                    break;
                }
            }

            Configuration::updateValue('RENTFUNNEL_DROPDOWNS', json_encode($dropdowns));
        }
    }

    private function renderFormCompanyInfo()
    {
        $dropdowns = json_decode(Configuration::get('RENTFUNNEL_DROPDOWNS'), true) ?: [];
        $dropdowns_Json = htmlspecialchars(json_encode($dropdowns), ENT_QUOTES, 'UTF-8');
        error_log('DROPDOWNS JSON: ' . $dropdowns_Json);
        $categories = RentFunnelObjectModel::getCategories();
        $categories_Json = htmlspecialchars(json_encode($categories), ENT_QUOTES, 'UTF-8');

        $menu_inputs = [];
        $menu_inputs[] = [
            'type' => 'html',
            'name' => 'dropdown-container',
            'html_content' => '
            <div>
                <h3>
                    Questions principales : 
                    <br> - "Quelle est la taille de votre entreprise ?"
                    <br> - "Quelle est la consommation de café de votre entreprise par jour ?"
                    <br> - "Quelle(s) autre(s) boisson(s) voulez-vous pouvoir proposer à vos collaborateurs ?"
                </h3>
            </div>
            <div id="dropdown_container" data-dropdowns="'.$dropdowns_Json.'" data-categories="'.$categories_Json.'">
                <h4>Questions supplémentaires <small>(cliquez sur "+" pour en ajouter)</small></h4>
                <div id="dropdown-list">
                    <!-- Les menus déroulants apparaîtront ici -->
                </div>
                <button type="button" id="add-dropdown-btn" class="btn btn-success">
                    <i class="icon-plus"></i> Ajouter un menu déroulant
                </button>
            </div>
            '
        ];

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Paramètres des questions', [], 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ],
                'input' => $menu_inputs,
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
        $helper->submit_action = 'submitRentFunnelCompanyInfo';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'uri' => $this->getPathUri(),
            'fields_value' => $this->getCompanyInfoConfigFieldValues($this->id_customer),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getCompanyInfoConfigFieldValues($company_Id)
    {
        return json_decode(Configuration::get('RENTFUNNEL_DROPDOWNS'), true) ?: [];
    }

}