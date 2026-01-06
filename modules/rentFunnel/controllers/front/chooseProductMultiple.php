<?php

class rentFunnelChooseProductMultipleModuleFrontController extends ModuleFrontController
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
        parent::initContent();

        $categoryList = json_decode(Configuration::get("RENTFUNNEL_CATEGORYLIST"));

        if (empty($categoryList)) {
            Tools::redirect($this->context->link->getPageLink('index'));
            return;
        }

        $currentCategory = $categoryList[0];

        // Récupérer les produits de cette catégorie
        $products = $this->getProducts($currentCategory->name);

        // Récupérer les produits déjà sélectionnés
        $totalSelectedProducts = json_decode(Configuration::get("RENTFUNNEL_SELECTED_PRODUCTS"), true);

        // Assigner les variables au template
        $this->context->smarty->assign([
            'products' => $products,
            'categoryList' => $categoryList,
            'currentCategory' => $currentCategory,
            'page_title' => 'Sélectionnez vos produits - ' . $currentCategory->name,
            'shop_url' => $this->context->shop->getBaseURL(),
            'shop_currency' => $this->context->currency->symbol,
        ]);

        // Définir le template à utiliser
        $this->setTemplate('module:rentFunnel/views/templates/front/chooseProductMultiple.tpl');
    }

    private function getProducts($categoryName)
    {
        return RentFunnelObjectModel::getCategoryProducts($categoryName);
    }
}