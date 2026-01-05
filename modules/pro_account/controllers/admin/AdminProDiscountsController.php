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

        $this->proGroupId = (int)Configuration::get('PRO_ACCOUNT_GROUP_ID');

        $this->_where = 'AND a.id_group = ' . $this->proGroupId;

        $this->fields_list = [
            'id_specific_price' => [
                'title' => $this->module->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'id_product' => [
                'title' => $this->module->l('ID Produit (0 = Tous)'),
                'align' => 'center',
                'callback' => 'getProductNameForList'
            ],
            'reduction' => [
                'title' => $this->module->l('Réduction'),
                'align' => 'center',
                'callback' => 'displayReduction'
            ],
            'from' => [
                'title' => $this->module->l('Date début'),
                'type' => 'datetime'
            ],
            'to' => [
                'title' => $this->module->l('Date fin'),
                'type' => 'datetime'
            ]
        ];

        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    public function initContent()
    {
        if (!$this->proGroupId) {
            $this->displayWarning($this->module->l('Attention : Le groupe "Professionnels" n\'est pas configuré. Réinitialisez le module.'));
        }
        parent::initContent();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAddspecific_price')) {
            if (!$this->proGroupId) {
                $this->proGroupId = Db::getInstance()->getValue('SELECT id_group FROM '._DB_PREFIX_.'group_lang WHERE name LIKE "%Professionnels (Module)%"');
            }

            $_POST['id_group'] = $this->proGroupId;
            $_POST['id_shop'] = 0; // 0 = Toutes les boutiques
            $_POST['id_shop_group'] = 0;
            $_POST['id_currency'] = 0;
            $_POST['id_country'] = 0;
            $_POST['id_customer'] = 0; // Tous les clients du groupe
            $_POST['from_quantity'] = 1;
            $_POST['price'] = -1;
            $_POST['id_product_attribute'] = 0;

            if (empty($_POST['id_product'])) {
                $_POST['id_product'] = 0;
            }

            // Gestion de la taxe (1 = TTC, 0 = HT)
            $_POST['reduction_tax'] = 0;

            // ID Produit
            if (empty($_POST['id_product'])) {
                $_POST['id_product'] = 0;
            }
        }

        parent::postProcess();
    }

    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->module->l('Ajouter une réduction PRO'),
                'icon' => 'icon-tag'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->module->l('ID Produit'),
                    'name' => 'id_product',
                    'required' => true,
                    'desc' => $this->module->l('Mettez 0 pour appliquer la réduction à TOUS les produits, ou l\'ID d\'un produit spécifique.'),
                    'col' => 2
                ],
                [
                    'type' => 'select',
                    'label' => $this->module->l('Type de réduction'),
                    'name' => 'reduction_type',
                    'options' => [
                        'query' => [
                            ['id' => 'amount', 'name' => $this->module->l('Montant (ex: -10€)')],
                            ['id' => 'percentage', 'name' => $this->module->l('Pourcentage (ex: -20%)')],
                        ],
                        'id' => 'id',
                        'name' => 'name'
                    ]
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Valeur de la réduction'),
                    'name' => 'reduction',
                    'col' => 2,
                    'desc' => $this->module->l('Ex: 0.20 pour 20%, ou 10 pour 10€.')
                ],
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
                ['type' => 'hidden', 'name' => 'id_group'],
                ['type' => 'hidden', 'name' => 'id_shop'],
            ],
            'submit' => [
                'title' => $this->module->l('Enregistrer'),
            ]
        ];

        if (empty($this->object->id)) {
            $this->fields_value['id_product'] = 0;
            $this->fields_value['id_group'] = $this->proGroupId;
            $this->fields_value['id_shop'] = 0;
        }

        return parent::renderForm();
    }

    public function getProductNameForList($id_product)
    {
        if ((int)$id_product === 0) {
            return '<span class="badge badge-success">TOUS LES PRODUITS</span>';
        }
        $product = new Product($id_product, false, $this->context->language->id);
        return $product->name . ' (ID: '.$id_product.')';
    }

    public function displayReduction($value, $row)
    {
        if ($row['reduction_type'] == 'percentage') {
            return '-' . (float)($value * 100) . '%';
        }

        return '-' . Context::getContext()->getCurrentLocale()->formatPrice(
                $value,
                Context::getContext()->currency->iso_code
            );
    }
}