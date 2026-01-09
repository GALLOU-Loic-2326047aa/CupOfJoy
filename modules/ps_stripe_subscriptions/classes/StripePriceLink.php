<?php

class StripePriceLink extends ObjectModel
{
    public $id_product_ps;
    public $id_product_attribute;
    public $id_product_stripe;
    public $id_price_stripe;

    public static $definition = array(
        'table' => 'stripe_price_link',
        'primary' => 'id_product_ps',
        'fields' => array(
            'id_product_ps'        => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'id_product_attribute' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'id_product_stripe'    => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'id_price_stripe'      => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
        ),
    );

    public static function getStripePriceIdByPsId($id_product, $id_product_attribute = 0)
    {
        $sql = new DbQuery();
        $sql->select('id_price_stripe');
        $sql->from('stripe_price_link');
        $sql->where('id_product_ps = ' . (int)$id_product);
        $sql->where('id_product_attribute = ' . (int)$id_product_attribute);

        return Db::getInstance()->getValue($sql);
    }
}