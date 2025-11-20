<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_Stripe_Subscriptions extends Module
{
    public function __construct()
    {
        $this->name = 'ps_stripe_subscriptions';
        $this->tab = 'billing_payment';
        $this->version = '1.0.0';
        $this->author = 'Baptiste Armani';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Module d\'abonnements Stripe');
        $this->description = $this->l('Module pour gérer les abonnements récurrents via Stripe.');

        // Vérifier si la version de PrestaShop est compatible
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_,
        ];
    }

    public function install()
    {
        if (parent::install() == false ||
            !$this->registerHook('header') ||
            !$this->registerHook('displayCustomerAccount') ||
            !$this->registerHook('actionFrontControllerSetMedia') ||
            // Correction 2: actionCustomerAccountAdd est sensible à la casse
            !$this->registerHook('actionCustomerAccountAdd') ||
            !$this->registerHook('actionAuthentication')
        ) {
            return false;
        }

        $sql_customer_table = "
        CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "stripe_customer_link` (
            `id_customer_ps` INT(10) UNSIGNED NOT NULL PRIMARY KEY,
            `id_customer_stripe` VARCHAR(50) NOT NULL,
            UNIQUE KEY `id_customer_stripe` (`id_customer_stripe`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;
        ";

        $sql_price_table = "
        CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "stripe_price_link` (
            `id_product_ps` INT(10) UNSIGNED NOT NULL PRIMARY KEY,
            `id_price_stripe` VARCHAR(50) NOT NULL,
            `id_product_stripe` VARCHAR(50) NOT NULL,
            UNIQUE KEY `id_price_stripe` (`id_price_stripe`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;
    ";
        if (!Db::getInstance()->execute($sql_price_table)||
            !Db::getInstance()->execute($sql_customer_table)) {

            $this->uninstall();
            return false;
        }

        // Les tables sont créées, l'installation a réussi.
        return true;
    }

    public function uninstall()
    {
        // Supprimer les tables lors de la désinstallation
        $sql_drop_customer = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "stripe_customer_link`";
        $sql_drop_price = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "stripe_price_link`";

        if (!Db::getInstance()->execute($sql_drop_customer) ||
            !Db::getInstance()->execute($sql_drop_price)) {
            return false;
        }

        return parent::uninstall();
    }


    /**
     * Lit la variable d'environnement à partir du fichier .env à la racine du projet
     */
    protected function getEnvVariable($key)
    {
        $envFile = _PS_ROOT_DIR_ . '/.env';

        if (!file_exists($envFile)) {
            return null;
        }

    }

    protected function initStripeApi() {
        require_once(_PS_MODULE_DIR_ . $this->name . '/vendor/autoload.php');

        $secret_key = $this->getEnvVariable('PS_STRIPE_SK');

        if (!$secret_key) {
            $secret_key = getenv('PS_STRIPE_SK');
        }
        \Stripe\Stripe::setApiKey($secret_key);
    }

    public function hookActionFrontControllerSetMedia(array $params) {
        $this->context->controller->registerJavascript(
            'remote-stripe-js',
            'https://js.stripe.com/v3/',
            ['server' => 'remote', 'position' => 'head', 'priority' => 10]
        );
        $this->context->controller->registerJavascript(
            'module-stripe-js',
            'modules/' . $this->name . '/views/js/stripe.js',
            ['position' => 'bottom', 'priority' => 50]
        );
        $this->context->controller->registerStylesheet(
            'module-stripe-css',
            'modules/' . $this->name . '/views/css/stripe.css',
            ['media' => 'all', 'priority' => 150]
        );
    }

    public function hookDisplayCustomerAccount(array $params) {
        $this->context->smarty->assign([
            'subscription_link' => $this->context->link->getModuleLink($this->name, 'default'),
            'subscriptions_icon' => 'card_membership',
        ]);
        return $this->display(__FILE__, 'views/templates/hook/customer_account.tpl');
    }

    public function createOrGetStripeCustomer($id_customer, $email, $firstname, $lastname)
    {
        // Nécessite le chargement de StripeCustomerLink.php
        if (!class_exists('StripeCustomerLink')) {
            require_once(_PS_MODULE_DIR_ . $this->name . '/classes/StripeCustomerLink.php');
        }

        // 1. VÉRIFIER si l'ID Stripe existe déjà dans votre base
        $stripe_customer_id = StripeCustomerLink::getStripeIdByPsId($id_customer);
        if ($stripe_customer_id) {
            return $stripe_customer_id;
        }

        try {
            $this->initStripeApi();
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Erreur Stripe: Clé API non configurée. ' . $e->getMessage(), 3, null, null, $id_customer);
            return null;
        }

        try {
            $customer = \Stripe\Customer::create([
                'email' => $email,
                'name'  => $firstname . ' ' . $lastname,
                'metadata' => [
                    'prestashop_id' => $id_customer,
                ],
            ]);
            $new_stripe_id = $customer->id;

            $link = new StripeCustomerLink();
            $link->id_customer_ps = (int)$id_customer;
            $link->id_customer_stripe = $new_stripe_id;
            $link->add();

            return $new_stripe_id;

        } catch (\Stripe\Exception\ApiErrorException $e) {
            $message = 'Erreur Stripe lors de la création du client PS #' . $id_customer . ': ' . $e->getMessage();
            PrestaShopLogger::addLog($message, 3, null, null, $id_customer);
            return null;
        }
    }

    public function hookActionAuthentication(array $params) {
        if (isset($params['customer']) && $params['customer'] instanceof Customer) {
            $customer = $params['customer'];
            $this->createOrGetStripeCustomer($customer->id, $customer->email, $customer->firstname, $customer->lastname);
        }
    }

    public function hookActionCustomerAccountAdd(array $params)
    {
        if (isset($params['newCustomer']) && $params['newCustomer'] instanceof Customer) {
            $customer = $params['newCustomer'];
            $this->createOrGetStripeCustomer($customer->id, $customer->email, $customer->firstname, $customer->lastname);
        }
    }

}
