<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_Stripe_Subscriptions extends modules
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
            !$this->registerHook('header')||
            !this->registerHook('displayCustomerAccount')||
            !this->registerHook('actionFrontControllerSetMedia')
        ) {
            return false;
        }

        // Ajoutez ici l'enregistrement des Hooks nécessaires (voir l'étape suivante)

        return true;
    }

    public function uninstall()
    {
        // Ajoutez ici la suppression des données, tables, etc.

        return parent::uninstall();
    }

    public function actionFrontControllerSetMedia(array $param) {
        $this->context->controller->registerJavascript(
            'remote-stripe-js',
            'https://js.stripe.com/v3/',
            ['server' => 'remote', 'position' => 'head', 'priority' => 10]
        );
    }

    // --- Les méthodes pour les Hooks (pour injecter du code dans le thème) seront ajoutées ici ---
    // public function hookDisplay...() { ... }
}
