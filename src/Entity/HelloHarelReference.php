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
 *
 * @author      Maxime Corteel
 * @copyright   Harel Systems SAS
 * @license     http://opensource.org/licenses/AGPL-3.0 AGPL-3.0
 */

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
    
    public function countReferences($type)
    {
        $query = new \DbQuery();
        $query->select('COUNT(DISTINCT hh_id)');
        $query->from(self::TABLE);
        $query->where('object_type = "' . pSQL($type) . '"');
        
        $id = \Db::getInstance()->getValue($query);
        return $id ?: 0;
    }
}
