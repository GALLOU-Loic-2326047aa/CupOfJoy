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
            && $this->registerHook('displayCustomerAccount')
            && $this->registerHook('displayAdminProductsExtra')
            && $this->registerHook('actionProductUpdate')
            && $this->registerHook('actionProductAdd')
            && $this->registerHook('displayProductActions')
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
            "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."rental_product` (
            `id_product` INT(10) UNSIGNED NOT NULL,
            `is_rental` TINYINT(1) NOT NULL DEFAULT 0,
            `price_per_month_12` DECIMAL(20, 6) NOT NULL DEFAULT 0.000000,
            `price_per_month_36` DECIMAL(20, 6) NOT NULL DEFAULT 0.000000,
            `deposit_amount` DECIMAL(20, 6) NOT NULL DEFAULT 0.000000,
            `installation_fee` DECIMAL(20, 6) NOT NULL DEFAULT 0.000000,
            PRIMARY KEY (`id_product`)
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
            $rental_data = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'rental_product` WHERE `id_product` = '.$id_product);
        }

        $this->context->smarty->assign([
            'is_rental' => isset($rental_data['is_rental']) ? $rental_data['is_rental'] : 0,
            'price_per_month_12' => isset($rental_data['price_per_month_12']) ? $rental_data['price_per_month_12'] : 0.00,
            'price_per_month_36' => isset($rental_data['price_per_month_36']) ? $rental_data['price_per_month_36'] : 0.00,
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
        $price_per_month_12 = (float)str_replace(',', '.', Tools::getValue('price_per_month_12'));
        $price_per_month_36 = (float)str_replace(',', '.', Tools::getValue('price_per_month_36'));
        $deposit_amount = (float)str_replace(',', '.', Tools::getValue('deposit_amount'));
        $installation_fee = (float)str_replace(',', '.', Tools::getValue('installation_fee'));

        $sql = "INSERT INTO `"._DB_PREFIX_."rental_product` 
            (id_product, is_rental, price_per_month_12, price_per_month_36, deposit_amount, installation_fee)
            VALUES (
                ".(int)$id_product.", 
                ".(int)$is_rental.", 
                ".(float)$price_per_month_12.", 
                ".(float)$price_per_month_36.", 
                ".(float)$deposit_amount.", 
                ".(float)$installation_fee."
            )
            ON DUPLICATE KEY UPDATE
            is_rental = VALUES(is_rental),
            price_per_month_12 = VALUES(price_per_month_12),
            price_per_month_36 = VALUES(price_per_month_36),
            deposit_amount = VALUES(deposit_amount),
            installation_fee = VALUES(installation_fee)";

        if (!Db::getInstance()->execute($sql)) {
            error_log('Erreur SQL dans updateRentalData: ' . Db::getInstance()->getMsgError());
        }
    }

    // --- HOOKS FRONT-OFFICE ---
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

            $price_12 = $rental_data['price_per_month_12'] ?? 0.00;
            $price_36 = $rental_data['price_per_month_36'] ?? 0.00;
            $deposit = $rental_data['deposit_amount'] ?? 0.00;
            $installation = $rental_data['installation_fee'] ?? 0.00;

            $this->context->smarty->assign([
                'rental_product_id' => $id_product,
                'booking_url' => $this->context->link->getModuleLink('rentalroute', 'booking', []),

                'price_per_month_12' => $price_12,
                'price_per_month_36' => $price_36,
                'deposit_amount' => $deposit,
                'installation_fee' => $installation,

                'price_per_month_12_formatted' => $this->context->getCurrentLocale()->formatPrice($price_12, $currency->iso_code),
                'price_per_month_36_formatted' => $this->context->getCurrentLocale()->formatPrice($price_36, $currency->iso_code),
                'deposit_amount_formatted' => $this->context->getCurrentLocale()->formatPrice($deposit, $currency->iso_code),
                'installation_fee_formatted' => $this->context->getCurrentLocale()->formatPrice($installation, $currency->iso_code),
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
