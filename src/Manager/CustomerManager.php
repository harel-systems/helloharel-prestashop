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

class CustomerManager extends AbstractManager
{
    const HOOKS = array(
        'actionCustomerAccountUpdate' => 'accountUpdate',
    );

    /**
     * @param array $params = array(
     *      'customer' => (object) Customer object
     * )
     */
    public function accountUpdate(array $params)
    {
        $instanceUrl = Configuration::get('HH_INSTANCE_URL');
        
        $customer = $params['customer'];
        
        $reference = HelloHarelReference::getHelloHarelId('customer', $customer->id);
        
        if ($reference !== null) {
            $response = $this->getHttpClient()->request('PATCH', $instanceUrl . '/api/v1/contact_references', array(
                'json' => array(
                    'id' => $reference->hh_id,
                    'externalReference' => $customer->id,
                    'firstName' => $customer->firstname,
                    'lastName' => $customer->lastname,
                    'email' => $customer->email,
                ),
            ));
            
            if ($response->getStatusCode() !== 200) {
                error_log('Customer could not be updated on Hello Harel. Error code ' . $response->getStatusCode());
            }
        }
    }
}
