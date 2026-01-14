<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

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
        return parent::install()
            && $this->installDatabase()
            && $this->registerHook('displayCustomerAccount')
            && $this->registerHook('displayNav1')
            && $this->registerHook('actionAdminCustomerThreadsListingFieldsModifier');
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->uninstallDatabase();
    }

    // Modifie la table customer_thread pour ajouter la colonne request_type
    public function installDatabase()
    {
        $sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'customer_thread` 
                ADD COLUMN `request_type` VARCHAR(100) NULL DEFAULT NULL AFTER `status`';

        try {
            return Db::getInstance()->execute($sql);
        } catch (Exception $e) {

        }

        return true;
    }

    // Supprime l'ajout de la colonne request_type dans la table customer_thread dans la BD
    public function uninstallDatabase()
    {
        $sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'customer_thread` DROP COLUMN `request_type`';
        return Db::getInstance()->execute($sql);
    }

    // Hook qui gère la modification de la liste des tickets supports pour ajouter une colonne "Type de demande"
    public function hookActionAdminCustomerThreadsListingFieldsModifier($params)
    {
        // On ajoute le champ à la requête SQL de la liste
        if (isset($params['select'])) {
            $params['select'] .= ', a.request_type';
        }

        // On définit la colonne visuelle
        $params['fields']['request_type'] = [
            'title' => $this->l('Type de demande'),
            'align' => 'center',
            'class' => 'fixed-width-lg',
            'type' => 'select',
            'list' => [
                'Panne Machine' => $this->l('Panne Machine'),
                'Intervention' => $this->l('Intervention'),
                'Remboursement' => $this->l('Remboursement'),
                'Autre' => $this->l('Autre')
            ],
            'filter_key' => 'a!request_type',
            'callback_object' => $this
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