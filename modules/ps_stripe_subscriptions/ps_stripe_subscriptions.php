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
        $this->version = '1.1.0';
        $this->author = 'Armani.B';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Module d\'abonnements Stripe');
        $this->description = $this->l('Gestion hybride des achats uniques et abonnements récurrents via Stripe.');

        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    /**
     * Charge les classes nécessaires au module pour éviter le ClassNotFound
     */
    public function loadModuleClasses()
    {
        $classDir = _PS_MODULE_DIR_ . $this->name . '/classes/';
        $classes = ['StripePriceLink', 'StripeCustomerLink'];

        foreach ($classes as $className) {
            if (!class_exists($className)) {
                $file = $classDir . $className . '.php';
                if (file_exists($file)) {
                    require_once $file;
                }
            }
        }
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $hooks = [
            'displayAdminProductsExtra',
            'displayAdminProductsCombinationsForm',
            'actionProductUpdate',
            'actionProductCombinationSave',
            'actionBeforeCartUpdateQty',
            'actionFrontControllerSetMedia',
            'displayCustomerAccount',
            'paymentOptions',
            'actionAuthentication',
            'actionCustomerAccountAdd'
        ];

        foreach ($hooks as $hook) {
            if (!$this->registerHook($hook)) {
                return false;
            }
        }

        $sqlPrice = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "stripe_price_link` (
            `id_product_ps` INT(10) UNSIGNED NOT NULL,
            `id_product_attribute` INT(10) UNSIGNED NOT NULL DEFAULT '0',
            `id_product_stripe` VARCHAR(255) NOT NULL,
            `id_price_stripe` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id_product_ps`, `id_product_attribute`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";

        $sqlCustomer = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "stripe_customer_link` (
            `id_customer_ps` INT(10) UNSIGNED NOT NULL PRIMARY KEY,
            `id_customer_stripe` VARCHAR(255) NOT NULL,
            UNIQUE KEY `id_customer_stripe_unique` (`id_customer_stripe`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";

        return Db::getInstance()->execute($sqlPrice) && Db::getInstance()->execute($sqlCustomer);
    }

    public function uninstall()
    {
        $sql = [
            "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "stripe_customer_link`",
            "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "stripe_price_link`"
        ];

        foreach ($sql as $query) {
            Db::getInstance()->execute($query);
        }

        return parent::uninstall();
    }

    // --- Interface de Configuration (Admin) ---

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitSyncAll')) {
            $this->syncAllProductsToStripe();
            $output .= $this->displayConfirmation($this->l('Synchronisation terminée ! Vos produits avec déclinaisons "Abonnement" sont liés.'));
        }

        return $output . $this->renderSyncForm();
    }

    protected function renderSyncForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->submit_action = 'submitSyncAll';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuration Stripe Subscriptions'),
                    'icon' => 'icon-cogs',
                ],
                'description' => $this->l('Cette interface vous permet de synchroniser massivement vos produits. Seules les déclinaisons contenant le mot "Abonnement" seront créées sur Stripe.'),
                'submit' => [
                    'title' => $this->l('Lancer la synchronisation globale'),
                    'class' => 'btn btn-primary'
                ],
            ],
        ];

        return $helper->generateForm([$form]);
    }

    private function syncAllProductsToStripe()
    {
        $this->loadModuleClasses();
        $id_lang = (int)$this->context->language->id;
        $products = Product::getProducts($id_lang, 0, 0, 'id_product', 'ASC');

        foreach ($products as $p) {
            $product_obj = new Product((int)$p['id_product'], false, $id_lang);
            $combinations = $product_obj->getAttributeCombinations($id_lang);

            if (!empty($combinations)) {
                foreach ($combinations as $comb) {
                    if (stripos($comb['attribute_name'], 'Abonnement') !== false) {
                        $this->createStripePriceForProduct(
                            $product_obj,
                            (int)$comb['id_product_attribute'],
                            $comb['attribute_name']
                        );
                    }
                }
            }
        }
    }

    // --- Configuration API & Helpers ---

    public function getEnvVariable($key)
    {
        $envPath = _PS_MODULE_DIR_ . $this->name . '/.env';
        if (!file_exists($envPath)) return null;

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                if (trim($name) === $key) {
                    return trim(trim($value), "\"' ;");
                }
            }
        }
        return null;
    }

    public function initStripeApi()
    {
        $autoload = _PS_MODULE_DIR_ . $this->name . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            throw new Exception("Vendor introuvable. Lancez 'composer install'.");
        }
        require_once $autoload;

        $sk = $this->getEnvVariable('PS_STRIPE_SK') ?: getenv('PS_STRIPE_SK');
        if (!$sk) throw new Exception('Clé API Stripe (SK) manquante.');

        \Stripe\Stripe::setApiKey($sk);
    }

    // --- Logique Métier (Stripe Sync) ---

    public function createStripePriceForProduct($product_obj, $id_attribute = 0, $attr_name = '')
    {
        $this->loadModuleClasses();
        if (StripePriceLink::getStripePriceIdByPsId($product_obj->id, $id_attribute)) return;

        try {
            $this->initStripeApi();
            $name = $product_obj->name[$this->context->language->id] . ($attr_name ? ' - ' . $attr_name : '');

            $stripeProduct = \Stripe\Product::create([
                'name' => $name,
                'metadata' => ['id_ps' => $product_obj->id, 'id_attr' => $id_attribute]
            ]);

            $price_wt = Product::getPriceStatic($product_obj->id, true, ($id_attribute ?: null));

            $stripePrice = \Stripe\Price::create([
                'product' => $stripeProduct->id,
                'unit_amount' => (int)round($price_wt * 100),
                'currency' => $this->context->currency->iso_code,
                'recurring' => ['interval' => 'month'],
            ]);

            $link = new StripePriceLink();
            $link->id_product_ps = (int)$product_obj->id;
            $link->id_product_attribute = (int)$id_attribute;
            $link->id_product_stripe = $stripeProduct->id;
            $link->id_price_stripe = $stripePrice->id;
            $link->add();

        } catch (Exception $e) {
            PrestaShopLogger::addLog('Stripe Sync Error: ' . $e->getMessage(), 3);
        }
    }


    public function createOrGetStripeCustomer($id_customer, $email, $firstname, $lastname)
    {
        $this->loadModuleClasses();
        $stripe_id = StripeCustomerLink::getStripeIdByPsId($id_customer);
        if ($stripe_id) return $stripe_id;

        try {
            $this->initStripeApi();
            $customer = \Stripe\Customer::create([
                'email' => $email,
                'name'  => $firstname . ' ' . $lastname,
                'metadata' => ['prestashop_id' => $id_customer]
            ]);

            $link = new StripeCustomerLink();
            $link->id_customer_ps = (int)$id_customer;
            $link->id_customer_stripe = $customer->id;
            $link->add();

            return $customer->id;
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Stripe Customer Error: ' . $e->getMessage(), 3);
            return null;
        }
    }

    public function hookActionBeforeCartUpdateQty($params)
    {
        $this->loadModuleClasses();
        $cart = $this->context->cart;
        if (!$cart || !$cart->nbProducts()) return;

        $has_sub = false;
        $has_classic = false;

        foreach ($cart->getProducts() as $p) {
            if (StripePriceLink::getStripePriceIdByPsId($p['id_product'], $p['id_product_attribute'])) {
                $has_sub = true;
            } else {
                $has_classic = true;
            }
        }

        $id_to_add = (int)Tools::getValue('id_product');
        $attr_to_add = (int)Tools::getValue('id_product_attribute');
        $is_adding_sub = (bool)StripePriceLink::getStripePriceIdByPsId($id_to_add, $attr_to_add);

        if (($is_adding_sub && $has_classic) || (!$is_adding_sub && $has_sub)) {
            die(json_encode([
                'hasError' => true,
                'errors' => [$this->l('Impossible de mélanger abonnements et achats classiques dans le même panier.')]
            ]));
        }
    }


    public function hookDisplayAdminProductsExtra($params)
    {
        $this->loadModuleClasses();
        $id_product = (int)$params['id_product'];
        $is_sub = (bool)StripePriceLink::getStripePriceIdByPsId($id_product, 0);

        $this->context->smarty->assign([
            'is_stripe_subscription' => $is_sub,
            'id_product' => $id_product
        ]);
        return $this->display(__FILE__, 'views/templates/admin/product_tab.tpl');
    }

    public function hookActionProductUpdate($params)
    {
        $id_product = (int)$params['id_product'];
        $is_sub_requested = (bool)Tools::getValue('is_stripe_subscription');
        if ($is_sub_requested) {
            $this->createStripePriceForProduct(new Product($id_product));
        } else {
            Db::getInstance()->delete('stripe_price_link', 'id_product_ps = ' . $id_product . ' AND id_product_attribute = 0');
        }
    }

    public function hookDisplayAdminProductsCombinationsForm($params)
    {
        $this->loadModuleClasses();
        $id_product = (int)$params['id_product'];
        $id_combination = (int)$params['id_combination'];
        $is_sub = (bool)StripePriceLink::getStripePriceIdByPsId($id_product, $id_combination);

        $this->context->smarty->assign([
            'is_stripe_subscription' => $is_sub,
            'id_combination' => $id_combination
        ]);
        return $this->display(__FILE__, 'views/templates/admin/combination_fields.tpl');
    }

    public function hookActionProductCombinationSave($params)
    {
        $id_product = (int)$params['id_product'];
        $id_comb = (int)$params['id_product_attribute'];
        $is_sub_requested = (bool)Tools::getValue('is_stripe_subscription_' . $id_comb);

        if ($is_sub_requested) {
            $this->createStripePriceForProduct(new Product($id_product), $id_comb);
        } else {
            Db::getInstance()->delete('stripe_price_link', 'id_product_ps = ' . $id_product . ' AND id_product_attribute = ' . $id_comb);
        }
    }


    public function hookPaymentOptions($params)
    {
        if (!$this->active) return [];
        $option = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $option->setModuleName($this->name)
            ->setCallToActionText($this->l('Payer par Carte (Stripe Subscriptions)'))
            ->setAction($this->context->link->getModuleLink($this->name, 'checkout', [], true));
        return [$option];
    }

    public function hookActionFrontControllerSetMedia($params)
    {
        $this->context->controller->registerJavascript('stripe-v3', 'https://js.stripe.com/v3/', ['server' => 'remote']);
    }

    public function hookDisplayCustomerAccount($params)
    {
        $this->context->smarty->assign(['subscription_link' => $this->context->link->getModuleLink($this->name, 'default')]);
        return $this->display(__FILE__, 'views/templates/customer_account.tpl');
    }

    public function hookActionAuthentication($params)
    {
        if (isset($params['customer'])) {
            $c = $params['customer'];
            $this->createOrGetStripeCustomer($c->id, $c->email, $c->firstname, $c->lastname);
        }
    }

    public function hookActionCustomerAccountAdd($params)
    {
        if (isset($params['newCustomer'])) {
            $c = $params['newCustomer'];
            $this->createOrGetStripeCustomer($c->id, $c->email, $c->firstname, $c->lastname);
        }
    }
}