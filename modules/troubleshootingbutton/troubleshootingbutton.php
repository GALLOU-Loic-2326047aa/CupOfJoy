<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class troubleshootingbutton extends Module
{

    public function __construct()
    {
        $this->name = 'troubleshootingbutton';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'GURREA.K';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Troubleshooting button');
        $this->description = $this->l('This module allows you to manage the troubleshooting button.');

        $this->templateFile = 'module:troubleshootingbutton/troubleshootingbutton.tpl';
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayNav1');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submittroubleshootingbutton')) {
            $supportUrl = Tools::getValue('troubleshootingbutton_URL');
            Configuration::updateValue('troubleshootingbutton_URL', $supportUrl);
            $output .= $this->displayConfirmation($this->l('Paramètres enregistrés'));
        }

        $defaultUrl = Configuration::get('troubleshootingbutton_URL', 'http://localhost:8082/admin350e44cybetj2my7gso');

        $output .= '<form method="post" action="'.htmlspecialchars($_SERVER['REQUEST_URI']).'">
            <div class="form-group">
                <label>'.$this->l('URL du support').'</label>
                <input type="text" name="troubleshootingbutton_URL" value="'.Tools::safeOutput($defaultUrl).'" class="form-control"/>
            </div>
            <button type="submit" name="submittroubleshootingbutton" class="btn btn-primary">'.$this->l('Save').'</button>
        </form>';

        return $output;
    }

    public function hookDisplayHeader($params)
    {
        $this->context->controller->registerStylesheet(
            'troubleshootingbutton-css',
            'css/troubleshootingbutton.css'
        );
    }

    public function hookDisplayNav1($params)
    {
        $supportUrl = Configuration::get('troubleshootingbutton_URL', 'https://http://localhost:8082/admin350e44cybetj2my7gso');

        $this->context->smarty->assign([
            'support_url' => $supportUrl,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/nav1_button.tpl');
    }

}