<?php

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
