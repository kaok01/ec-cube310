<?php
/*
* This file is part of EC-CUBE
*
* Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
* http://www.lockon.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\CustomerTag\Controller;

use Plugin\CustomerTag\Form\Type\CustomerTagType;
use Eccube\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception as HttpException;

class CustomerTagController
{
    private $main_title;
    private $sub_title;

    public function __construct()
    {
    }

    public function index(Application $app, Request $request, $id)
    {
    	$repos = $app['eccube.plugin.customertag.repository.customertag'];

		$TargetCustomerTag = new \Plugin\CustomerTag\Entity\CustomerTag();

        if ($id) {
            $TargetCustomerTag = $repos->find($id);
            if (!$TargetCustomerTag) {
                throw new NotFoundHttpException();
            }
        }

        $form = $app['form.factory']
            ->createBuilder('admin_customertag', $TargetCustomerTag)
            ->getForm();

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $status = $repos->save($TargetCustomerTag);

                if ($status) {
                    $app->addSuccess('admin.customertag.save.complete', 'admin');
                    return $app->redirect($app->url('admin_customertag'));
                } else {
                    $app->addError('admin.customertag.save.error', 'admin');
                }
            }
        }
    	
        $CustomerTags = $app['eccube.plugin.customertag.repository.customertag']->findAll();

        return $app->render('CustomerTag/View/admin/customertag.twig', array(
        	'form'   		=> $form->createView(),
            'CustomerTags' 		=> $CustomerTags,
            'TargetCustomerTag' 	=> $TargetCustomerTag,
        ));
    }

    public function delete(Application $app, Request $request, $id)
    {
    	$repos = $app['eccube.plugin.customertag.repository.customertag'];

        $TargetCustomerTag = $repos->find($id);
        
        if (!$TargetCustomerTag) {
            throw new NotFoundHttpException();
        }

        $form = $app['form.factory']
            ->createNamedBuilder('admin_customertag', 'form', null, array(
                'allow_extra_fields' => true,
            ))
            ->getForm();

        $status = false;
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $status = $repos->delete($TargetCustomerTag);
            }
        }

        if ($status === true) {
            $app->addSuccess('admin.customertag.delete.complete', 'admin');
        } else {
            $app->addError('admin.customertag.delete.error', 'admin');
        }

        return $app->redirect($app->url('admin_customertag'));
    }

    public function up(Application $app, Request $request, $id)
    {
    	$repos = $app['eccube.plugin.customertag.repository.customertag'];
    	
        $TargetCustomerTag = $repos->find($id);
        if (!$TargetCustomerTag) {
            throw new NotFoundHttpException();
        }

        $form = $app['form.factory']
            ->createNamedBuilder('admin_customertag', 'form', null, array(
                'allow_extra_fields' => true,
            ))
            ->getForm();

        $status = false;
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $status = $repos->up($TargetCustomerTag);
            }
        }

        if ($status === true) {
            $app->addSuccess('admin.customertag.down.complete', 'admin');
        } else {
            $app->addError('admin.customertag.down.error', 'admin');
        }

        return $app->redirect($app->url('admin_customertag'));
    }

    public function down(Application $app, Request $request, $id)
    {
    	$repos = $app['eccube.plugin.customertag.repository.customertag'];
    	
        $TargetCustomerTag = $repos->find($id);
        if (!$TargetCustomerTag) {
            throw new NotFoundHttpException();
        }

        $form = $app['form.factory']
            ->createNamedBuilder('admin_customertag', 'form', null, array(
                'allow_extra_fields' => true,
            ))
            ->getForm();

        $status = false;
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $status = $repos->down($TargetCustomerTag);
            }
        }

        if ($status === true) {
            $app->addSuccess('admin.customertag.down.complete', 'admin');
        } else {
            $app->addError('admin.customertag.down.error', 'admin');
        }

        return $app->redirect($app->url('admin_customertag'));
    }

}
