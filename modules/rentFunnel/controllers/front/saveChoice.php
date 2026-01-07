<?php

class RentFunnelSaveChoiceModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $categoryList = json_decode(Configuration::get("RENTFUNNEL_CATEGORYLIST"));
        $category = $categoryList[0];
        $product_id = Tools::getValue('product_id'); // bien nom ‘product’ pour correspondre aux URLs
        $product = RentFunnelObjectModel::getProductById($product_id);

        $totalSelectedProducts = json_decode(Configuration::get("RENTFUNNEL_SELECTED_PRODUCTS"), true);

        // Ajout du produit simple s'il existe
        if ($categoryList != [] && !is_null($product) && $product_id) {
            if (!isset($totalSelectedProducts[$category->name])) {
                $totalSelectedProducts[$category->name] = [];
            }
            $totalSelectedProducts[$category->name][$product['id_product']] = [
                'name' => $product['name'],
                'description' => $product['description'],
                'price' => $product['price'],
                'quantity' => 1,
            ];
        }

        // Traitement POST des produits multi
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $product_quantities = Tools::getValue('product_quantities', []);
            $product_info = Tools::getValue('product_info', []);
            $selected_products = [];
            foreach ($product_quantities as $id => $qty) {
                if ($qty > 0) {
                    $selected_products[$id] = [
                        'name' => $product_info[$id]['name'] ?? null,
                        'description' => $product_info[$id]['description'] ?? null,
                        'price' => $product_info[$id]['price'] ?? null,
                        'quantity' => (int)$qty,
                    ];
                }
            }
            if (!empty($selected_products)) {
                $totalSelectedProducts[$category->name] = $selected_products;
            }
        }

        array_splice($categoryList, 0, 1);
        Configuration::updateValue("RENTFUNNEL_CATEGORYLIST", json_encode($categoryList));

        // Détermination de la prochaine page selon multiselect
        $nextPage = empty($categoryList) ? 'recap' : ($categoryList[0]->multiselect ? 'chooseProductMultiple' : 'chooseProductSimple');

        Configuration::updateValue("RENTFUNNEL_SELECTED_PRODUCTS", json_encode($totalSelectedProducts));
        Tools::redirect($this->context->link->getModuleLink('rentFunnel', $nextPage));
    }
}
