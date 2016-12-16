<?php
/*
 * Copyright(c) 2015 GMO Payment Gateway, Inc. All rights reserved.
 * http://www.gmo-pg.com/
 */

namespace Plugin\ContactMailConfig\Controller\Helper;

class Helper_CSV {
    
    public static function getFieldPMPQCSV(){
        return array(
            array(
                'entity_name' => 'Plugin\\ContactMailConfig\\Entity\\ContactMailConfig',
                'field_name' => 'id',
                'disp_name' => '商品ID',
            ),
            array(
                'entity_name' => 'Plugin\\ContactMailConfig\\Entity\\ContactMailConfig',
                'field_name' => 'Product',
                'disp_name' => '商品名',
                'reference_field_name' => 'name'
            ),
            array(
                'entity_name' => 'Plugin\\ContactMailConfig\\Entity\\ContactMailConfig',
                'field_name' => 'quantity',
                'disp_name' => '最低購入個数'
            )
        );
    }
}