<?php

require_once dirname(__FILE__).'/../../classes/RentalBooking.php';

class AdminRentalRouteController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'rentalroute_booking';
        $this->className = 'RentalBooking';
        $this->identifier = 'id_booking';
        $this->lang = false;

        parent::__construct();

        $this->_join .= ' LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (a.`id_product` = pl.`id_product` AND pl.`id_lang` = ' . (int)$this->context->language->id . ')';
        $this->_join .= ' LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (a.`id_customer` = c.`id_customer`)';
        $this->_select .= 'pl.name AS product_name, CONCAT(c.firstname, " ", c.lastname) AS customer_name';

        $this->fields_list = [
            'id_booking' => ['title' => $this->module->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'product_name' => [
                'title' => $this->module->l('Produit'),
                'filter_key' => 'pl!name',
                'callback' => 'renderProductLink'
            ],
            'customer_name' => [
                'title' => $this->module->l('Client'),
                'filter_key' => 'c!lastname',
                'havingFilter' => true,
                'callback' => 'renderCustomerLink'
            ],
            'date_start' => ['title' => $this->module->l('Début'), 'type' => 'date'],
            'date_end' => ['title' => $this->module->l('Fin'), 'type' => 'date'],
            'total_price' => ['title' => $this->module->l('Prix total'), 'type' => 'price'],
            'status' => ['title' => $this->module->l('Statut'), 'filter_key' => 'a!status'],
        ];

        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    public function renderProductLink($value, $row)
    {
        if (empty($value)) {
            return $this->module->l('Produit supprimé');
        }
        $url = $this->context->link->getAdminLink('AdminProducts', true, [], ['id_product' => (int)$row['id_product'], 'updateproduct' => '1']);
        return '<a href="' . $url . '" target="_blank">' . $value . '</a>';
    }

    public function renderCustomerLink($value, $row)
    {
        if (empty($value) || $row['id_customer'] == 0) {
            return $this->module->l('Client invité ou supprimé');
        }
        $url = $this->context->link->getAdminLink('AdminCustomers', true, [], ['id_customer' => (int)$row['id_customer'], 'viewcustomer' => '1']);
        return '<a href="' . $url . '" target="_blank">' . $value . '</a>';
    }

    public function renderForm()
    {
        $products = Product::getProducts($this->context->language->id, 0, 0, 'name', 'ASC');
        $customers = Customer::getCustomers();
        $customerOptions = [];
        foreach ($customers as $customer) {
            $customerOptions[] = ['id_customer' => $customer['id_customer'], 'name' => $customer['firstname'] . ' ' . $customer['lastname']];
        }

        $this->fields_form = [
            'legend' => ['title' => $this->module->l('Réservation')],
            'input' => [
                ['type' => 'select', 'label' => $this->module->l('Produit'), 'name' => 'id_product', 'options' => ['query' => $products, 'id' => 'id_product', 'name' => 'name'], 'required' => true],
                ['type' => 'select', 'label' => $this->module->l('Client'), 'name' => 'id_customer', 'options' => ['query' => $customerOptions, 'id' => 'id_customer', 'name' => 'name'], 'required' => true],
                ['type' => 'date', 'label' => $this->module->l('Date début'), 'name' => 'date_start', 'required' => true],
                ['type' => 'date', 'label' => $this->module->l('Date fin'), 'name' => 'date_end', 'required' => true],
                ['type' => 'text', 'label' => $this->module->l('Prix total'), 'name' => 'total_price', 'required' => true],
                ['type' => 'select', 'label' => $this->module->l('Statut'), 'name' => 'status', 'options' => ['query' => [['id' => 'pending', 'name' => $this->module->l('En attente')], ['id' => 'confirmed', 'name' => $this->module->l('Confirmée')], ['id' => 'ongoing', 'name' => $this->module->l('En cours')], ['id' => 'finished', 'name' => $this->module->l('Terminée')], ['id' => 'cancelled', 'name' => $this->module->l('Annulée')]], 'id' => 'id', 'name' => 'name'], 'required' => true],
            ],
            'submit' => ['title' => $this->module->l('Sauvegarder')],
        ];

        return parent::renderForm();
    }
}