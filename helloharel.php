<?php

if(!defined('_PS_VERSION_')) {
    exit;
}

use HelloHarel\Entity\HelloHarelReference;
use Symfony\Component\HttpClient\HttpClient;

include_once(_PS_MODULE_DIR_ . 'helloharel/classes/WebserviceSpecificManagementHelloharel.php');

class HelloHarel extends Module
{
    public function __construct()
    {
        $this->name = 'helloharel';
        $this->version = '0.0.1';
        $this->author = 'Harel Systems SAS';
        $this->need_instance = 0;
        
        $this->bootstrap = true;
        
        parent::__construct();
        
        $this->displayName = $this->getTranslator()->trans('Hello Harel integration', array(), 'Modules.HelloHarel.Admin');
        
        $this->description = $this->getTranslator()->trans('Hello Harel ERP integration', array(), 'Modules.HelloHarel.Admin');
        
        $this->ps_versions_compliancy = array(
            'min' => '1.7.6.0',
            'max' => _PS_VERSION_,
        );
    }
    
    public function isUsingNewTranslationSystem()
    {
        return true;
    }
    
    public function install()
    {
        return parent::install() &&
            $this->installSql() &&
            $this->registerHook([
                'addWebserviceResources',
                'actionValidateOrder',
                'actionPaymentCCAdd',
                'actionPaymentConfirmation',
                'actionSetInvoice',
                'displayAdminProductsMainStepLeftColumnBottom',
                'displayOrderDetail',
                'displayAdminOrder',
                'displayCustomerAccount',
                'displayAdminAfterHeader',
                'actionCustomerAccountUpdate',
            ])
        ;
    }
    
