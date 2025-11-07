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

    public $productImages = [];

    public static $definition = [
        'table' => 'offer_block',
        'primary' => 'id_offer_block',
        'fields' => [
            'id_offer_block'    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'name'              => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 255],
            'image'             => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 255],
            'product1_id'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'product2_id'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'product3_id'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'product4_id'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
        ],
    ];

    private function getProductImages($id_lang)
    {
        if($id_lang == null)
        {
            $id_lang = Context::getContext()->language->id;
        }

        for($i = 1 ; $i <= 4 ; ++$i)
        {
            $productId = $this->{'product'.$i.'_id'};
            if($productId)
            {
                $product = new Product($productId, false, $id_lang);
                $cover = Product::getCover($productId);
                if ($cover && Validate::isLoadedObject($product)) {
                    $image = new Image($cover['id_image']);
                    $this->productImages[$i] = Context::getContext()->link->getImageLink(
                        $product->link_rewrite,
                        $image->id,
                        ImageType::getFormattedName('home')
                    );
                } else {
                    $this->productImages[$i] = null;
                }
            }
        }
    }

    public static function getOfferBlocks($id_lang = null)
    {
        $sql = 'SELECT id_offer_block FROM ' . _DB_PREFIX_ . 'offer_block ORDER BY id_offer_block ASC';
        $ids = Db::getInstance()->executeS($sql);

        $offerBlocks = [];
        if ($ids) {
            foreach ($ids as $row) {
                $offerBlock = new self($row['id_offer_block']);
                if (Validate::isLoadedObject($offerBlock)) {
                    $offerBlock->getProductImages($id_lang);
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