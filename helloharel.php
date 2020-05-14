<?php

if(!defined('_PS_VERSION_')) {
    exit;
}

use HelloHarel\Domain\Reviewer\Command\UpdateIsAllowedToReviewCommand;
use HelloHarel\Domain\Reviewer\Exception\CannotCreateReviewerException;
use HelloHarel\Domain\Reviewer\Exception\CannotToggleAllowedToReviewStatusException;
use HelloHarel\Domain\Reviewer\Exception\ReviewerException;
use HelloHarel\Domain\Reviewer\Query\GetReviewerSettingsForForm;
use HelloHarel\Domain\Reviewer\QueryResult\ReviewerSettingsForForm;
use Doctrine\DBAL\Query\QueryBuilder;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Domain\Customer\Exception\CustomerException;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ToggleColumn;
use PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinitionInterface;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Core\Search\Filters\CustomerFilters;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\YesAndNoChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

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

    /**
     * This function is required in order to make module compatible with new translation system.
     *
     * @return bool
     */
    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    /**
     * Install module and register hooks to allow grid modification.
     *
     * @see https://devdocs.prestashop.com/1.7/modules/concepts/hooks/use-hooks-on-modern-pages/
     *
     * @return bool
     */
    public function install()
    {
        return parent::install() &&
            Configuration::updateValue('PS_WEBSERVICE', 'true') &&
            $this->registerHook('addWebserviceResources') &&
            $this->registerHook('actionOrderStatusPostUpdate')
        ;
    }
    
    public function hookAddWebserviceResources()
    {
        return array(
            'helloharel' => array(
                'description' => 'Hello Harel configuration',
                'specific_management' => true,
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
                'categories' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'customers' => array('POST' => 1),
                'helloharel' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'languages' => array('GET' => 1),
                'orders' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
                'products' => array('GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1),
            );
            
            WebserviceKey::setPermissionForAccount($apiKey->id, $permissions);
        }
        return $apiKey;
    }
    
    public function getContent()
    {
        $output = null;
        
        $apiKey = $this->getWebserviceKey();
        $instanceUrl = Tools::getValue('HH_INSTANCE_URL', Configuration::get('HH_INSTANCE_URL'));
        $instanceKey = Tools::getValue('HH_INSTANCE_URL', Configuration::get('HH_INSTANCE_KEY'));
        
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


    public function uninstall()
    {
        return $this->deleteWebserviceKey() &&
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

    /**
     * @param array $params = array(
     *     'newOrderStatus' => (object) OrderState,
     *     'id_order' => (int) Order ID
     * )
     */
    public function hookActionOrderEdited(array $params)
    {
        $order = $params['id_order'];
        
    }
}
