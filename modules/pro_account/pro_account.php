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
            || !$this->registerHook('actionFrontControllerSetMedia')
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

    public function hookActionFrontControllerSetMedia()
    {
        if ('authentication' === $this->context->controller->php_self) {
            $this->context->controller->registerJavascript(
                'module-pro_account-front-js', // un identifiant unique
                'modules/' . $this->name . '/views/js/front.js', // le chemin relatif
                ['position' => 'bottom', 'priority' => 150] // options
            );
        }
    }
}