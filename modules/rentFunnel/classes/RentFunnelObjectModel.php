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

    public static function getRentFunnelOrder($id_lang = null)
    {
        if ($id_lang === null) {
            $id_lang = Context::getContext()->language->id;
        }

        $sql = "SELECT rf.id_category, rf.name, rf.position, rf.multiselect, rf.skippable
                FROM " . _DB_PREFIX_ . "rentFunnel_order rf
                ORDER BY rf.position ASC";

        return Db::getInstance()->executeS($sql);
    }

    public static function getCategoryProducts($categoryName, $id_lang = null)
    {
        if ($id_lang === null) {
            $id_lang = Context::getContext()->language->id;
        }

        // Vérifier si la table existe en essayant une requête simple
        $tableExists = false;
        try {
            $result = Db::getInstance()->executeS("SHOW TABLES LIKE '" . _DB_PREFIX_ . "stripe_price_link'");
            $tableExists = !empty($result);
        } catch (Exception $e) {
            $tableExists = false;
        }

        if ($tableExists) {
            $sql = "SELECT p.id_product, p.price, pl.name, pl.description
                FROM " . _DB_PREFIX_ . "product p
                JOIN " . _DB_PREFIX_ . "product_lang pl ON p.id_product = pl.id_product
                JOIN " . _DB_PREFIX_ . "category_product cp ON p.id_product = cp.id_product
                JOIN " . _DB_PREFIX_ . "category c ON cp.id_category = c.id_category
                JOIN " . _DB_PREFIX_ . "category_lang cl ON c.id_category = cl.id_category
                JOIN " . _DB_PREFIX_ . "stripe_price_link spl ON p.id_product = spl.id_product_ps
                WHERE spl.id_product_ps = p.id_product
                AND cl.name = '" . pSQL($categoryName) . "'
                AND cl.id_lang = " . (int)$id_lang . "
                AND pl.id_lang = " . (int)$id_lang . "
                AND p.active = 1
                ORDER BY cp.position ASC";
        } else {
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
        }

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

    public static function getCompanyInfo($companyId)
    {
        $sql = "SELECT ci.company_size, ci.consumption, ci.additional_drinks, ci.dynamic_answers
                FROM " . _DB_PREFIX_ . "rentFunnel_company_info ci
                WHERE ci.company_id = '" . (int)$companyId . "'";

        $result = Db::getInstance()->getRow($sql);
        if (!$result)
        {
            return [
                'company_size' => '',
                'consumption' => '',
                'additional_drinks' => '',
                'dynamic_answers' => '',
            ];
        }

        if(!empty($result['additional_drinks']))
        {
            $result['additional_drinks'] = json_decode($result['additional_drinks'], true) ?: [];
        }

        if(!empty($result['dynamic_answers']))
        {
            $result['dynamic_answers'] = json_decode($result['dynamic_answers'], true) ?: [];
        }

        return $result;
    }

    public static function setCompanyInfo($companyId, $companyInfo)
    {
        $companySize = pSQL($companyInfo['company_size']);
        $consumption = pSQL($companyInfo['consumption']);
        $additionalDrinks = isset($companyInfo['additional_drinks']) ? pSQL($companyInfo['additional_drinks']) : '';
        if (is_array($additionalDrinks)) {
            $additionalDrinksJson = pSQL(json_encode($additionalDrinks));
        } else {
            $additionalDrinksJson = pSQL(json_encode([$additionalDrinks]));
        }

        // Gérer les réponses dynamiques
        $dynamicAnswers = isset($companyInfo['dynamic_answers']) ? $companyInfo['dynamic_answers'] : '[]';
        if (is_string($dynamicAnswers)) {
            $dynamicAnswersJson = pSQL($dynamicAnswers);
        } else {
            $dynamicAnswersJson = pSQL(json_encode($dynamicAnswers));
        }

        $existingId = Db::getInstance()->getValue(
            "SELECT id_rentFunnel_company_info FROM " . _DB_PREFIX_ . "rentFunnel_company_info
                    WHERE company_id = " . (int)$companyId
        );

        if($existingId) {
            $sql = "UPDATE " . _DB_PREFIX_ . "rentFunnel_company_info
                        SET company_size = '$companySize',
                            consumption = '$consumption',
                            additional_drinks = '$additionalDrinksJson',
                            dynamic_answers = '$dynamicAnswersJson'
                        WHERE company_id = " . (int)$companyId;
        } else {
            $sql = "INSERT INTO " . _DB_PREFIX_ . "rentFunnel_company_info
                (company_id, company_size, consumption, additional_drinks, dynamic_answers)
                VALUES ('$companyId', '$companySize', '$consumption', '$additionalDrinksJson', '$dynamicAnswersJson')";
        }

        Db::getInstance()->execute($sql);
    }

    public static function getDrinkTypes($id_lang = null)
    {
        if ($id_lang === null) {
            $id_lang = Context::getContext()->language->id;
        }

        $sql = "SELECT cl.id_category, cl.name 
                    FROM " . _DB_PREFIX_ . "category_lang cl
                    JOIN " . _DB_PREFIX_ . "category c ON cl.id_category = c.id_category
                    JOIN " . _DB_PREFIX_ . "category_lang parent_cl ON c.id_parent = parent_cl.id_category
                    WHERE parent_cl.name = 'Boissons' 
                    AND cl.id_lang = " . $id_lang . " 
                    AND parent_cl.id_lang = " . $id_lang;

        $result = Db::getInstance()->executeS($sql);
        if (!$result)
        {
            return [];
        }

        return $result;
    }
}