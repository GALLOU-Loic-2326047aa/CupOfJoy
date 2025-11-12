<?php
class PsBannerItem extends ObjectModel
{
    public $id_ps_banner_item;
    public $image;
    public $link;
    public $description;

    public static $definition = [
        'table' => 'ps_banner_item',
        'primary' => 'id_ps_banner_item',
        'fields' => [
            'id_ps_banner_item' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'image'             => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 255],
            'link'              => ['type' => self::TYPE_STRING, 'validate' => 'isUrl', 'required' => false, 'size' => 255],
            'description'       => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 512],
        ],
    ];

    /**
     * Retourne un objet PsBannerItem par son ID
     */
    public static function getBannerById($id)
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

    /**
     * Retourne un tableau d’objets PsBannerItem
     */
    public static function getBanners()
    {
        $sql = 'SELECT id_ps_banner_item FROM ' . _DB_PREFIX_ . 'ps_banner_item ORDER BY id_ps_banner_item ASC';
        $ids = Db::getInstance()->executeS($sql);

        $banners = [];
        if ($ids) {
            foreach ($ids as $row) {
                $banner = new self($row['id_ps_banner_item']);
                if (Validate::isLoadedObject($banner)) {
                    $banners[] = $banner;
                }
            }
        }
        return $banners;
    }

    /**
     * Supprime l'objet et éventuellement le fichier image associé
     */
    public function delete()
    {
        // Supprimer le fichier image si existant
        $imagePath = _PS_MODULE_DIR_ . 'ps_banner/img/' . $this->image;
        if ($this->image && file_exists($imagePath)) {
            @unlink($imagePath);
        }

        return parent::delete();
    }
}
