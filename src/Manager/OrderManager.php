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

use Address;
use Configuration;
use Country;
use CustomerThread;
use Db;
use Language;
use Order;
use OrderState;
use HelloHarel\Entity\HelloHarelReference;

class OrderManager extends AbstractManager
{
    const HOOKS = array(
        'actionValidateOrder' => 'orderValidation',
        'displayAdminOrder' => 'adminOrderView',
        'displayCustomerAccount' => 'customerAccountView',
        'displayOrderDetail' => 'customerOrderView',
    );
    
    public function install()
    {
        if(!$this->disableModule('ps_customeraccountlinks')) {
            $this->app->_errors[] = 'Could not disable ps_customeraccountlinks module';
            return false;
        }
        
        if(!Configuration::updateValue('PS_ORDER_RETURN', '0')) {
            $this->app->_errors[] = 'Could not deactivate returns';
            return false;
        }
        
        // Remove problematic properties of order states (invoice, delivery)
        if(Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'order_state SET logable = false, delivery = false, shipped = false, invoice = false, pdf_invoice = false, pdf_delivery = false')) {
            $this->app->_errors[] = 'Could not fix order states';
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
            $paymentReceived->name[$language['id_lang']] = $this->trans('Payment received', array(), 'Modules.Helloharel.Admin');
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
            $orderValidated->name[$language['id_lang']] = $this->trans('Order validated', array(), 'Modules.Helloharel.Admin');
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
            $shipped->name[$language['id_lang']] = $this->trans('Shipped', array(), 'Modules.Helloharel.Admin');
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
            $delivered->name[$language['id_lang']] = $this->trans('Delivered', array(), 'Modules.Helloharel.Admin');
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
            $cancelled->name[$language['id_lang']] = $this->trans('Cancelled', array(), 'Modules.Helloharel.Admin');
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
        if(!Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'order_state WHERE module_name = \'helloharel\'')) {
            $this->app->_errors[] = 'Could not delete custom order states';
            return false;
        }
        return true;
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
    public function orderValidation(array $params)
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
            \PS_ROUND_UP => 'up',
            \PS_ROUND_DOWN => 'down',
            \PS_ROUND_HALF_UP => 'half_up',
            \PS_ROUND_HALF_DOWN => 'half_down',
            \PS_ROUND_HALF_EVEN => 'half_even',
            \PS_ROUND_HALF_ODD => 'half_odd',
        )[Configuration::get('PS_PRICE_ROUND_MODE')];
        
        $vouchers = [];
        if($order->total_discounts_tax_incl) {
            $vouchers[] = array(
                'description' => $this->trans('PrestaShop voucher', array(), 'Modules.Helloharel.Admin'),
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
            $_order = $response->toArray();
            if($_order['code']) {
                $order->reference = $_order['code'];
            }
            $customerReference = HelloHarelReference::getHelloHarelId('customer', $customer->id);
            if($customerReference === null) {
                $customerReference = new HelloHarelReference();
                $customerReference->object_type = 'customer';
                $customerReference->ps_id = $customer->id;
                $customerReference->hh_id = $_order['contact']['id'];
                $customerReference->save();
            }
            if($_order['access_id']) {
                $orderReference = new HelloHarelReference();
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
     *      'id_order' => (int) Order ID
     * )
     */
    public function adminOrderView(array $params)
    {
        $instanceUrl = Configuration::get('HH_INSTANCE_URL');
        $order = new Order($params['id_order']);
        
        $reference = HelloHarelReference::getHelloHarelId('order', $order->id);
        
        if($instanceUrl && $reference !== null) {
            return "
            <div class=\"alert alert-info\">
                <a href=\"$instanceUrl/sales/orders/by_reference/ordering.prestashop/{$order->id}\" class=\"btn btn-primary pull-right\"><i class=\"material-icons\">edit</i> " . $this->trans('View on Hello Harel', array(), 'Modules.Helloharel.Admin') . "</a>
                " . $this->trans('This order is managed by Hello Harel.', array(), 'Modules.Helloharel.Admin') . "
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
    public function customerOrderView(array $params)
    {
        $instanceUrl = Configuration::get('HH_INSTANCE_URL');
        
        $reference = HelloHarelReference::getHelloHarelId('order', $params['order']->id);
        
        $comments = CustomerThread::getCustomerMessagesOrder($params['order']->id_customer, $params['order']->id);
        
        if($instanceUrl && $reference !== null) {
            return "
            <a class=\"box\" style=\"display: block; text-align: center;\" href=\"$instanceUrl/public/invoice/$reference/download\">
                <i class=\"material-icons\">cloud_download</i> " . $this->trans('Download your invoice', array(), 'Modules.Helloharel.Admin') . "
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
    public function customerAccountView(array $params)
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
}
