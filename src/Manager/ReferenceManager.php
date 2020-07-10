<?php

namespace HelloHarel\Manager;

use Db;

class ReferenceManager extends AbstractManager
{
    public function install()
    {
        $query = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'helloharel_references` (
            `id_reference` INT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `object_type` VARCHAR(50) NOT NULL,
            `ps_id` int(20) UNSIGNED NOT NULL,
            `hh_id` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id_reference`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;';

        if(!Db::getInstance()->execute($query)) {
            $this->app->_errors[] = 'Could not create reference table';
            return false;
        }
        if(!Db::getInstance()->execute('ALTER TABLE ' . _DB_PREFIX_ . 'orders CHANGE `reference` `reference` VARCHAR(255) DEFAULT NULL')) {
            $this->app->_errors[] = 'Could not fix order reference size';
            return false;
        }
        if(!Db::getInstance()->execute('ALTER TABLE ' . _DB_PREFIX_ . 'order_payment CHANGE `order_reference` `order_reference` VARCHAR(255) DEFAULT NULL')) {
            $this->app->_errors[] = 'Could not fix order reference size';
            return false;
        }
        
        return true;
    }
}