    private function disableModule($name)
    {
        $query = new \DbQuery();
        $query->select('id_module');
        $query->from('module');
        $query->where('name = "' . $name . '"');
        
        $id = \Db::getInstance()->getValue($query);
        
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
    
    protected function installSql()
    {
        // Disable conflicting modules
        if(!$this->disableModule('ps_customeraccountlinks')) {
            $this->_errors[] = 'Could not disable ps_customeraccountlinks module';
            return false;
            
        }
        
        // Activate web service
        if(!Configuration::updateValue('PS_WEBSERVICE', 'true')) {
            $this->_errors[] = 'Could not activate web service';
            return false;
        }
        if(!Configuration::updateValue('PS_ORDER_RETURN', '0')) {
            $this->_errors[] = 'Could not deactivate returns';
            return false;
        }
        
        $query = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'helloharel_references` (
            `id_reference` INT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `object_type` VARCHAR(50) NOT NULL,
            `ps_id` int(20) UNSIGNED NOT NULL,
            `hh_id` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id_reference`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;';

        if(!Db::getInstance()->execute($query)) {
            $this->_errors[] = 'Could not create reference table';
            return false;
        }
        if(!Db::getInstance()->execute('ALTER TABLE ' . _DB_PREFIX_ . 'orders CHANGE `reference` `reference` VARCHAR(255) DEFAULT NULL')) {
            $this->_errors[] = 'Could not fix order reference size';
            return false;
        }
        if(!Db::getInstance()->execute('ALTER TABLE ' . _DB_PREFIX_ . 'order_payment CHANGE `order_reference` `order_reference` VARCHAR(255) DEFAULT NULL')) {
            $this->_errors[] = 'Could not fix order reference size';
            return false;
        }
        // Remove problematic properties of order states (invoice, delivery)
        if(!Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'order_state SET logable = false, delivery = false, shipped = false, invoice = false, pdf_invoice = false, pdf_delivery = false')) {
            $this->_errors[] = 'Could not fix order states';
            return false;
        }
        
        // Create our order events
        $orderStates = array();
        $paymentReceived = new OrderState();
        $paymentReceived->color = '#32cd32';
        $paymentReceived->module_name = 'helloharel';
        $paymentReceived->template = 'payment';
        $paymentReceived->logable = 1;
        $paymentReceived->paid = 1;
        $paymentReceived->send_email = 1;
        $paymentReceived->unremovable = 1;
        $paymentReceived->name = array();
        $languages = Language::getLanguages(false);
        foreach($languages as $language) {
            $paymentReceived->name[$language['id_lang']] = $this->getTranslator()->trans('Payment received', array(), 'Modules.HelloHarel.Admin');
        }
        $paymentReceived->add();
        $orderStates['payment_received'] = $paymentReceived->id;
        
        $orderValidated = new OrderState();
        $orderValidated->color = '#ff8c00';
        $orderValidated->module_name = 'helloharel';
        $orderValidated->template = 'preparation';
        $orderValidated->logable = 1;
        $orderValidated->send_email = 1;
        $orderValidated->unremovable = 1;
        $orderValidated->name = array();
        $languages = Language::getLanguages(false);
        foreach($languages as $language) {
            $orderValidated->name[$language['id_lang']] = $this->getTranslator()->trans('Order validated', array(), 'Modules.HelloHarel.Admin');
        }
        $orderValidated->add();
        $orderStates['order_validated'] = $orderValidated->id;
        
        $shipped = new OrderState();
        $shipped->color = '#8a2be2';
        $shipped->module_name = 'helloharel';
        $shipped->template = 'shipped';
        $shipped->logable = 1;
        $shipped->send_email = 1;
        $shipped->shipped = 1;
        $shipped->unremovable = 1;
        $shipped->name = array();
        $languages = Language::getLanguages(false);
        foreach($languages as $language) {
            $shipped->name[$language['id_lang']] = $this->getTranslator()->trans('Shipped', array(), 'Modules.HelloHarel.Admin');
        }
        $shipped->add();
        $orderStates['shipped'] = $shipped->id;
        
        $delivered = new OrderState();
        $delivered->color = '#108510';
        $delivered->module_name = 'helloharel';
        $delivered->logable = 1;
        $delivered->shipped = 1;
        $delivered->unremovable = 1;
        $delivered->name = array();
        $languages = Language::getLanguages(false);
        foreach($languages as $language) {
            $delivered->name[$language['id_lang']] = $this->getTranslator()->trans('Delivered', array(), 'Modules.HelloHarel.Admin');
        }
        $delivered->add();
        $orderStates['delivered'] = $delivered->id;
        
        $cancelled = new OrderState();
        $cancelled->color = '#dc143c';
        $cancelled->module_name = 'helloharel';
        $cancelled->template = 'ordered_canceled';
        $cancelled->send_email = 1;
        $cancelled->unremovable = 1;
        $cancelled->name = array();
        $languages = Language::getLanguages(false);
        foreach($languages as $language) {
            $cancelled->name[$language['id_lang']] = $this->getTranslator()->trans('Cancelled', array(), 'Modules.HelloHarel.Admin');
        }
        $cancelled->add();
        $orderStates['cancelled'] = $cancelled->id;
        
        if(!Configuration::updateValue('HH_ORDER_STATES', json_encode($orderStates))) {
            $this->_errors[] = 'Could not set order states configuration';
            return false;
        }
        
        return true;
    }
    
    public function uninstall()
    {
        return $this->deleteWebserviceKey() &&
            $this->uninstallSql() &&
            parent::uninstall();
    }
    
    public function deleteWebserviceKey()
    {
        $apiKey = new WebserviceKey(Configuration::get('HH_API_KEY'));
        $apiKey->delete();
        Configuration::deleteByName('HH_API_KEY');
        Configuration::deleteByName('HH_INSTANCE_KEY');
        Configuration::deleteByName('HH_INSTANCE_URL');
        
        return true;
    }
    
    protected function uninstallSql()
    {
        /* Keep references if the module is reactivated
        if(!Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'helloharel_references`')) {
            $this->_errors[] = 'Could not delete reference table';
            return false;
        }
        */
        
        if(!Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'order_state WHERE module_name = \'helloharel\'')) {
            $this->_errors[] = 'Could not delete custom order states';
            return false;
        }
        
        return true;
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
    
