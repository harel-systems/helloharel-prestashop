<?php
/**
 * Hello Harel PrestaShop integration module
 * Copyright (C) 2020  Harel Systems SAS
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

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
            return 'Could not create reference table';
        }
        if(!Db::getInstance()->execute('ALTER TABLE ' . _DB_PREFIX_ . 'orders CHANGE `reference` `reference` VARCHAR(255) DEFAULT NULL')) {
            return 'Could not fix order reference size';
        }
        if(!Db::getInstance()->execute('ALTER TABLE ' . _DB_PREFIX_ . 'order_payment CHANGE `order_reference` `order_reference` VARCHAR(255) DEFAULT NULL')) {
            return 'Could not fix order reference size';
        }
        
        return true;
    }
}
