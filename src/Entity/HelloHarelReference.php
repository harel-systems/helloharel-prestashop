<?php

namespace HelloHarel\Entity;

class HelloHarelReference extends \ObjectModel
{
    const TABLE = 'helloharel_references';
    
    public $object_type;
    public $ps_id;
    public $hh_id;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => self::TABLE,
        'primary' => 'id_reference',
        'fields' => [
            'object_type' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'ps_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'hh_id' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
        ],
    ];
    
    public function getHelloHarelId($type, $id)
    {
        $query = new \DbQuery();
        $query->select('hh_id');
        $query->from(self::TABLE);
        $query->where('object_type = "' . pSQL($type) . '" AND ps_id = ' . (int)$id);
        
        $id = \Db::getInstance()->getValue($query);
        return $id ?: null;
    }
}
