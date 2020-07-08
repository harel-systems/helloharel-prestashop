<?php

namespace HelloHarel\Manager;

use Configuration;
use WebserviceKey;

class ConfigManager extends AbstractManager
{
    public function install($app)
    {
        if(!Configuration::updateValue('PS_WEBSERVICE', 'true')) {
            $app->_errors[] = 'Could not activate web service';
            return false;
        }
        return true;
    }
    
    public function uninstall()
    {
        $apiKey = new WebserviceKey(Configuration::get('HH_API_KEY'));
        $apiKey->delete();
        Configuration::deleteByName('HH_API_KEY');
        Configuration::deleteByName('HH_INSTANCE_KEY');
        Configuration::deleteByName('HH_INSTANCE_URL');
    }
    
    public function getWebserviceKey()
    {
        if($id = Configuration::get('HH_API_KEY')) {
            $apiKey = new WebserviceKey($id);
        } else {
            $apiKey = new WebserviceKey();
            $apiKey->key = md5(random_bytes(10));
            $apiKey->description = $this->trans('Hello Harel API key', array(), 'Modules.HelloHarel.Admin');
            $apiKey->save();
            Configuration::updateValue('HH_API_KEY', $apiKey->id);
            
            $permissions = array(
                'addresses' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'carriers' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'cart_rules' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'carts' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'categories' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'combinations' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'configurations' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'contacts' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'content_management_system' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'countries' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'currencies' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'customer_messages' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'customer_threads' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'customers' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'customizations' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'deliveries' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'employees' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'groups' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'guests' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'helloharel' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'helloharel_references' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'image_types' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'images' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'languages' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'manufacturers' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'messages' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'order_carriers' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'order_details' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'order_histories' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'order_invoices' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'order_payments' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'order_slip' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'order_states' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'orders' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'price_ranges' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'product_customization_fields' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'product_feature_values' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'product_features' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'product_option_values' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'product_options' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'product_suppliers' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'products' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'search' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'shop_groups' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'shop_urls' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'shops' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'specific_price_rules' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'specific_prices' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'states' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'stock_availables' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'stock_movement_reasons' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'stock_movements' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'stocks' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'stores' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'suppliers' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'supply_order_details' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'supply_order_histories' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'supply_order_receipt_histories' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'supply_order_states' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'supply_orders' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'tags' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'tax_rule_groups' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'tax_rules' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'taxes' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'translated_configurations' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'warehouse_product_locations' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'warehouses' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'weight_ranges' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'zones' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
            );
            
            WebserviceKey::setPermissionForAccount($apiKey->id, $permissions);
        }
        return $apiKey;
    }
    
    public function configView()
    {
        $apiKey = $this->getWebserviceKey();
        $instanceUrl = Configuration::get('HH_INSTANCE_URL');
        
        if($instanceUrl) {
            return '<div class="alert alert-success"><strong>Congratulations!</strong> ' . $this->trans('Your module is now integrated with <a href="%url%" target="_blank">%url%</a>.', array('%url%' => $instanceUrl), 'Modules.HelloHarel.Admin') . '</div>';
        } else {
            return '
                <div class="alert alert-success"><strong>Congratulations!</strong> You are only one last step away from integrating Hello Harel into PrestaShop.</div>
                <p>To activate the integration, go to your Hello Harel instance:</p>
                <ol class="breadcrumb">
                    <li>Administration</li>
                    <li>External applications</li>
                    <li>PrestaShop</li>
                    <li>App settings</li>
                </ol>
                <p>and copy the following information:</p>
                <div class="form-horizontal">
                    <div class="form-group">
                        <label class="control-label col-md-4">PrestaShop URL</label>
                        <div class="controls col-md-8">
                            <div class="input-group">
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default copy-to-clipboard"><i class="icon-copy"></i></button>
                                </span>
                                <input type="text" readonly class="form-control" value="' . _PS_BASE_URL_ . __PS_BASE_URI__ . '" />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-md-4">API key</label>
                        <div class="controls col-md-8">
                            <div class="input-group">
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default copy-to-clipboard"><i class="icon-copy"></i></button>
                                </span>
                                <input type="text" readonly class="form-control" value="' . $apiKey->key . '" />
                            </div>
                        </div>
                    </div>
                    <a class="btn btn-success pull-right" href=""><i class="icon-check"></i> It\'s done!</a>
                </div>
                <script>
                $(document).ready(function() {
                    $(".copy-to-clipboard").click(function() {
                        let value = $(this).closest(".input-group").find("input").val();
                        const el = document.createElement("textarea");
                        el.value = value;
                        document.body.appendChild(el);
                        el.select();
                        document.execCommand("copy");
                        document.body.removeChild(el);
                    });
                });
                </script>
            ';
        }

        return $output;
    }
}
