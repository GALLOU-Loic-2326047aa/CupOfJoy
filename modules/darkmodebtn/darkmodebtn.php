<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class DarkModeBtn extends Module
{
    public function __construct()
    {
        $this->name = 'darkmodebtn';
        $this->version = '1.0.0';
        $this->author = 'Maxime A';
        $this->tab = 'front_office_features';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Dark Mode Button');
        $this->description = $this->l('Ajoute un bouton centré permettant de basculer en mode clair/sombre.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHome')
            && $this->registerHook('displayHeader');
    }

    public function hookDisplayHeader()
    {
        // Ajout du CSS
        $this->context->controller->registerStylesheet(
            'module-darkmodebtn',
            'modules/' . $this->name . '/views/css/darkmodebtn.css',
            [
                'media' => 'all',
                'priority' => 150,
            ]
        );

        // Ajout du JS
        $this->context->controller->registerJavascript(
            'module-darkmodebtn',
            'modules/' . $this->name . '/views/js/darkmodebtn.js',
            [
                'position' => 'bottom',
                'priority' => 150,
            ]
        );
    }

    public function hookDisplayHome($params)
    {
        return $this->fetch('module:darkmodebtn/views/templates/hook/displayHome.tpl');
    }
}
