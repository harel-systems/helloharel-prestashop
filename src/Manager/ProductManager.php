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
                    $(document).ready(function() {
                        $('#page-header-desc-configuration-add').replaceWith('<a href=\"#\" class=\"btn btn-primary pointer disabled\">" . str_replace("'", "\'", $this->trans('Products are managed by Hello Harel', array(), 'Modules.Helloharel.Admin')) . "</a>');
                        $('.product-header > .row').append('<div class=\"col-xxl-10\"><div class=\"alert alert-info\" style=\"margin-top: 20px; margin-bottom: 0;\"><a href=\"$instanceUrl/products/products/by_reference/{$params['id_product']}#config\" class=\"btn btn-primary float-right\"><i class=\"material-icons\">edit</i> " . str_replace("'", "\'", $this->trans('View on Hello Harel', array(), 'Modules.Helloharel.Admin') . "</a>" . $this->trans('This product is managed by Hello Harel.', array(), 'Modules.Helloharel.Admin')) . "</div></div>');
                    });
                    </script>";
                    //"
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
            <style>
                #step1, #tab_step1,
                #step5, #tab_step5,
                #step6, #tab_step6,
                #hooks, #tab_hooks,
                #product_form_delete_btn,
                #product_form_save_duplicate_btn,
                #product_form_save_new_btn
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
                #form_step1_name_1 {
                    border: none;
                    background: transparent;
                }
                #step2, #step3, #step4 {
                    background: #FFF;
                }
            });
            </style>
            <script>
            $(document).ready(function() {
                $('#form_step1_name_1').prop('disabled', true);
                $('#form_step2_price').closest('.col-xl-2').hide();
                $('#form_step2_price_ttc').closest('.col-xl-2').hide();
                $('#form_step2_unit_price').closest('.mx-auto').removeClass('mx-auto');
                $('#form_step2_on_sale').closest('.col-md-12').hide();
                $('#form_step2_id_tax_rules_group').closest('.col-md-12').hide();
                $('#form_step2_wholesale_price').closest('.col-md-12').hide();
            });
            </script>
            ";
        }
    }
}
