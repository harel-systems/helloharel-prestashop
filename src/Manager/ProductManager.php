<?php

namespace HelloHarel\Manager;

use Configuration;
use Context;
use HelloHarel\Entity\HelloHarelReference;
use Product;

class ProductManager extends AbstractManager
{
    const HOOKS = array(
        'displayAdminAfterHeader' => 'adminListView',
        'displayAdminProductsMainStepLeftColumnBottom' => 'adminProductView',
    );

    /**
     * @param array $params = array(
     *
     * )
     */
    public function adminListView(array $params)
    {
        $instanceUrl = Configuration::get('HH_INSTANCE_URL');
        if($instanceUrl) {
            switch(Context::getContext()->controller->php_self) {
                case 'AdminProducts':
                    return "<script>
                    $('#page-header-desc-configuration-add').replaceWith('<a href=\"#\" class=\"btn btn-primary pointer disabled\">" . $this->trans('Products are managed by Hello Harel', array(), 'Modules.HelloHarel.Admin') . "</a>');
                    </script>";
            }
        }
    }

    /**
     * @param array $params = array(
     *
     * )
     */
    public function adminProductView(array $params)
    {
        $instanceUrl = Configuration::get('HH_INSTANCE_URL');
        $product = new Product($params['id_product']);
        $reference = HelloHarelReference::getHelloHarelId('product', $product->id);
        
        if($instanceUrl && $reference !== null) {
            return "
            <div class=\"alert alert-info\">
                <a href=\"$instanceUrl/products/products/by_reference/{$params['id_product']}#config\" class=\"btn btn-primary float-right\"><i class=\"material-icons\">edit</i> " . $this->trans('View on Hello Harel', array(), 'Modules.HelloHarel.Admin') . "</a>
                " . $this->trans('This product is managed by Hello Harel.', array(), 'Modules.HelloHarel.Admin') . "
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
}
