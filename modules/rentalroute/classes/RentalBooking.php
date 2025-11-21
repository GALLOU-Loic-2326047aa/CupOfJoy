<?php

class RentalBooking extends ObjectModel
{
    public $id_product;
    public $id_customer;
    public $date_start;
    public $date_end;
    public $total_price;
    public $status;

    public static $definition = [
        'table' => 'rentalroute_booking',
        'primary' => 'id_booking',
        'fields' => [
            'id_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'date_start' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'date_end' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'total_price' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'status' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
        ],
    ];
}