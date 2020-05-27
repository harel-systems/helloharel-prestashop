<?php

class Product extends ProductCore
{
    /** @var int Managed by Hello Harel */
    public $helloharel;
 
    public function __construct($id_product = null, $full = false, $id_lang = null, $id_shop = null, \Context $context = null)
    {
        self::$definition['fields']['helloharel'] = [
            'type' => self::TYPE_BOOL,
            'validate' => 'isBool',
            'sqlId' => 'helloharel',
        ];
        
        parent::__construct($id_product, $full, $id_lang, $id_shop, $context);
    }
}
