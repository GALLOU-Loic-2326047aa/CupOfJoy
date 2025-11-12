<?php

class MachinesObjectModel extends ObjectModel
{
    public static function getMachines()
    {
        $id_lang = Context::getContext()->language->id;
        $sql = "SELECT p.id_product, p.price, pl.description, pl.name, 
                       CONCAT('/img/p/',
                                       IF(CHAR_LENGTH(pi.id_image) >= 5, CONCAT(SUBSTRING(pi.id_image, -5, 1), '/'), ''),
                                       IF(CHAR_LENGTH(pi.id_image) >= 4, CONCAT(SUBSTRING(pi.id_image, -4, 1), '/'), ''),
                                       IF(CHAR_LENGTH(pi.id_image) >= 3, CONCAT(SUBSTRING(pi.id_image, -3, 1), '/'), ''),
                                       IF(CHAR_LENGTH(pi.id_image) >= 2, CONCAT(SUBSTRING(pi.id_image, -2, 1), '/'), ''),
                                       IF(CHAR_LENGTH(pi.id_image) >= 1, CONCAT(SUBSTRING(pi.id_image, -1, 1), '/'), ''),
                                       pi.id_image, '.jpg') AS image_url
                        FROM " . _DB_PREFIX_ . "product p
                        JOIN " . _DB_PREFIX_ . "product_lang pl ON p.id_product = pl.id_product
                        JOIN " . _DB_PREFIX_ . "category_product cp ON cp.id_product = p.id_product
                        JOIN " . _DB_PREFIX_ . "category_lang cl ON cl.id_category = cp.id_category
                        LEFT JOIN " . _DB_PREFIX_ . "configuration conf ON conf.name = 'PS_SHOP_DOMAIN'
                        LEFT JOIN " . _DB_PREFIX_ . "image pi ON p.id_product = pi.id_product AND pi.cover = 1
                        WHERE cl.name = 'Machines' AND pl.id_lang = " . $id_lang;

        return Db::getInstance()->executeS($sql);
    }
}