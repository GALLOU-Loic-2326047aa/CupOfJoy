<?php

class rentFunnelRecapModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $categoryList = [];
        $total_price = 0;

        $totalSelectedProducts = json_decode(Configuration::get("RENTFUNNEL_SELECTED_PRODUCTS"), true);

        foreach ($totalSelectedProducts as $categoryName => $products)
        {
            $categoryList[$categoryName] = [
                'name' => $categoryName,
                'products' => []
            ];

            foreach ($products as $productId => $product)
            {
                $categoryList[$categoryName]['products'][$productId] = $product;

                if(isset($product['price']) && isset($product['quantity']))
                {
                    $total_price += floatval($product['price']) * intval($product['quantity']);
                }
                else if(isset($product['price']))
                {
                    $total_price += floatval($product['price']);
                }
            }
        }

        $this->context->smarty->assign([
            'category_list' => $categoryList,
            'total_price' => $total_price,
            'page_title' => 'Récapitulatif :',
            'shop_url' => $this->context->shop->getBaseURL(),
            'shop_currency' => $this->context->currency->symbol,
        ]);

        $this->setTemplate('module:rentFunnel/views/templates/front/recap.tpl');
    }

    public function addProductsToCart()
    {
        $totalSelectedProducts = json_decode(Configuration::get("RENTFUNNEL_SELECTED_PRODUCTS"), true);

        if (empty($totalSelectedProducts)) {
            return false;
        }

        $cart = $this->context->cart;

        if (!$cart->id) {
            $cart->id_lang = (int)$this->context->cookie->id_lang;
            $cart->id_currency = (int)$this->context->cookie->id_currency;
            $cart->id_customer = (int)$this->context->customer->id;
            $cart->id_shop_group = (int)$this->context->shop->id_shop_group;
            $cart->id_shop = $this->context->shop->id;

            if ($this->context->customer->isLogged()) {
                $cart->id_address_delivery = (int)Address::getFirstCustomerAddressId($cart->id_customer);
                $cart->id_address_invoice = $cart->id_address_delivery;
            }

            $cart->add();
            $this->context->cookie->id_cart = (int)$cart->id;
        }

        foreach ($totalSelectedProducts as $categoryName => $products) {
            foreach ($products as $productId => $product) {
                $quantity = isset($product['quantity']) ? (int)$product['quantity'] : 1;

                Configuration::updateValue('ADD_PRODUCT_TO_CART_ID', $productId);
                $cart->updateQty($quantity, (int)$productId, null, false, 'up');
            }
        }

        $cart->update();

        return true;
    }

    public function postProcess()
    {
        parent::postProcess();

        if (Tools::isSubmit('addToCart')) {
            if ($this->addProductsToCart()) {
                Tools::redirect($this->context->link->getPageLink('cart', true));
            } else {
                $this->errors[] = $this->module->l('Une erreur est survenue lors de l\'ajout au panier.');
            }
        }
    }
}