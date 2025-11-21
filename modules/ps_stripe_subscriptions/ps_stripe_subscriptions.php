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
        $this->author = 'Armani.B';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Module d\'abonnements Stripe');
        $this->description = $this->l('Module pour gérer les abonnements récurrents via Stripe.');

        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_,
        ];
    }

    public function install()
    {
        if (parent::install() == false ||
            !$this->registerHook('payementOptions') ||
            !$this->registerHook('paymentReturn') ||
            !$this->registerHook('header') ||
            !$this->registerHook('displayCustomerAccount') ||
            !$this->registerHook('actionFrontControllerSetMedia') ||
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ";

        $sql_price_table = "
        CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "stripe_price_link` (
            `id_product_ps` INT(10) UNSIGNED NOT NULL PRIMARY KEY,
            `id_price_stripe` VARCHAR(50) NOT NULL,
            `id_product_stripe` VARCHAR(50) NOT NULL,
            UNIQUE KEY `id_price_stripe` (`id_price_stripe`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ";

        if (!Db::getInstance()->execute($sql_customer_table) ||
            !Db::getInstance()->execute($sql_price_table)) {

            // En cas d'échec SQL, on annule l'installation complète
            $this->uninstall();
            return false;
        }

        return true;
    }

    public function uninstall()
    {
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
        $lines = file($envFile, 4);
        if (!$lines) {
            return null;
        }
        foreach ($lines as $line) {
            // La logique de trim() ici compense l'omission de FILE_SKIP_WHITE_SPACE
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            list($k, $v) = explode('=', $line, 2);
            if (trim($k) === $key) {
                return trim($v, " \n\r\t\v\x00\"'");
            }
        }
        return null;
    }

    /**
     * Configure la clé secrète Stripe pour la librairie en lisant le fichier .env
     */
    protected function initStripeApi()
    {
        require_once(_PS_MODULE_DIR_ . $this->name . '/vendor/autoload.php');

        $secret_key = $this->getEnvVariable('PS_STRIPE_SK');

        if (!$secret_key) {
            $secret_key = getenv('PS_STRIPE_SK');
        }

        if (!$secret_key) {
            throw new Exception('La clé secrète PS_STRIPE_SK n\'a pas été trouvée dans le fichier .env à la racine.');
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

    public function hookHeader(array $params)
    {
        // Méthode requise car le Hook 'header' est enregistré
        return null;
    }

    public function hookDisplayCustomerAccount(array $params) {
        $this->context->smarty->assign([
            'subscription_link' => $this->context->link->getModuleLink($this->name, 'default'),
            'subscriptions_icon' => 'card_membership',
        ]);
        // Utilisation du chemin exact que vous avez corrigé
        return $this->display(__FILE__, 'views/templates/customer_account.tpl');
    }

    public function createOrGetStripeCustomer($id_customer, $email, $firstname, $lastname)
    {
        if (!class_exists('StripeCustomerLink')) {
            require_once(_PS_MODULE_DIR_ . $this->name . '/classes/StripeCustomerLink.php');
        }

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

    public function hookPaymentOptions($params) {
        if (!$this->active || !$this->context->customer->isLogged() || $params['cart']->nbProducts() == 0) {
            return [];
        }
        $newOption = newPaymentOption();
        $newOption = setModuleName($this->name);
        $newOption->setCallToActionText($this->l('Payer la Location + Abonnement Stripe'));
        $newOption->setAction($this->context->link->getModuleLink($this->name, 'payment', [], true));


    }

    public function getIdCustomerPsByStripeId($stripe_customer_id)
    {
        if (!class_exists('StripeCustomerLink')) {
            require_once(_PS_MODULE_DIR_ . $this->name . '/classes/StripeCustomerLink.php');
        }

        return Db::getInstance()->getValue('
        SELECT `id_customer_ps`
        FROM `' . _DB_PREFIX_ . 'stripe_customer_link`
        WHERE `id_customer_stripe` = "' . pSQL($stripe_customer_id) . '"
    ');
    }

    public function getIdProductPsByStripePriceId($stripe_price_id)
    {
        if (!class_exists('StripePriceLink')) {
            require_once(_PS_MODULE_DIR_ . $this->name . '/classes/StripePriceLink.php');
        }

        return Db::getInstance()->getValue('
        SELECT `id_product_ps`
        FROM `' . _DB_PREFIX_ . 'stripe_price_link`
        WHERE `id_price_stripe` = "' . pSQL($stripe_price_id) . '"
    ');
    }

    protected function createRecurringOrderCart($id_customer_ps, $invoice) {
        $customer = new Customer((int)$id_customer_ps);
        $cart = new Cart();

        $id_address = Address::getCustomerDefaultID((int)$id_customer_ps);

        if (!$id_address) {
            PrestaShopLogger::addLog('Client PS #' . $id_customer_ps . ' n\'a pas d\'adresse par défaut.', 3);
            return false;
        }
        $cart->id_shop = (int)$this->context->shop->id;
        $cart->id_customer = (int)$id_customer_ps;
        $cart->id_address_delivery = (int)$id_address;
        $cart->id_address_invoice = (int)$id_address;
        $cart->id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $cart->id_currency = (int)Configuration::get('PS_CURRENCY_DEFAULT');
        $cart->id_guest = (int)Guest::getFromCustomer($customer->id);

        $id_carrier = (int)Configuration::get('PS_CARRIER_DEFAULT');
        $cart->id_carrier = (int)$id_carrier;

        if (!$cart->add()) {
            PrestaShopLogger::addLog('Impossible de créer le panier pour le client PS.', 3);
            return false;
        }

        foreach ($invoice->lines->data as $line) {
            $stripe_price_id = $line->price->id;
            $quantity = $line->quantity;
            $id_product_ps = $this->getIdProductPsByStripePriceId($stripe_price_id);
            if ($id_product_ps) {
                $cart->updateQty($quantity, (int)$id_product_ps, null, null, 'up', 0, new Shop((int)$cart->id_shop));
            }
            else {
                PrestaShopLogger::addLog('Produit PS introuvable pour le prix Stripe #' . $stripe_price_id, 3);
            }
        }

        $cart->update();
        $cart->setDeliveryOption(
            $cart->getCarrierList() ? [$cart->id_address_delivery => $id_carrier . ','] : []
        );
        $cart->updateDeliveryOption();
        return (int)$cart->id;
    }

    public function processSubscriptionPaymentSuccess($invoice)
    {
        $stripe_customer_id = $invoice->customer;
        $id_customer_ps = $this->getIdCustomerPsByStripeId($stripe_customer_id);

        if (!$id_customer_ps) {
            PrestaShopLogger::addLog('WebHook Stripe: Client PS non trouvé pour Stripe ID: ' . $stripe_customer_id, 3, null, null, null, true);
            return false;
        }

        $cart_id = $this->createRecurringOrderCart($id_customer_ps, $invoice);

        if (!$cart_id) {
            PrestaShopLogger::addLog('WebHook Stripe: Impossible de créer le panier pour l\'abonnement ID: ' . $invoice->subscription, 3, null, null, $id_customer_ps, true);
            return false;
        }


        $customer = new Customer((int)$id_customer_ps);
        $amount_paid = (float)$invoice->amount_paid / 100;

        $this->validateOrder(
            (int)$cart_id,
            Configuration::get('PS_OS_PAYMENT'),
            $amount_paid,
            $this->displayName,
            $this->l('Paiement récurrent réussi via Stripe (Facture ID: ') . $invoice->id . ')',
            [],
            (int)Configuration::get('PS_CURRENCY_DEFAULT'),
            false,
            $customer->secure_key
        );

        PrestaShopLogger::addLog('Commande PS #' . (int)$this->currentOrder . ' créée via Webhook Stripe.', 1, null, 'Order', (int)$this->currentOrder, true);

        return (int)$this->currentOrder;
    }

    public function processSubscriptionPaymentFailure($invoice)
    {
        $stripe_customer_id = $invoice->customer;
        $id_customer_ps = $this->getIdCustomerPsByStripeId($stripe_customer_id);

        if ($id_customer_ps) {
            PrestaShopLogger::addLog('Échec du paiement récurrent pour le client PS #' . $id_customer_ps, 2, null, 'Customer', $id_customer_ps, true);
        }
        return true;
    }

    public function processSubscriptionDeletion($subscription)
    {
        $stripe_customer_id = $subscription->customer;
        $id_customer_ps = $this->getIdCustomerPsByStripeId($stripe_customer_id);

        if ($id_customer_ps) {
            PrestaShopLogger::addLog('Abonnement Stripe annulé pour le client PS #' . $id_customer_ps . ' (Sub ID: ' . $subscription->id . ')', 1, null, 'Customer', $id_customer_ps, true);
        }
        return true;
    }
}