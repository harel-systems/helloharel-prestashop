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

if(!defined('_PS_VERSION_')) {
    exit;
}

use HelloHarel\Entity\HelloHarelReference;
use HelloHarel\Manager;

include_once(_PS_MODULE_DIR_ . 'helloharel/classes/WebserviceSpecificManagementHelloharel.php');

class HelloHarel extends Module
{
    private $managers = array();
    
    public function __construct()
    {
        $this->name = 'helloharel';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Harel Systems SAS';
        $this->need_instance = 0;
        
        $this->bootstrap = true;
        
        parent::__construct();
        
        $this->displayName = $this->trans('Hello Harel integration', array(), 'Modules.Helloharel.Admin');
        
        $this->description = $this->trans('Hello Harel ERP integration', array(), 'Modules.Helloharel.Admin');
        
        $this->ps_versions_compliancy = array(
            'min' => '1.7.6.0',
            'max' => _PS_VERSION_,
        );
        
        $this->managers = array(
            'reference' => new Manager\ReferenceManager($this),
            'config' => new Manager\ConfigManager($this),
            'order' => new Manager\OrderManager($this),
            'product' => new Manager\ProductManager($this),
            'payment' => new Manager\PaymentManager($this),
            'customer' => new Manager\CustomerManager($this),
        );
    }
    
    public function isUsingNewTranslationSystem()
    {
        return true;
    }
    
    public function render($template, $vars = array())
    {
        $this->smarty->assign($vars);
        return $this->fetch($template);
    }
    
    public function install()
    {
        if(!parent::install() || !$this->registerHook(['addWebserviceResources'])) {
            return false;
        }
        foreach($this->managers as $manager) {
            if(!$this->registerHooks(array_keys($manager::HOOKS)) || !$manager->install($this)) {
                return false;
            }
        }
        return true;
    }
    
    public function uninstall()
    {
        if(!parent::uninstall() || !$this->registerHook(['addWebserviceResources'])) {
            return false;
        }
        foreach($this->managers as $manager) {
            if(!$this->registerHooks(array_keys($manager::HOOKS)) || !$manager->uninstall($this)) {
                return false;
            }
        }
        return true;
    }
    
    public function __call($name, $arguments)
    {
        if(!Validate::isHookName($name)) {
            return false;
        }
        $name = lcfirst(substr($name, 4));
        foreach($this->managers as $id => $manager) {
            if(isset($manager::HOOKS[$name])) {
                return $manager->{$manager::HOOKS[$name]}(...$arguments);
            }
        }
    }
    
    public function hookAddWebserviceResources()
    {
        return array(
            'helloharel' => array(
                'description' => 'Hello Harel configuration',
                'specific_management' => true,
            ),
            'helloharel_references' => array(
                'description' => 'Hello Harel references',
                'class' => 'HelloHarel\\Entity\\HelloHarelReference',
            ),
        );
    }
    
    public function getContent()
    {
        return $this->managers['config']->configView();
    }
}
