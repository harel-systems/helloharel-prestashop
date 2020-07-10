<?php

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
