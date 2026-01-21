<?php

class MachineSupportType extends ObjectModel
{
    public $id;
    public $name; // Le nom du type (ex: Panne, Remboursement...)
    public $active = true;

    public static $definition = [
        'table' => 'support_client_type',
        'primary' => 'id_support_client_type',
        'multilang' => true, // On gère le multilingue
        'fields' => [
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
        ],
    ];

    // Fonction qui récupère tout les type de demande actif
    public static function getTypes($id_lang)
    {
        $sql = 'SELECT a.id_support_client_type, b.name
                FROM ' . _DB_PREFIX_ . 'support_client_type a
                LEFT JOIN ' . _DB_PREFIX_ . 'support_client_type_lang b ON (a.id_support_client_type = b.id_support_client_type)
                WHERE b.id_lang = ' . (int)$id_lang . ' AND a.active = 1';

        return Db::getInstance()->executeS($sql);
    }
}