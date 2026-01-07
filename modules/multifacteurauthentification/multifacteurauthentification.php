<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class MultifacteurAuthentification extends Module
{
    public function __construct()
    {
        $this->name = 'multifacteurauthentification';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Maxime Allasio';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Authentification Multi-Facteurs (2FA)');
        $this->description = $this->l('Ajoute une seconde étape de vérification par email lors de la connexion.');
    }

    public function install()
    {
        return parent::install() && $this->registerHook('actionAuthentication');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function hookActionAuthentication($params)
    {
        $customer = $params['customer'];

        $verificationCode = rand(100000, 999999);

        $cookie = new Cookie('ps-mfa');
        $cookie->mfa_code = $verificationCode;
        $cookie->mfa_time = time() + (10 * 60);
        $cookie->mfa_id_customer = $customer->id;
        $cookie->write();

        Mail::Send(
            (int)$this->context->language->id,
            'mfa_mail',
            $this->l('Votre code de vérification'),
            [
                '{firstname}' => $customer->firstname,
                '{lastname}' => $customer->lastname,
                '{code}' => $verificationCode,
            ],
            $customer->email,
            $customer->firstname . ' ' . $customer->lastname
        );

        $this->context->customer->logout();

        $verificationUrl = $this->context->link->getModuleLink($this->name, 'verification');
        Tools::redirect($verificationUrl);
    }
}