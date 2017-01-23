<?php
/*
 * Plugin Name : ProductOption
 *
 * Copyright (C) 2015 BraTech Co., Ltd. All Rights Reserved.
 * http://www.bratech.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ProductOption\Event\WorkPlace;

use Eccube\Common\Constant;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Validator\Constraints as Assert;

class AdminOrderEdit extends AbstractWorkPlace
{    
    public function createTwig(TemplateEvent $event)
    {
        $app = $this->app;
        $parameters = $event->getParameters();
        $request = $app['request'];
        $BaseInfo = $app['eccube.repository.base_info']->get();

        $Order = $parameters['Order'];

        $source = $event->getSource();
        
        if(preg_match('/<(.*)\s*id="shipment_item__class_category_name.*>\n/',$source, $result)){
            $start_tag = $result[0];
            $tag_name = trim($result[1]);
            $end_tag = '</' . $tag_name . '>';
            $start_index = strpos($source, $start_tag);
            $end_index = strpos($source, $end_tag, $start_index);
            $search = substr($source, $start_index, ($end_index - $start_index));
                
            $snipet = file_get_contents($app['config']['plugin_realdir']. '/ProductOption/Resource/template/admin/Order/shipmentitem_option.twig');
            $replace = $search.$snipet;
            $source = str_replace($search, $replace, $source);
        }
       

        $event->setSource($source);
        // $parameters['plgForm'] = $form->createView();
        // $parameters['plgOrderDetails'] = $plgOrderDetails;
        // $parameters['plgShipmentItems'] = $plgShipmentItems;
        // $event->setParameters($parameters);
    }
    


}
