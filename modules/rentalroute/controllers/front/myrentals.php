<?php

class rentalroutemyrentalsModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        if (!$this->context->customer->isLogged()) {
            Tools::redirect('index.php?controller=authentication&back=my-account');
        }

        $myRentals = $this->getCustomerRentals((int)$this->context->customer->id);

        $this->context->smarty->assign([
            'my_rentals' => $myRentals,
        ]);

        $this->setTemplate('module:rentalroute/views/templates/front/my_rentals.tpl');
    }

    private function getCustomerRentals($id_customer)
    {
        $sql = new DbQuery();
        $sql->select('b.*, pl.name AS product_name');
        $sql->from('rentalroute_booking', 'b');
        $sql->leftJoin('product_lang', 'pl', 'pl.id_product = b.id_product AND pl.id_lang = '.(int)$this->context->language->id);
        $sql->where('b.id_customer = '.(int)$id_customer);
        $sql->orderBy('b.date_start DESC');

        return Db::getInstance()->executeS($sql);
    }
}