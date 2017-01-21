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

        $source = $event->getSource();
        
        if(preg_match('/admin_product_product_delete.*>\n/',$source, $result)){
            $search = $result[0];
            $snipet = file_get_contents($app['config']['plugin_realdir']. '/DownloadProduct/Resource/template/admin/Order/index.twig');
            $replace = $search. $snipet;
            $source = str_replace($search, $replace, $source);
        }
        

        $event->setSource($source);

    }

}
