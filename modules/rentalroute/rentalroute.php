<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class rentalroute extends Module
{
    public function __construct()
    {
        $this->name = 'rentalroute';
        $this->tab = 'Rental';
        $this->version = '1.0.0';
        $this->author = 'GURREA.K';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Rental Route');
        $this->description = $this->l('Module de location avec durée, dépôt et frais d’installation.');
    }

    public function install()
    {
        return parent::install()
            && $this->installDatabase()
            && $this->installTab()
            && $this->registerHook('moduleRoutes')
            && $this->registerHook('displayCustomerAccount')
            && $this->registerHook('displayAdminProductsExtra')
            && $this->registerHook('actionProductUpdate')
            && $this->registerHook('actionProductAdd')
            && $this->registerHook('displayProductActions')
            && $this->registerHook('displayHeader')
            && $this->registerHook('actionValidateOrder');
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->uninstallDatabase();
    }

    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminRentalRoute';
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Gestion des locations';
        }
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminParentOrders');
        $tab->module = $this->name;
        return $tab->add();
    }

    private function installDatabase()
    {
        $sql_queries = [
            "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."rentalroute_product` (
            `id_rentalroute_product` INT AUTO_INCREMENT PRIMARY KEY,
            `id_product` INT NOT NULL,
            `rental_price_day` DECIMAL(20,6) NOT NULL,
            `rental_price_week` DECIMAL(20,6),
            `deposit` DECIMAL(20,6),
            `installation_fee` DECIMAL(20,6),
            `min_duration` INT,
            `max_duration` INT
        ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8;",

            "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."rentalroute_booking` (
            `id_booking` INT AUTO_INCREMENT PRIMARY KEY,
            `id_product` INT NOT NULL,
            `id_customer` INT NOT NULL,
            `quantity` INT NOT NULL DEFAULT 1,
            `date_start` DATE NOT NULL,
            `date_end` DATE NOT NULL,
            `total_price` DECIMAL(20,6) NOT NULL,
            `status` ENUM('pending','confirmed','ongoing','finished','cancelled') DEFAULT 'pending'
        ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8;",

            "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."rental_product` (
            `id_product` INT(10) UNSIGNED NOT NULL,
            `is_rental` TINYINT(1) NOT NULL DEFAULT 0,
            `deposit_amount` DECIMAL(20, 6) NOT NULL DEFAULT 0.000000,
            `installation_fee` DECIMAL(20, 6) NOT NULL DEFAULT 0.000000,
            PRIMARY KEY (`id_product`)
        ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8;"
        ];

        foreach ($sql_queries as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }
        return true;
    }

    private function uninstallDatabase()
    {
        $sql_queries = [
            "DROP TABLE IF EXISTS `"._DB_PREFIX_."rentalroute_booking`",
            "DROP TABLE IF EXISTS `"._DB_PREFIX_."rentalroute_product`",
            "DROP TABLE IF EXISTS `"._DB_PREFIX_."rental_product`"
        ];

        foreach ($sql_queries as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }
        return true;
    }

    // --- HOOKS BACK-OFFICE ---
    public function hookDisplayAdminProductsExtra($params)
    {
        $id_product = (int)$params['id_product'];
        $rental_data = [];

        if ($id_product) {
            $rental_data = Db::getInstance()->getRow(
                'SELECT * FROM `'._DB_PREFIX_.'rental_product` WHERE `id_product` = '.$id_product
            );
        }

        $this->context->smarty->assign([
            'is_rental' => isset($rental_data['is_rental']) ? $rental_data['is_rental'] : 0,
            'deposit_amount' => isset($rental_data['deposit_amount']) ? $rental_data['deposit_amount'] : 0.00,
            'installation_fee' => isset($rental_data['installation_fee']) ? $rental_data['installation_fee'] : 0.00,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/product_extra_tab.tpl');
    }

    public function hookActionProductAdd($params)
    {
        if (isset($params['id_product'])) {
            $this->updateRentalData((int)$params['id_product']);
        }
    }

    public function hookActionProductUpdate($params)
    {
        if (isset($params['product']->id)) {
            $this->updateRentalData((int)$params['product']->id);
        }
    }

    private function updateRentalData($id_product)
    {
        if (!$id_product) {
            return;
        }

        $is_rental = (int)Tools::getValue('is_rental');
        $deposit_amount = (float)str_replace(',', '.', Tools::getValue('deposit_amount'));
        $installation_fee = (float)str_replace(',', '.', Tools::getValue('installation_fee'));

        $sql = "INSERT INTO `"._DB_PREFIX_."rental_product` (id_product, is_rental, deposit_amount, installation_fee)
            VALUES (
                ".(int)$id_product.", 
                ".(int)$is_rental.", 
                ".(float)$deposit_amount.", 
                ".(float)$installation_fee."
            )
            ON DUPLICATE KEY UPDATE
            is_rental = VALUES(is_rental),
            deposit_amount = VALUES(deposit_amount),
            installation_fee = VALUES(installation_fee)";

        Db::getInstance()->execute($sql);
    }

    public function hookModuleRoutes()
    {
        return [
            'module-rentalroute-booking' => [
                'rule' => 'module/rentalroute/booking',
                'keywords' => [],
                'controller' => 'booking',
                'params' => [
                    'fc' => 'module',
                    'module' => $this->name,
                ],
            ],
        ];
    }

    // --- HOOKS FRONT-OFFICE ---
    public function hookDisplayHeader()
    {
        if ($this->context->controller->php_self !== 'product') {
            return;
        }

        $id_product = (int)Tools::getValue('id_product');

        if ($id_product > 0) {
            $is_rental = (int)Db::getInstance()->getValue(
                'SELECT `is_rental` FROM `'._DB_PREFIX_.'rental_product` WHERE `id_product` = '.$id_product
            );

            if ($is_rental == 1) {
                $this->context->smarty->assign('hide_add_to_cart_script', true);
                return $this->display(__FILE__, 'views/templates/hook/header.tpl');
            }
        }
    }

    public function hookDisplayProductActions($params)
    {
        $id_product = 0;
        if (isset($params['product']['id_product'])) {
            $id_product = (int)$params['product']['id_product'];
        } elseif (isset($params['product']->id)) {
            $id_product = (int)$params['product']->id;
        }

        if (!$id_product) {
            return;
        }

        $rental_data = Db::getInstance()->getRow(
            'SELECT * FROM `'._DB_PREFIX_.'rental_product` WHERE `id_product` = '.$id_product
        );

        if ($rental_data && $rental_data['is_rental'] == 1) {
            $currency = $this->context->currency;

            $deposit_formatted = $this->context->getCurrentLocale()->formatPrice(
                $rental_data['deposit_amount'],
                $currency->iso_code
            );
            $installation_formatted = $this->context->getCurrentLocale()->formatPrice(
                $rental_data['installation_fee'],
                $currency->iso_code
            );

            $this->context->smarty->assign([
                'rental_product_id' => $id_product,
                'booking_url' => $this->context->link->getModuleLink($this->name, 'booking', []),
                'deposit_amount' => $rental_data['deposit_amount'],
                'installation_fee' => $rental_data['installation_fee'],
                'deposit_amount_formatted' => $deposit_formatted,
                'installation_fee_formatted' => $installation_formatted,
            ]);

            return $this->display(__FILE__, 'views/templates/front/rentalform.tpl');
        }
    }

    public function hookDisplayCustomerAccount()
    {
        $myRentalsUrl = $this->context->link->getModuleLink($this->name, 'myrentals');

        $this->context->smarty->assign([
            'my_rentals_url' => $myRentalsUrl,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/my_account_link.tpl');
    }

    public function hookActionValidateOrder($params)
    {
        return true;
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminRentalRoute'));
    }
}
