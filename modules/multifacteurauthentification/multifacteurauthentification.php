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
        // Création de la table mfa lors de l'installation
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mfa` (
            `id_customer` INT(11) UNSIGNED NOT NULL,
            `is_verified` TINYINT(1) DEFAULT 0,
            PRIMARY KEY (`id_customer`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return parent::install()
            && Db::getInstance()->execute($sql)
            && $this->registerHook('actionAuthentication');
    }

    public function uninstall()
    {
        // On supprime la table si on désinstalle le module
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'mfa`');
        return parent::uninstall();
    }

    public function hookActionAuthentication($params)
    {
        $customer = $params['customer'];

        // Si la vérification est déjà faite, on arrête et on passe à la suite
        $alreadyVerified = Db::getInstance()->getValue('
            SELECT is_verified FROM `' . _DB_PREFIX_ . 'mfa` 
            WHERE id_customer = ' . (int)$customer->id
        );

        if ((int)$alreadyVerified === 1) {
            return;
        }

        $verificationCode = rand(100000, 999999);

        $cookie = new Cookie('ps-mfa');
        $cookie->mfa_code = $verificationCode;
        $cookie->mfa_time = time() + (10 * 60);
        $cookie->mfa_id_customer = $customer->id;
        $cookie->write();

        // Envoie du mail contenant le code de vérification
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