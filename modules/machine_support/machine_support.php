<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'machine_support/classes/MachineSupportType.php';

class Machine_Support extends Module
{
    public function __construct()
    {
        $this->name = 'machine_support';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'K.GURREA';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Support Machine (SAV)');
        $this->description = $this->l('Permet aux clients d\'ouvrir un ticket SAV.');
    }

    public function install()
    {
        include_once _PS_MODULE_DIR_ . 'machine_support/classes/MachineSupportType.php';

        return parent::install()
            && $this->installDatabase()
            && $this->installTab()
            && $this->registerHook('displayCustomerAccount')
            && $this->registerHook('displayNav1')
            && $this->registerHook('actionAdminCustomerThreadsListingFieldsModifier');
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->uninstallDatabase();
    }

    // Modifie la table customer_thread pour ajouter la colonne request_type
    // Créer la table
    public function installDatabase()
    {
        $sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'customer_thread` 
                ADD COLUMN `request_type` VARCHAR(100) NULL DEFAULT NULL AFTER `status`';

        try {
            return Db::getInstance()->execute($sql);
        } catch (Exception $e) {

        }

        // Table des types
        $sql1 = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'support_client_type` (
            `id_support_client_type` int(11) NOT NULL AUTO_INCREMENT,
            `active` tinyint(1) unsigned NOT NULL DEFAULT \'1\',
            PRIMARY KEY (`id_support_client_type`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        // Table langue
        $sql2 = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'support_client_type_lang` (
            `id_support_client_type` int(11) NOT NULL,
            `id_lang` int(11) NOT NULL,
            `name` varchar(255) NOT NULL,
            PRIMARY KEY (`id_support_client_type`, `id_lang`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        if (Db::getInstance()->execute($sql1) && Db::getInstance()->execute($sql2)) {
            // Insertion des valeurs par défaut
            $defaults = ['Panne Machine', 'Intervention', 'Remboursement', 'Autre'];
            foreach ($defaults as $name) {
                $type = new MachineSupportType();
                $type->active = 1;
                foreach (Language::getLanguages(false) as $lang) {
                    $type->name[$lang['id_lang']] = $name;
                }
                $type->save();
            }
            return true;
        }
        return false;
    }

    // Supprime l'ajout de la colonne request_type dans la table customer_thread dans la BD
    public function uninstallDatabase()
    {
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'support_client_type`');
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'support_client_type_lang`');
        return true;

    }

    public function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminMachineSupportTypes';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Types de demande SAV';
        }
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentCustomerThreads'); // Dans le menu Clients
        $tab->module = $this->name;
        return $tab->add();
    }

    // Hook qui gère la modification de la liste des tickets supports pour ajouter une colonne "Type de demande"
    public function hookActionAdminCustomerThreadsListingFieldsModifier($params)
    {
        if (isset($params['select'])) {
            $params['select'] .= ', a.request_type';
        }

        // Récupération dynamique des types pour le filtre
        include_once _PS_MODULE_DIR_ . 'machine_support/classes/MachineSupportType.php';
        $types = MachineSupportType::getTypes($this->context->language->id);
        $list = [];
        foreach ($types as $t) {
            $list[$t['name']] = $t['name'];
        }

        $params['fields']['request_type'] = [
            'title' => $this->l('Type de demande'),
            'align' => 'center',
            'class' => 'fixed-width-lg',
            'type' => 'select',
            'list' => $list, // Liste dynamique
            'filter_key' => 'a!request_type'
        ];
    }

    // Hook qui permet d'ajouter de nouvelle page à la partie compte client
    public function hookDisplayCustomerAccount()
    {
        return $this->display(__FILE__, 'views/templates/hook/customer_account.tpl');
    }

    // Hook qui permet d'ajouter de nouveaux éléments dans la barre de navigation
    public function hookDisplayNav1()
    {
        return $this->display(__FILE__, 'views/templates/hook/nav.tpl');
    }
}
