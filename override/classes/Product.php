<?php

class Product extends ProductCore
{
 
    public $custom_field;
    public $custom_field_lang;
    public $custom_field_lang_wysiwyg;
 
    public function __construct($id_product = null, $full = false, $id_lang = null, $id_shop = null, \Context $context = null)
    {
        self::$definition['fields']['helloharel'] = [
            'type' => self::TYPE_BOOL,
            'validate' => 'isBool',
        ];
        
        parent::__construct($id_product, $full, $id_lang, $id_shop, $context);
    }
}
