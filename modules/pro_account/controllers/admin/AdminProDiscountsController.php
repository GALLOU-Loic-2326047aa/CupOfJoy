<?php

class AdminProDiscountsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'specific_price';
        $this->className = 'SpecificPrice';
        $this->lang = false;

        parent::__construct();

        $this->meta_title = $this->module->l('Réductions pour les comptes PRO');

        // ID du groupe PRO
        $this->proGroupId = (int)Configuration::get('PRO_ACCOUNT_GROUP_ID');

        // On filtre pour n'afficher que les règles liées au groupe PRO OU aux clients PRO
        $this->_where = 'AND (a.id_group = ' . $this->proGroupId . ' OR a.id_customer > 0)';

        $this->fields_list = [
            'id_specific_price' => ['title' => 'ID', 'class' => 'fixed-width-xs', 'align' => 'center'],
            'id_product' => ['title' => 'Produit', 'callback' => 'getProductNameForList'],
            'id_customer' => ['title' => 'Client', 'callback' => 'getCustomerNameForList'],
            'reduction' => ['title' => 'Réduction', 'callback' => 'displayReduction', 'align' => 'center'],
            'from' => ['title' => 'Début', 'type' => 'datetime'],
            'to' => ['title' => 'Fin', 'type' => 'datetime']
        ];

        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    // Fonction qui gère l'appel du JS pour l'autocomplete des champs textes pro
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        // Autocomplete
        $this->addJqueryUI('ui.autocomplete');

        $this->addJS(_PS_MODULE_DIR_ . $this->module->name . '/views/js/admin_discount.js');

        Media::addJsDef([
            'adminProductsToken' => Tools::getAdminTokenLite('AdminProducts'),
            'adminCustomersToken' => Tools::getAdminTokenLite('AdminCustomers')
        ]);
    }

    public function initContent()
    {
        if (!$this->proGroupId) {
            $this->displayWarning($this->module->l('Attention : Le groupe "Professionnels" n\'est pas configuré.'));
        }
        parent::initContent();
    }

    // Fonction qui va géré tout le processus coté admin de l'affichage des réduction pour les comptes pro
    public function postProcess()
    {
        if (Tools::isSubmit('submitAddspecific_price')) {

            $scope = Tools::getValue('scope'); // product, category, all
            $id_product = (int)Tools::getValue('id_product');
            $id_category = (int)Tools::getValue('id_category');

            if (is_array($id_category)) {
                $id_category = (int)reset($id_category);
            }

            $commonData = [
                'id_shop' => 0,
                'id_shop_group' => 0,
                'id_currency' => 0,
                'id_country' => 0,
                'id_group' => (int)Configuration::get('PRO_ACCOUNT_GROUP_ID'),
                'id_customer' => (int)Tools::getValue('id_customer'),
                'id_product_attribute' => 0,
                'price' => -1,
                'from_quantity' => 1,
                'reduction' => (float)Tools::getValue('reduction'),
                'reduction_type' => Tools::getValue('reduction_type'),
                'from' => Tools::getValue('from'),
                'to' => Tools::getValue('to'),
                'reduction_tax' => 0 // HT
            ];

            if ($commonData['id_customer'] > 0) {
                $commonData['id_group'] = 0;
            }

            // Par catégorie
            if ($scope === 'category' && $id_category > 0) {
                $products = Product::getProducts($this->context->language->id, 0, 0, 'id_product', 'ASC', $id_category, true);

                if (!empty($products)) {
                    foreach ($products as $product) {
                        $this->createSpecificPrice($product['id_product'], $commonData);
                    }
                    $this->confirmations[] = $this->module->l('Réductions créées pour tous les produits de la catégorie.');
                    Tools::redirectAdmin(self::$currentIndex.'&conf=3&token='.$this->token);
                } else {
                    $this->errors[] = $this->module->l('Aucun produit trouvé dans cette catégorie.');
                }
                return;
            }

            // Tous les produits
            if ($scope === 'all') {
                $_POST['id_product'] = 0;
            }

            // Produit unique
            $_POST['id_shop'] = $commonData['id_shop'];
            $_POST['id_shop_group'] = $commonData['id_shop_group'];
            $_POST['id_currency'] = $commonData['id_currency'];
            $_POST['id_country'] = $commonData['id_country'];
            $_POST['id_group'] = $commonData['id_group'];
            $_POST['id_customer'] = $commonData['id_customer'];
            $_POST['from_quantity'] = $commonData['from_quantity'];
            $_POST['price'] = $commonData['price'];
            $_POST['reduction_tax'] = $commonData['reduction_tax'];

            if ($scope === 'product' && empty($_POST['id_product'])) {
                $this->errors[] = $this->module->l('Veuillez rechercher et sélectionner un produit.');
                return;
            }
        }

        parent::postProcess();
    }

    // Fonction qui gère la création des prix spécific (par défaut pour prestashop)
    protected function createSpecificPrice($id_product, $data)
    {
        $specificPrice = new SpecificPrice();
        $specificPrice->id_product = (int)$id_product;
        $specificPrice->id_shop = $data['id_shop'];
        $specificPrice->id_shop_group = $data['id_shop_group'];
        $specificPrice->id_currency = $data['id_currency'];
        $specificPrice->id_country = $data['id_country'];
        $specificPrice->id_group = $data['id_group'];
        $specificPrice->id_customer = $data['id_customer'];
        $specificPrice->id_product_attribute = $data['id_product_attribute'];
        $specificPrice->price = $data['price'];
        $specificPrice->from_quantity = $data['from_quantity'];
        $specificPrice->reduction = $data['reduction'];
        $specificPrice->reduction_type = $data['reduction_type'];
        $specificPrice->from = $data['from'];
        $specificPrice->to = $data['to'];
        $specificPrice->reduction_tax = $data['reduction_tax'];
        $specificPrice->add();
    }

    // Fonction qui gère la mise en forme de à qui, quel catégorie ou quelle produit on applique la réduction
    public function renderForm()
    {
        // Liste des produits
        $products = Product::getProducts($this->context->language->id, 0, 0, 'name', 'ASC');
        $productOptions = [];
        $productOptions[] = ['id_product' => 0, 'name' => '--- Sélectionner un produit ---'];
        foreach ($products as $product) {
            $productOptions[] = [
                'id_product' => $product['id_product'],
                'name' => $product['name'] . ' (Ref: ' . $product['reference'] . ')'
            ];
        }

        // Liste des clients pro
        $sql = 'SELECT c.id_customer, c.firstname, c.lastname, c.email, p.company_name
                FROM '._DB_PREFIX_.'customer c
                INNER JOIN '._DB_PREFIX_.'customer_pro_data p ON c.id_customer = p.id_customer
                WHERE c.active = 1 AND c.deleted = 0
                ORDER BY p.company_name ASC';

        $proCustomers = Db::getInstance()->executeS($sql);

        $customerOptions = [];
        // 0 = Tous les clients du compte pro
        $customerOptions[] = ['id_customer' => 0, 'name' => '--- Tous les Pros (Groupe) ---'];

        if ($proCustomers) {
            foreach ($proCustomers as $customer) {
                $customerOptions[] = [
                    'id_customer' => $customer['id_customer'],
                    // Affichage : Nom Entreprise - Prénom Nom (email)
                    'name' => $customer['company_name'] . ' - ' . $customer['firstname'] . ' ' . $customer['lastname'] . ' (' . $customer['email'] . ')'
                ];
            }
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->module->l('Gérer une réduction PRO'),
                'icon' => 'icon-tag'
            ],
            'input' => [
                [
                    'type' => 'radio',
                    'label' => $this->module->l('Appliquer à'),
                    'name' => 'scope',
                    'class' => 't',
                    'values' => [
                        ['id' => 'scope_all', 'value' => 'all', 'label' => $this->module->l('Tout le catalogue')],
                        ['id' => 'scope_category', 'value' => 'category', 'label' => $this->module->l('Une Catégorie')],
                        ['id' => 'scope_product', 'value' => 'product', 'label' => $this->module->l('Un Produit spécifique')],
                    ]
                ],

                // Menu déroulant produit
                [
                    'type' => 'select',
                    'label' => $this->module->l('Choisir un produit'),
                    'name' => 'id_product',
                    'class' => 'chosen', // Recherche activée
                    'options' => [
                        'query' => $productOptions,
                        'id' => 'id_product',
                        'name' => 'name'
                    ],
                    'form_group_class' => 'product_select_container'
                ],

                // Arbre catégorie
                [
                    'type' => 'categories',
                    'label' => $this->module->l('Choisir une catégorie'),
                    'name' => 'id_category',
                    'tree' => [
                        'id' => 'categories-tree',
                        'selected_categories' => [],
                        'disabled_categories' => [],
                        'use_search' => true,
                        'use_checkbox' => false,
                    ],
                    'form_group_class' => 'category_tree_container'
                ],

                // Menu déroulant compte pro
                [
                    'type' => 'select',
                    'label' => $this->module->l('Client spécifique (Optionnel)'),
                    'name' => 'id_customer', // Nom direct
                    'class' => 'chosen', // Recherche activée
                    'options' => [
                        'query' => $customerOptions,
                        'id' => 'id_customer',
                        'name' => 'name'
                    ],
                    'desc' => $this->module->l('Laisser sur "Tous les Pros" pour appliquer au groupe entier, ou choisir une entreprise spécifique.')
                ],

                // Menu déroulant pour le type de réduction
                [
                    'type' => 'select',
                    'label' => $this->module->l('Type de réduction'),
                    'name' => 'reduction_type',
                    'options' => [
                        'query' => [
                            ['id' => 'amount', 'name' => $this->module->l('Montant (HT)')],
                            ['id' => 'percentage', 'name' => $this->module->l('Pourcentage (%)')],
                        ],
                        'id' => 'id',
                        'name' => 'name'
                    ]
                ],
                // Champs texte pour mettre le prix
                [
                    'type' => 'text',
                    'label' => $this->module->l('Valeur'),
                    'name' => 'reduction',
                    'col' => 2,
                    'suffix' => $this->module->l('HT ou %')
                ],
                // Choix des dates
                [
                    'type' => 'datetime',
                    'label' => $this->module->l('Disponible du'),
                    'name' => 'from',
                ],
                [
                    'type' => 'datetime',
                    'label' => $this->module->l('Jusqu\'au'),
                    'name' => 'to',
                ],
            ],
            'submit' => [
                'title' => $this->module->l('Enregistrer'),
            ]
        ];

        // Valeurs par défaut
        if (empty($this->object->id)) {
            $this->fields_value['scope'] = 'all';
            $this->fields_value['id_product'] = 0;
            $this->fields_value['id_customer'] = 0;
        } else {
            if ($this->object->id_product > 0) {
                $this->fields_value['scope'] = 'product';
            } else {
                $this->fields_value['scope'] = 'all';
            }
        }

        return parent::renderForm();
    }

    // Fonction qui retourne le nom des produits
    public function getProductNameForList($id_product)
    {
        if ((int)$id_product === 0) return '<span class="badge badge-success">TOUS</span>';
        $p = new Product($id_product, false, $this->context->language->id);
        return $p->name;
    }

    // Fonction qui retourne le nom des clients pro
    public function getCustomerNameForList($id_customer)
    {
        if ((int)$id_customer === 0) return '<span class="badge badge-info">GROUPE PRO</span>';
        $c = new Customer($id_customer);
        return $c->firstname . ' ' . $c->lastname;
    }

    // Fonction qui retourne la réduction final, et si la réduction est un pourcentage cela réalise le calcul
    public function displayReduction($value, $row)
    {
        if ($row['reduction_type'] == 'percentage') return '-' . (float)($value * 100) . '%';
        return '-' . Context::getContext()->getCurrentLocale()->formatPrice($value, Context::getContext()->currency->iso_code) . ' (HT)';
    }
}