<?php

class StripeCustomerLink extends ObjectModel {
    public $id_customer_ps;
    public $id_customer_stripe;

    public static $definition = [
        'table' => 'stripe_customer_link',
        'primary' => 'id_customer_ps',
        'fields' => [
            'id_customer_ps' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true,
                'db_type' => 'INT(10) UNSIGNED',
            ],
            'id_customer_stripe' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => true,
                'db_type' => 'VARCHAR(50)',
            ],
        ],
    ];

    public static function getStripeIdByPsId($id_customer_ps)
    {
        return Db::getInstance()->getValue('
            SELECT `id_customer_stripe`
            FROM `' . _DB_PREFIX_ . 'stripe_customer_link`
            WHERE `id_customer_ps` = ' . (int)$id_customer_ps
        );
    }
}