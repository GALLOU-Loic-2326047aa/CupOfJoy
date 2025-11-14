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
        $this->author = 'Votre Nom';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Compte Professionnel (avec TPL)');
        $this->description = $this->l('Ajoute des champs pro au formulaire d\'inscription via un fichier de template.');
    }

    public function install()
    {
        // On enregistre les hooks dont nous avons besoin
        return parent::install()
            && $this->installDatabase()
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

    /**
     * Affiche notre template sur le formulaire de création de compte.
     */
    public function hookDisplayCustomerAccountForm()
    {
        // On assigne l'URL de notre contrôleur AJAX à Smarty (le moteur de template)
        $this->context->smarty->assign([
            'pro_account_ajax_url' => $this->context->link->getModuleLink($this->name, 'ajax', [], true)
        ]);

        // On affiche le contenu du fichier .tpl
        return $this->display(__FILE__, 'views/templates/hook/pro_fields.tpl');
    }

    /**
     * Valide les données AVANT que le compte ne soit créé.
     */
    public function hookActionSubmitAccount()
    {
        // Si la case "pro" n'est pas cochée, on ne fait rien.
        if (!Tools::isSubmit('is_pro')) {
            return;
        }

        // On récupère la "preuve" de validation envoyée par le JavaScript
        $siretValidated = Tools::getValue('siret_validated');

        if (empty(Tools::getValue('siret'))) {
            $this->context->controller->errors[] = $this->l('Le numéro de SIRET est obligatoire.');
        } elseif (empty(Tools::getValue('company_name'))) {
            $this->context->controller->errors[] = $this->l('Le nom de l\'entreprise est obligatoire.');
        } elseif ($siretValidated !== '1') {
            $this->context->controller->errors[] = $this->l('Veuillez cliquer sur "Vérifier le Siret" et utiliser un numéro valide.');
        }
    }

    /**
     * Sauvegarde nos données APRÈS que le compte ait été créé avec succès.
     */
    public function hookActionCustomerAccountAdd($params)
    {
        // On vérifie une dernière fois si c'est un compte pro
        if (!Tools::isSubmit('is_pro')) {
            return;
        }

        // $params contient les informations sur le client qui vient d'être créé
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
        }
    }

    public function hookDisplayHeader()
    {
        // 1. On ne fait rien si le client n'est pas connecté
        if (!$this->context->customer->isLogged()) {
            return;
        }

        // 2. On vérifie si le client est un professionnel
        $id_customer = (int)$this->context->customer->id;
        $sql = new DbQuery();
        $sql->select('id_customer');
        $sql->from('customer_pro_data');
        $sql->where('id_customer = ' . $id_customer);

        // Db::getInstance()->getValue() est parfait pour ça: il retourne la valeur ou false.
        $is_pro = (bool)Db::getInstance()->getValue($sql);

        // 3. Si c'est un pro, on prépare les données pour le JavaScript
        if ($is_pro) {
            // Media::addJsDef() est la méthode propre à PrestaShop pour créer des variables JS
            Media::addJsDef(['customerIsPro' => true]);

            // 4. On charge nos fichiers CSS et JS qui vont créer le badge
            $this->context->controller->addCSS($this->_path . 'views/css/pro_badge.css');
            $this->context->controller->addJS($this->_path . 'views/js/pro_badge.js');
        }
    }
}
