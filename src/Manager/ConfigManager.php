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

namespace HelloHarel\Manager;

use Configuration;
use HelloHarel\Entity\HelloHarelReference;
use WebserviceKey;
use Tools;

class ConfigManager extends AbstractManager
{
    const PERMISSIONS = [
        'addresses',
        'carriers',
        'cart_rules',
        'carts',
        'categories',
        'combinations',
        'configurations',
        'contacts',
        'content_management_system',
        'countries',
        'currencies',
        'customer_messages',
        'customer_threads',
        'customers',
        'customizations',
        'deliveries',
        'employees',
        'groups',
        'guests',
        'helloharel',
        'helloharel_references',
        'image_types',
        'images',
        'languages',
        'manufacturers',
        'messages',
        'order_carriers',
        'order_details',
        'order_histories',
        'order_invoices',
        'order_payments',
        'order_slip',
        'order_states',
        'orders',
        'price_ranges',
        'product_customization_fields',
        'product_feature_values',
        'product_features',
        'product_option_values',
        'product_options',
        'product_suppliers',
        'products',
        'search',
        'shop_groups',
        'shop_urls',
        'shops',
        'specific_price_rules',
        'specific_prices',
        'states',
        'stock_availables',
        'stock_movement_reasons',
        'stock_movements',
        'stocks',
        'stores',
        'suppliers',
        'supply_order_details',
        'supply_order_histories',
        'supply_order_receipt_histories',
        'supply_order_states',
        'supply_orders',
        'tags',
        'tax_rule_groups',
        'tax_rules',
        'taxes',
        'translated_configurations',
        'warehouse_product_locations',
        'warehouses',
        'weight_ranges',
        'zones',
    ];
    
    public function install()
    {
        if (!Configuration::updateValue('PS_WEBSERVICE', 'true')) {
            return 'Could not activate web service';
        }
        return true;
    }
    
    public function uninstall()
    {
        $this->deleteApiKey();
        Configuration::deleteByName('HH_INSTANCE_KEY');
        Configuration::deleteByName('HH_INSTANCE_URL');
        return true;
    }
    
    private function getApiKey()
    {
        if ($id = Configuration::get('HH_API_KEY')) {
            $apiKey = new WebserviceKey($id);
        } else {
            $apiKey = new WebserviceKey();
            $apiKey->key = md5(random_bytes(10));
            $apiKey->description = $this->trans('Hello Harel API key', array(), 'Modules.Helloharel.Admin');
            $apiKey->save();
            Configuration::updateValue('HH_API_KEY', $apiKey->id);
            
            $permissions = array();
            foreach (self::PERMISSIONS as $permission) {
                $permissions[$permission] = array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1);
            }
            
            WebserviceKey::setPermissionForAccount($apiKey->id, $permissions);
        }
        return $apiKey;
    }
    
    private function deleteApiKey()
    {
        if ($apiKey = $this->getApiKey()) {
            $apiKey->delete();
            Configuration::updateValue('HH_API_KEY', null);
        }
    }
    
    public function configView()
    {
        $apiKey = $this->getApiKey();
        $instanceUrl = Configuration::get('HH_INSTANCE_URL');
        $action = array(
            'type' => null,
        );
        if (Tools::getIsset('unlink_confirm') || Tools::getIsset('unlink')) {
            $action['type'] = 'unlink';
            $action['confirmed'] = Tools::getIsset('unlink_confirm');
            $action['drop_references'] = Tools::getIsset('drop_references');
            if ($action['confirmed']) {
                $this->deleteApiKey();
                $apiKey = $this->getApiKey();
                if ($action['drop_references']) {
                    $this->app->getManager('reference')->dropReferences();
                }
                
                Configuration::updateValue('HH_INSTANCE_URL', null);
                $instanceUrl = null;
            }
        }
        if (Tools::getIsset('extract_translations')) {
            $action['type'] = 'translations';
            $action['content'] = (new TranslationManager($this->app))->extractTranslations();
        }
        
        if ($instanceUrl) {
            return $this->app->render('module:helloharel/views/templates/admin/config/config.tpl', array(
                'action' => $action,
                'post' => json_encode(Tools::getAllValues()),
                'instanceUrl' => $instanceUrl,
                'references' => array(
                    'products' => HelloHarelReference::countReferences('product'),
                    'customers' => HelloHarelReference::countReferences('customer'),
                    'orders' => HelloHarelReference::countReferences('order'),
                ),
            ));
        }
        
        return $this->app->render('module:helloharel/views/templates/admin/config/wizard.tpl', array(
            'action' => $action,
            'prestashopUrl' => _PS_BASE_URL_ . __PS_BASE_URI__,
            'apiKey' => $apiKey->key,
        ));
    }
}
