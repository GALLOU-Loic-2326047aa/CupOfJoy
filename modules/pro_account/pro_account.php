<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Pro_Account extends Module
{
    public function __construct()
    {
        $this->name = 'pro_account';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Killian GURREA';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Compte Professionnel');
        $this->description = $this->l('Permet aux clients de s\'enregistrer en tant que professionnels.');
    }


    public function install()
    {
        if (!parent::install()
            || !$this->installDatabase()
            || !$this->registerHook('actionCustomerAccountAdd')
            || !$this->registerHook('additionalCustomerFormFields')
            || !$this->registerHook('displayHeader')
        ) {
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->uninstallDatabase();
    }

    public function installDatabase()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."customer_pro_data` (
            `id_customer` INT(10) UNSIGNED NOT NULL,
            `company_name` VARCHAR(255) NOT NULL,
            `siret` VARCHAR(14),
            `vat_number` VARCHAR(255),
            PRIMARY KEY (`id_customer`)
        ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8;";

        return Db::getInstance()->execute($sql);
    }

    public function uninstallDatabase()
    {
        return Db::getInstance()->execute("DROP TABLE IF EXISTS `"._DB_PREFIX_."customer_pro_data`");
    }


    // BACK-END //
    public function hookActionCustomerAccountAdd($params)
    {
        if (!Tools::isSubmit('is_pro')) {
            return;
        }

        $newCustomer = $params['newCustomer'];
        $id_customer = $newCustomer->id;

        $company_name = Tools::getValue('company_name');
        $siret = Tools::getValue('siret');

        if ($id_customer && !empty($company_name)) {
            Db::getInstance()->insert('customer_pro_data', [
                'id_customer' => (int)$id_customer,
                'company_name' => pSQL($company_name),
                'siret' => pSQL($siret),
            ]);
        }
    }

    // FRONT-END //
    public function hookDisplayHeader()
    {
        $this->context->controller->addJS($this->_path . 'views/js/front.js');

        $js_vars = [];

        if ($this->context->customer->isLogged()) {
            $is_pro = (bool)Db::getInstance()->getValue('SELECT 1 FROM `'._DB_PREFIX_.'customer_pro_data` WHERE `id_customer` = '.(int)$this->context->customer->id);
            if ($is_pro) {
                $js_vars['customerIsPro'] = true;
            }
        }

        if ($this->context->controller->php_self == 'authentication') {
            $js_vars['proAccountAjaxUrl'] = $this->context->link->getModuleLink('pro_account', 'ajax');
        }

        if (!empty($js_vars)) {
            Media::addJsDef($js_vars);
        }
    }

    public function hookAdditionalCustomerFormFields($params)
    {
        return [
            (new FormField)
                ->setName('is_pro')
                ->setType('checkbox')
                ->setLabel($this->l('Je suis un professionnel')),

            (new FormField)
                ->setName('company_name')
                ->setType('text')
                ->setLabel($this->l('Nom de l\'entreprise'))
                ->addAvailableValue('css-class', 'pro-field')
                ->setRequired(true),

            (new FormField)
                ->setName('siret')
                ->setType('text')
                ->setLabel($this->l('Numéro de SIRET'))
                ->addAvailableValue('css-class', 'pro-field'),
        ];
    }
}