    public function getWebserviceKey()
    {
        if($id = Configuration::get('HH_API_KEY')) {
            $apiKey = new WebserviceKey($id);
        } else {
            $apiKey = new WebserviceKey();
            $apiKey->key = md5(random_bytes(10));
            $apiKey->description = $this->getTranslator()->trans('Hello Harel API key', array(), 'Modules.HelloHarel.Admin');
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
    
    private function getHttpClient()
    {
        return HttpClient::create(array(
            'headers' => array(
                'X-AUTH-TOKEN' => Configuration::get('HH_INSTANCE_KEY'),
            ),
        ));
    }
    
    public function getContent()
    {
        $output = null;
        
        $apiKey = $this->getWebserviceKey();
        $instanceUrl = Configuration::get('HH_INSTANCE_URL');
        
        if($instanceUrl) {
            $output .= '<div class="alert alert-success"><strong>Congratulations!</strong> ' . $this->getTranslator()->trans('Your module is now integrated with <a href="%url%" target="_blank">%url%</a>.', array('%url%' => $instanceUrl), 'Modules.HelloHarel.Admin') . '</div>';
        } else {
            $output .= '
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

    /**
     * @param array $params = array(
     *     'cart' => (object) Cart,
     *     'order' => (object) Order,
     *     'customer' => (object) Customer,
     *     'currency' => (object) Currency,
     *     'orderStatus' => (object) OrderState
     * )
     */
    public function hookActionValidateOrder(array $params)
    {
        $instanceUrl = Configuration::get('HH_INSTANCE_URL');
        
        $order = $params['order'];
        $customer = $params['customer'];
        $deliveryAddress = new Address($order->id_address_delivery);
        $invoicingAddress = new Address($order->id_address_invoice);
        
        $managedProducts = array_map('current', Db::getInstance()->executeS('SELECT ps_id FROM ' . _DB_PREFIX_ . 'helloharel_references WHERE object_type = "product"'));
        
        $rows = Db::getInstance()->executeS('SELECT product_id, product_quantity, product_name, unit_price_tax_excl FROM ' . _DB_PREFIX_ . 'order_detail WHERE id_order = ' . (int)$order->id);

        $items = [];
        foreach($rows as $row) {
            if(!in_array($row['product_id'], $managedProducts)) {
                error_log($row['product_id'] . ' is not managed by hello harel. Managed products are: ' . implode(', ', $managedProducts));
                continue;
            }
            $items[] = array(
                'externalReference' => $row['product_id'],
                'orderedQuantity' => $row['product_quantity'],
                'unitPrice' => $row['unit_price_tax_excl'],
                'label' => $row['product_name'],
            );
        }
        
        if(count($rows) !== count($items)) {
            error_log($params['order']->id . ' was not created on Hello Harel because some products were not mapped.');
            return;
        }
        
        $expectedDeliveryDate = date('Y-m-d 00:00:00');
        if(isset($order->ddw_order_date)) {
            $expectedDeliveryDate = $order->ddw_order_date;
        }
        
        $calculationMode = array(
            Order::ROUND_ITEM => 'unit',
            Order::ROUND_LINE => 'line',
            Order::ROUND_TOTAL => 'total',
        )[Configuration::get('PS_ROUND_TYPE')];
        
        $roundingMode = array(
            PS_ROUND_UP => 'up',
            PS_ROUND_DOWN => 'down',
            PS_ROUND_HALF_UP => 'half_up',
            PS_ROUND_HALF_DOWN => 'half_down',
            PS_ROUND_HALF_EVEN => 'half_even',
            PS_ROUND_HALF_ODD => 'half_odd',
        )[Configuration::get('PS_PRICE_ROUND_MODE')];
        
        $vouchers = [];
        if($order->total_discounts_tax_incl) {
            $vouchers[] = array(
                'description' => $this->getTranslator()->trans('PrestaShop voucher', array(), 'Modules.HelloHarel.Order'),
                'taxedAmount' => $order->total_discounts_tax_incl,
            );
        }
        
        $response = $this->getHttpClient()->request('POST', $instanceUrl . '/api/v1/orders', array(
            'json' => array(
                'externalId' => $order->id,
                'calculationMode' => $calculationMode,
                'roundingMode' => $roundingMode,
                'contact' => array(
                    'externalReference' => $customer->id,
                    'firstName' => $customer->firstname,
                    'lastName' => $customer->lastname,
                    'email' => $customer->email,
                ),
                'customer' => array(
                    'individual' => (string)$customer->company === '' ? 0 : 1,
                    'name' => $customer->company,
                ),
                'deliveryAddress' => array(
                    'recipient' => $deliveryAddress->company ?: trim($deliveryAddress->firstname . ' ' . $deliveryAddress->lastname),
                    'street' => $deliveryAddress->address1,
                    'details' => $deliveryAddress->address2,
                    'zipCode' => $deliveryAddress->postcode,
                    'city' => $deliveryAddress->city,
                    'country' => (new Country($deliveryAddress->id_country))->iso_code,
                ),
                'invoicingAddress' => array(
                    'recipient' => $invoicingAddress->company ?: trim($invoicingAddress->firstname . ' ' . $invoicingAddress->lastname),
                    'street' => $invoicingAddress->address1,
                    'details' => $invoicingAddress->address2,
                    'zipCode' => $invoicingAddress->postcode,
                    'city' => $invoicingAddress->city,
                    'country' => (new Country($invoicingAddress->id_country))->iso_code,
                ),
                'shippingMethod' => array(
                    'externalReference' => $order->id_carrier,
                    'price' => $order->total_shipping_tax_excl,
                ),
                'expectedDeliveryDate' => $expectedDeliveryDate,
                'comment' => html_entity_decode($params['order']->getFirstMessage(), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'items' => $items,
                'vouchers' => $vouchers,
            ),
        ));
        
        if($response->getStatusCode() === 200) {
            error_log('success');
            $_order = $response->toArray();
            if($_order['code']) {
                $order->reference = $_order['code'];
            }
            $customerReference = HelloHarelReference::getHelloHarelId('customer', $customer->id);
            if($customerReference === null) {
                $customerReference = new HelloHarel\Entity\HelloHarelReference();
                $customerReference->object_type = 'customer';
                $customerReference->ps_id = $customer->id;
                $customerReference->hh_id = $_order['contact']['id'];
                $customerReference->save();
            }
            if($_order['access_id']) {
                $orderReference = new HelloHarel\Entity\HelloHarelReference();
                $orderReference->object_type = 'order';
                $orderReference->ps_id = $order->id;
                $orderReference->hh_id = $_order['access_id'];
                $orderReference->save();
            }
            $order->save();
        } else {
            error_log('Order creation request failed with error ' . $response->getStatusCode());
        }
    }

    /**
     * @param array $params = array(
     *     'paymentCC' => (object) OrderPayment
     * )
     */
    public function hookActionPaymentCCAdd(array $params)
    {
        $instanceUrl = Configuration::get('HH_INSTANCE_URL');
        
        $payment = $params['paymentCC'];
        
        $orders = Db::getInstance()->executeS('SELECT id_order FROM ' . _DB_PREFIX_ . 'orders WHERE reference = "' . (string)$payment->order_reference . '"');
        
        if(!$orders || count($orders) !== 1) {
            error_log('No single order found for payment with order reference ' . (string)$payment->order_reference);
            // No payment
            return;
        } else {
            error_log(json_encode($orders));
        }
        
        $order = new Order($orders[0]['id_order']);
        
        $response = $this->getHttpClient()->request('POST', $instanceUrl . '/api/v1/payments', array(
            'json' => array(
                'type' => 'external',
                'customer' => array(
                    'externalReference' => $order->id_customer,
                ),
                'date' => substr($payment->date_add, 0, 10),
                'amount' => $payment->amount,
                'reference' => $payment->transaction_id,
                'comment' => $payment->payment_method,
                'operations' => [
                    array(
                        'orderExternalReference' => $order->id,
                        'amount' => $payment->amount,
                    ),
                ],
            ),
        ));
        
        if($response->getStatusCode() === 200) {
            $_payment = $response->toArray();
        } else {
            error_log('Payment could not be created on Hello Harel. Error code ' . $response->getStatusCode());
        }
    }

    /**
     * @param array $params = array(
     *     'id_order' => (int) Order ID
     * )
     */
    public function hookActionPaymentConfirmation(array $params)
    {
        
    }

    /**
     * @param array $params = array(
     *     'Order' => (object) Order,
     *     'OrderInvoice' => (object) OrderInvoice,
     *     'use_existing_payment' => (bool)
     * )
     */
    public function hookActionSetInvoice(array $params)
    {
        
    }

    /**
     * @param array $params = array(
     *
     * )
     */
    public function hookDisplayAdminProductsMainStepLeftColumnBottom(array $params)
    {
        $instanceUrl = Configuration::get('HH_INSTANCE_URL');
        $product = new Product($params['id_product']);
        
        $reference = HelloHarelReference::getHelloHarelId('product', $product->id);
        
        if($instanceUrl && $reference !== null) {
            return "
            <div class=\"alert alert-info\">
                <a href=\"$instanceUrl/products/products/by_reference/{$params['id_product']}#config\" class=\"btn btn-primary float-right\"><i class=\"material-icons\">edit</i> " . $this->getTranslator()->trans('View on Hello Harel', array(), 'Modules.HelloHarel.Admin') . "</a>
                " . $this->getTranslator()->trans('This product is managed by Hello Harel.', array(), 'Modules.HelloHarel.Admin') . "
            </div>
            <style>
                .tabs.js-tabs, #step2, #step3, #step4, #step5, #step6,
                #product-images-container, .summary-description-container, #features, #manufacturer, #related-product,
                .right-column, .product-footer, #hooks
                {
                    display: none!important;
                }
                .form_step1_type_product
                {
                    visibility: hidden;
                }
                .tab-content {
                    background: none;
                }
                .left-column {
                    flex: 0 0 100%;
                    max-width: 100%;
                }
                .alert > a.float-right {
                    margin-right: 4px;
                    padding: 8px 12px;
                    position: relative;
                    top: -9px;
                }
            });
            </style>
            <script>
            $('input').prop('disabled', true);
            </script>
            ";
        }
    }

    /**
     * @param array $params = array(
     *      'id_order' => (int) Order ID
     * )
     */
    public function hookDisplayAdminOrder(array $params)
    {
        $instanceUrl = Configuration::get('HH_INSTANCE_URL');
        $order = new Order($params['id_order']);
        
        $reference = HelloHarelReference::getHelloHarelId('order', $order->id);
        
        if($instanceUrl && $reference !== null) {
            return "
            <div class=\"alert alert-info\">
                <a href=\"$instanceUrl/sales/orders/by_reference/ordering.prestashop/{$order->id}\" class=\"btn btn-primary pull-right\"><i class=\"material-icons\">edit</i> " . $this->getTranslator()->trans('View on Hello Harel', array(), 'Modules.HelloHarel.Admin') . "</a>
                " . $this->getTranslator()->trans('This order is managed by Hello Harel.', array(), 'Modules.HelloHarel.Admin') . "
            </div>
            <div class=\"text-center\">
                <button class=\"btn btn-default\" id=\"hh_takeover\">Take over!</button>
            </div>
            <style id=\"hh_style\">
                .panel, .alert-warning
                {
                    display: none!important;
                }
                
                .alert > a.pull-right {
                    margin-right: 4px;
                    padding: 8px 12px;
                    position: relative;
                    top: -9px;
                }
                
                .alert > a .material-icons {
                    font-size: 1.4em;
                    margin-top: -.083em;
                    display: inline-block;
                    vertical-align: middle;
                }
            </style>
            <script>
            $('input').prop('disabled', true);
            $('#hh_takeover').click(function() {
                $('#hh_style').remove();
                $('input').prop('disabled', false);
                $(this).remove();
            });
            </script>
            ";
        }
    }

    /**
     * @param array $params = array(
     *      'order' => (object) Order object
     * )
     */
    public function hookDisplayOrderDetail(array $params)
    {
        $instanceUrl = Configuration::get('HH_INSTANCE_URL');
        
        $reference = HelloHarelReference::getHelloHarelId('order', $params['order']->id);
        
        $comments = CustomerThread::getCustomerMessagesOrder($params['order']->id_customer, $params['order']->id);
        
        if($instanceUrl && $reference !== null) {
            return "
            <a class=\"box\" style=\"display: block; text-align: center;\" href=\"$instanceUrl/public/invoice/$reference/download\">
                <i class=\"material-icons\">cloud_download</i> " . $this->getTranslator()->trans('Download your invoice', array(), 'Modules.HelloHarel.Admin') . "
            </a>
            <style>
            #order-infos .box:nth-child(2) a {
                display: none;
            }
            </style>
            ";
        }
    }

    /**
     * @param array $params = array(
     *      'order' => (object) Order object
     * )
     */
    public function hookDisplayCustomerAccount(array $params)
    {
        $instanceUrl = Configuration::get('HH_INSTANCE_URL');
        
        if($instanceUrl) {
            return "
            <style>
            #order-slips-link {
                display: none!important;
            }
            </style>
            ";
        }
    }

    /**
     * @param array $params = array(
     *
     * )
     */
    public function hookDisplayAdminAfterHeader(array $params)
    {
        $instanceUrl = Configuration::get('HH_INSTANCE_URL');
        if($instanceUrl) {
            switch(Context::getContext()->controller->php_self) {
                case 'AdminProducts':
                    return "<script>
                    $('#page-header-desc-configuration-add').replaceWith('<a href=\"#\" class=\"btn btn-primary pointer disabled\">" . $this->getTranslator()->trans('Products are managed by Hello Harel', array(), 'Modules.HelloHarel.Admin') . "</a>');
                    </script>";
            }
        }
    }

    /**
     * @param array $params = array(
     *      'customer' => (object) Customer object
     * )
     */
    public function hookActionCustomerAccountUpdate(array $params)
    {
        $instanceUrl = Configuration::get('HH_INSTANCE_URL');
        
        $customer = $params['customer'];
        
        $reference = HelloHarelReference::getHelloHarelId('customer', $customer->id);
        
        if($reference !== null) {
            $response = $this->getHttpClient()->request('PATCH', $instanceUrl . '/api/v1/contact_references', array(
                'json' => array(
                    'id' => $reference->hh_id,
                    'externalReference' => $customer->id,
                    'firstName' => $customer->firstname,
                    'lastName' => $customer->lastname,
                    'email' => $customer->email,
                ),
            ));
            
            if($response->getStatusCode() === 200) {
                $_payment = $response->toArray();
            } else {
                error_log('Customer could not be updated on Hello Harel. Error code ' . $response->getStatusCode());
            }
        }
    }
}
