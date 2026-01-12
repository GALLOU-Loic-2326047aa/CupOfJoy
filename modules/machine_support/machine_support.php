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
        $this->version = '1.0.0';
        $this->author = 'K.GURREA';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Support Machine (SAV)');
        $this->description = $this->l('Permet aux clients d\'ouvrir un ticket SAV pour une machine.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayCustomerAccount') // Lien dans "Mon Compte"
            && $this->registerHook('displayNav2'); // Lien dans le header
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    // Affiche le lien dans la page "Mon Compte"
    public function hookDisplayCustomerAccount()
    {
        return $this->display(__FILE__, 'views/templates/hook/customer_account.tpl');
    }

    // Affiche un lien dans le header à côté de "Contactez-nous"
    public function hookDisplayNav2()
    {
        return $this->display(__FILE__, 'views/templates/hook/nav.tpl');
    }
}