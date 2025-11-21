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
        $this->author = 'GURREA Killian';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Compte Professionnel');
        $this->description = $this->l('Ajoute des champs pro au formulaire d\'inscription.');
    }

    public function install()
    {
        return parent::install()
            && $this->installDatabase()
            && $this->registerHook('displayCustomerAccountForm')
            && $this->registerHook('actionSubmitAccount')
            && $this->registerHook('displayHeader')
            && $this->installTab()
            && $this->registerHook('actionCustomerAccountAdd');
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
            PRIMARY KEY (`id_customer`)
        ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8;";
        return Db::getInstance()->execute($sql);
    }

    public function uninstallDatabase()
    {
        return Db::getInstance()->execute("DROP TABLE IF EXISTS `"._DB_PREFIX_."customer_pro_data`");
    }

    public function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminProAccountBusiness';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Business (Pro)';
        }
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentCustomer'); // On le met dans le menu "Clients"
        $tab->module = $this->name;
        return $tab->add();
    }

    public function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminProAccountBusiness');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    public function hookDisplayCustomerAccountForm()
    {
        $this->context->smarty->assign([
            'pro_account_ajax_url' => $this->context->link->getModuleLink($this->name, 'ajax', [], true),
            'manual_validation_url' => $this->context->link->getModuleLink($this->name, 'manualvalidation', [], true)
        ]);

        return $this->display(__FILE__, 'views/templates/hook/pro_fields.tpl');
    }

    public function hookActionSubmitAccount()
    {
        if (!Tools::isSubmit('is_pro')) {
            return;
        }

        $siretValidated = Tools::getValue('siret_validated');

        if (empty(Tools::getValue('siret'))) {
            $this->context->controller->errors[] = $this->l('Le numéro de SIRET est obligatoire.');
        } elseif (empty(Tools::getValue('company_name'))) {
            $this->context->controller->errors[] = $this->l('Le nom de l\'entreprise est obligatoire.');
        } elseif ($siretValidated !== '1') {
            $this->context->controller->errors[] = $this->l('Veuillez cliquer sur "Vérifier le Siret" et utiliser un numéro valide.');
        }
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

        if ($id_customer && !empty($company_name) && !empty($siret)) {
            Db::getInstance()->insert('customer_pro_data', [
                'id_customer' => (int)$id_customer,
                'company_name' => pSQL($company_name),
                'siret' => pSQL($siret),
            ]);
        }
    }

    public function hookDisplayHeader()
    {
        if (!$this->context->customer->isLogged()) {
            return;
        }

        $id_customer = (int)$this->context->customer->id;
        $sql = new DbQuery();
        $sql->select('id_customer');
        $sql->from('customer_pro_data');
        $sql->where('id_customer = ' . $id_customer);

        $is_pro = (bool)Db::getInstance()->getValue($sql);

        if ($is_pro) {
            Media::addJsDef(['customerIsPro' => true]);

            $this->context->controller->addCSS($this->_path . 'views/css/pro_badge.css');
            $this->context->controller->addJS($this->_path . 'views/js/pro_badge.js');
        }
    }
}
