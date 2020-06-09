<?php

class HelloHarelReference extends \ObjectModel
{
    public $object_type;
    public $ps_id;
    public $hh_id;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'helloharel_references',
        'primary' => 'id_reference',
        'fields' => [
            'object_type' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'ps_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'hh_id' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
        ],
    ];
}
