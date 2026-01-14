<?php

require_once "modules/rentFunnel/classes/RentFunnelObjectModel.php";

class rentFunnelChooseProductSimpleModuleFrontController extends ModuleFrontController
{

    public function setMedia()
    {
        parent::setMedia();
        $this->registerStylesheet(
            'rentfunnel-header',
            'modules/rentFunnel/views/css/header.css',
            ['media' => 'all', 'priority' => 150]
        );
        $this->registerStylesheet(
            'rentfunnel-page',
            'modules/rentFunnel/views/css/page.css',
            ['media' => 'all', 'priority' => 150]
        );
    }
    public function initContent()
    {
        $categoryList = json_decode(Configuration::get("RENTFUNNEL_CATEGORYLIST"));
        $products = $this->getProducts($categoryList[0]->name);


        $totalSelectedProducts = json_decode(Configuration::get("RENTFUNNEL_SELECTED_PRODUCTS"), true);

        parent::initContent();

        $this->context->smarty->assign([
            'products' => $products,
            'categoryList' => $categoryList,
            'page_title' => 'Veuillez faire votre choix',
            'shop_url' => $this->context->shop->getBaseURL(),
            'shop_currency' => $this->context->currency->symbol,
        ]);

        $this->setTemplate('module:rentFunnel/views/templates/front/chooseProductSimple.tpl');
    }

    private function getProducts($category)
    {
        return RentFunnelObjectModel::getCategoryProducts($category);
    }
}