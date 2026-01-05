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
        $this->description = $this->l('Ajoute des champs pro au formulaire d\'inscription, et aussi la possiblité d\'ajouter des reductions uniquement au compte professionnel.');
    }

    public function install()
    {
        return parent::install()
            && $this->installDatabase()
            && $this->installProGroup()
            && $this->installTab()
            && $this->registerHook('displayCustomerAccountForm')
            && $this->registerHook('actionSubmitAccount')
            && $this->registerHook('displayHeader')
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
        // 1. Onglet Business (Clients)
        $this->addTab('AdminProAccountBusiness', 'Business (Création)', 'AdminParentCustomer');

        // 2. Onglet Discounts (Catalogue)
        $this->addTab('AdminProDiscounts', 'Discount for Businesses', 'AdminCatalog');

        return true;
    }

    private function addTab($className, $tabName, $parentClassName)
    {
        // On vérifie si l'onglet existe déjà pour éviter les doublons
        $id_tab = (int)Tab::getIdFromClassName($className);
        if ($id_tab) {
            return true; // Il existe déjà, on ne fait rien
        }

        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $className;
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $tabName;
        }
        $tab->id_parent = (int)Tab::getIdFromClassName($parentClassName);
        $tab->module = $this->name;

        return $tab->add();
    }

    public function uninstallTab()
    {
        $this->removeTab('AdminProAccountBusiness');
        $this->removeTab('AdminProDiscounts');

        return true;
    }

    private function removeTab($className)
    {
        $id_tab = (int)Tab::getIdFromClassName($className);
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    public function installProGroup()
    {
        // On vérifie si le groupe existe déjà dans la config
        if (!Configuration::get('PRO_ACCOUNT_GROUP_ID')) {
            $group = new Group();
            $group->name = array();
            foreach (Language::getLanguages(true) as $lang) {
                $group->name[$lang['id_lang']] = 'Professionnels (Module)';
            }
            $group->price_display_method = 1; // 1 = Hors Taxe, 0 = TTC
            $group->save();

            Configuration::updateValue('PRO_ACCOUNT_GROUP_ID', $group->id);
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

            $groupId = (int)Configuration::get('PRO_ACCOUNT_GROUP_ID');
            if ($groupId) {
                $newCustomer->addGroups([$groupId]);
                // On définit le groupe par défaut
                $newCustomer->id_default_group = $groupId;
                $newCustomer->update();
            }
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
