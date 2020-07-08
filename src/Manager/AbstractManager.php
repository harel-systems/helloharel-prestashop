<?php

namespace HelloHarel\Manager;

use Configuration;
use Symfony\Component\HttpClient\HttpClient;

abstract class AbstractManager
{
    const HOOKS = array();
    
    private $app;
    
    public function __construct($app)
    {
        $this->app = $app;
    }
    
    public function install($app)
    {
        return true;
    }
    
    public function uninstall($app)
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
