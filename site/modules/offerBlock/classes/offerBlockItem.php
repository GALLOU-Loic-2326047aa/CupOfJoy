<?php

class offerBlockItem extends ObjectModel
{
    public $id_offer_block;
    public $name;
    public $image;
    public $product1_id;
    public $product2_id;
    public $product3_id;
    public $product4_id;

    public static $definition = [
        'table' => 'offer_block',
        'primary' => 'id_offer_block',
        'fields' => [
            'id_offer_block'    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'name'              => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 255],
            'image'             => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 255],
            'product1_id'       => ['type' => self::TYPE_INT, 'validate' => 'isString', 'required' => true, 'size' => 10],
            'product2_id'       => ['type' => self::TYPE_INT, 'validate' => 'isString', 'required' => true, 'size' => 10],
            'product3_id'       => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true, 'size' => 10],
            'product4_id'       => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true, 'size' => 10],
        ],
    ];

    public static function getOfferBlocks()
    {
        $sql = 'SELECT id_offer_block FROM ' . _DB_PREFIX_ . 'offer_block ORDER BY id_offer_block ASC';
        $ids = Db::getInstance()->executeS($sql);

        $offerBlocks = [];
        if ($ids) {
            foreach ($ids as $row) {
                $offerBlock = new self($row['id_ps_banner_item']);
                if (Validate::isLoadedObject($offerBlock)) {
                    $offerBlocks[] = $offerBlock;
                }
            }
        }
        return $offerBlocks;
    }

    public static function getOfferBlockById($id)
    {
        if (!(int)$id) {
            return false;
        }
        $obj = new self((int)$id);
        if (Validate::isLoadedObject($obj)) {
            return $obj;
        }
        return false;
    }
}