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
use Db;
use Order;

class PaymentManager extends AbstractManager
{
    const HOOKS = array(
        'actionPaymentCCAdd' => 'paymentCreation',
    );

    /**
     * @param array $params = array(
     *     'paymentCC' => (object) OrderPayment
     * )
     */
    public function paymentCreation(array $params)
    {
        $instanceUrl = Configuration::get('HH_INSTANCE_URL');
        
        $payment = $params['paymentCC'];
        
        $orders = Db::getInstance()->executeS('SELECT id_order FROM ' . _DB_PREFIX_ . 'orders WHERE reference = "' . (string)$payment->order_reference . '"');
        
        if(!$orders || count($orders) !== 1) {
            error_log('No single order found for payment with order reference ' . (string)$payment->order_reference);
            // No payment
            return;
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
}
