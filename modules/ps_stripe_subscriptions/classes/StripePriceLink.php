<?php

class StripePriceLink extends ObjectModel
{
    public $id_product_ps;
    public $id_price_stripe;
    public $id_product_stripe;

    public static $definition = [
        'table' => 'stripe_price_link',
        'primary' => 'id_product_ps',
        'fields' => [
            'id_product_ps' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true,
                'db_type' => 'INT(10) UNSIGNED',
            ],
            'id_price_stripe' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => true,
                'db_type' => 'VARCHAR(50)',
            ],
            'id_product_stripe' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => true,
                'db_type' => 'VARCHAR(50)',
            ],
        ],
    ];

    public static function getStripePriceIdByPsId($id_product_ps)
    {
        return Db::getInstance()->getValue('
            SELECT `id_price_stripe`
            FROM `' . _DB_PREFIX_ . 'stripe_price_link`
            WHERE `id_price_stripe` = "' . pSQL($id_product_ps) . '"
        ');
    }
}