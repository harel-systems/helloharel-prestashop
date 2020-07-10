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

class WebserviceSpecificManagementHelloharel implements WebserviceSpecificManagementInterface
{
    protected $objOutput;
    protected $output;
    protected $wsObject;
    
    public function setObjectOutput(WebserviceOutputBuilderCore $obj)
    {
        $this->objOutput = $obj;

        return $this;
    }

    public function setWsObject(WebserviceRequestCore $obj)
    {
        $this->wsObject = $obj;

        return $this;
    }

    public function getWsObject()
    {
        return $this->wsObject;
    }

    public function getObjectOutput()
    {
        return $this->objOutput;
    }

    public function setUrlSegment($segments)
    {
        $this->urlSegment = $segments;

        return $this;
    }

    public function getUrlSegment()
    {
        return $this->urlSegment;
    }
    
    public function manage()
    {
        switch($this->wsObject->method) {
            case 'POST':
                if(!isset($_POST['url']) || !isset($_POST['key'])) {
                    throw new WebserviceException('You have send values for the \'url\' and \'key\' fields', [100, 400]);
                }
                Configuration::updateValue('HH_INSTANCE_URL', $_POST['url']);
                Configuration::updateValue('HH_INSTANCE_KEY', $_POST['key']);
            case 'GET':
                $this->objOutput->setHeaderParams('Content-Type', 'application/json');
                
                $unmanagedProducts = array_map('current', Db::getInstance()->executeS('SELECT p.id_product FROM ' . _DB_PREFIX_ . 'product p WHERE p.visibility != "" AND p.id_product NOT IN (SELECT r.ps_id FROM ' . _DB_PREFIX_ . 'helloharel_references r WHERE r.object_type = "product")'));
                
                $this->output .= json_encode(array(
                    'admin_dir' => null,
                    'order_states' => json_decode(\Configuration::get('HH_ORDER_STATES'), true),
                    'unmanaged_products' => $unmanagedProducts,
                ));
                break;
            default:
                throw new WebserviceException('Method unsupported', [100, 400]);
        }
        
    }

    /**
     * This must return a string with specific values as WebserviceRequest expects.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->output;
    }
}
