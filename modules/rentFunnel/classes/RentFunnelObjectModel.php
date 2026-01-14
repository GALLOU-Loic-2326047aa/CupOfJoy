<?php

class RentFunnelObjectModel extends ObjectModel
{
    public static function getProductById($product_id, $id_lang = null)
    {
        if ($id_lang === null) {
            $id_lang = Context::getContext()->language->id;
        }

        $sql = "SELECT p.id_product, p.price, pl.description, pl.name 
            FROM " . _DB_PREFIX_ . "product p
            JOIN " . _DB_PREFIX_ . "product_lang pl ON p.id_product = pl.id_product
            WHERE p.id_product = " . (int)$product_id . " AND pl.id_lang = " . (int)$id_lang;

        $results = Db::getInstance()->executeS($sql);

        return isset($results[0]) ? $results[0] : null;
    }

    public static function getCategories($id_lang = null)
    {
        if ($id_lang === null) {
            $id_lang = Context::getContext()->language->id;
        }

        $sql = "SELECT c.id_category, cl.name 
                FROM " . _DB_PREFIX_ . "category c
                JOIN " . _DB_PREFIX_ . "category_lang cl ON c.id_category = cl.id_category
                WHERE cl.id_lang = " . (int)$id_lang . "
                AND c.active = 1
                AND cl.name != 'Racine'
                ORDER BY cl.name ASC";

        return Db::getInstance()->executeS($sql);
    }

    public static function getRentFunnel($id_lang = null)
    {
        if ($id_lang === null) {
            $id_lang = Context::getContext()->language->id;
        }

        $sql = "SELECT rf.id_category, rf.name, rf.position, rf.multiselect, rf.skippable
                FROM " . _DB_PREFIX_ . "rentFunnel rf
                ORDER BY rf.position ASC";

        return Db::getInstance()->executeS($sql);
    }

    public static function getCategoryProducts($categoryName, $id_lang = null)
    {
        if ($id_lang === null) {
            $id_lang = Context::getContext()->language->id;
        }

        // Récupérer les produits de cette catégorie en joignant directement avec category_lang
        $sql = "SELECT p.id_product, p.price, pl.name, pl.description
                FROM " . _DB_PREFIX_ . "product p
                JOIN " . _DB_PREFIX_ . "product_lang pl ON p.id_product = pl.id_product
                JOIN " . _DB_PREFIX_ . "category_product cp ON p.id_product = cp.id_product
                JOIN " . _DB_PREFIX_ . "category c ON cp.id_category = c.id_category
                JOIN " . _DB_PREFIX_ . "category_lang cl ON c.id_category = cl.id_category
                WHERE cl.name = '" . pSQL($categoryName) . "'
                AND cl.id_lang = " . (int)$id_lang . "
                AND pl.id_lang = " . (int)$id_lang . "
                AND p.active = 1
                ORDER BY cp.position ASC";

        $products = Db::getInstance()->executeS($sql);

        if (!$products) {
            return [];
        }

        // Ajouter le chemin de l'image pour chaque produit
        foreach ($products as &$product) {
            $product['image_url'] = self::getProductImagePath($product['id_product']);
        }

        return $products;
    }

    private static function getProductImagePath($id_product)
    {
        // Récupérer l'image de couverture du produit
        $sql = "SELECT i.id_image FROM " . _DB_PREFIX_ . "image i WHERE i.id_product = " . (int)$id_product . " AND i.cover = 1";

        $image = Db::getInstance()->getRow($sql);

        if ($image) {
            $id_image = $image['id_image'];

            // Construire le chemin de l'image selon la structure PrestaShop
            // Ex: /img/p/1/2/3/123-home_default.jpg
            $imageId = str_split($id_image);
            $path = '/img/p/';

            foreach ($imageId as $digit) {
                $path .= $digit . '/';
            }

            $path .= $id_image . '-home_default.jpg';

            return $path;
        }

        // Image par défaut si aucune image n'est trouvée
        return '/img/p/fr-default-home_default.jpg';
    }
}