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

use Configuration;
use Symfony\Component\HttpClient\HttpClient;

abstract class AbstractManager
{
    const HOOKS = array();
    
    protected $app;
    
    public function __construct($app)
    {
        $this->app = $app;
    }
    
    public function install()
    {
        return true;
    }
    
    public function uninstall()
    {
        return true;
    }
    
    protected function trans($message, $params, $domain)
    {
        return $this->app->getTranslator()->trans($message, $params, $domain);
    }
    
    protected function getHttpClient()
    {
        return HttpClient::create(array(
            'headers' => array(
                'X-AUTH-TOKEN' => Configuration::get('HH_INSTANCE_KEY'),
            ),
        ));
    }
    
    protected function disableModule($name)
    {
        $query = new \DbQuery();
        $query->select('id_module');
        $query->from('module');
        $query->where('name = "' . $name . '"');
        
        $id = Db::getInstance()->getValue($query);
        
        if(!$id) {
            return true;
        }
        
        // FIXME It works, but the uninstalled module shows up weirdly in the module manager
        return  Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'hook_module WHERE id_module=' . (int)$id)
            &&
                Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'module_shop WHERE id_module=' . (int)$id)
            &&
                Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'module_group WHERE id_module=' . (int)$id)
            &&
                Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'module SET active = 0 WHERE name="' . $name . '"')
        ;
    }
}
