<?php

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
