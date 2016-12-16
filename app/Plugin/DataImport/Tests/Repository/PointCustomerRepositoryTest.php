<?php
/*
* This file is part of EC-CUBE
*
* Copyright(c) 2000-2016 LOCKON CO.,LTD. All Rights Reserved.
* http://www.lockon.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace Eccube\Tests\Repository;

use Eccube\Application;
use Eccube\Tests\EccubeTestCase;

/**
 * Class DataImportCustomerRepositoryTest
 *
 * @package Eccube\Tests\Repository
 */
class DataImportCustomerRepositoryTest extends EccubeTestCase
{
    public function testSaveDataImport(){
        $Customer = $this->createCustomer();
        $DataImportCustomer = $this->app['eccube.plugin.dataimport.repository.dataimportcustomer']->saveDataImport(100, $Customer);
        $this->expected = 100;
        $this->actual = $DataImportCustomer->getPlgDataImportCurrent();
        $this->verify();
    }

    public function testGetLastDataImportById(){
        $Customer = $this->createCustomer();
        $DataImportCustomer = $this->app['eccube.plugin.dataimport.repository.dataimportcustomer']->saveDataImport(101, $Customer);
        $dataimport = $this->app['eccube.plugin.dataimport.repository.dataimportcustomer']->getLastDataImportById($Customer->getId());
        $this->expected = 101;
        $this->actual = $dataimport;
        $this->verify();
    }

    public function testGetLastDataImportByIdNoResults(){
        $Customer = $this->createCustomer();
        $dataimport = $this->app['eccube.plugin.dataimport.repository.dataimportcustomer']->getLastDataImportById($Customer->getId());
        $this->expected = 0;
        $this->actual = $dataimport;
        $this->verify();
    }

    public function testGetLastDataImportByIdException(){

        try {
            $this->app['eccube.plugin.dataimport.repository.dataimportcustomer']->getLastDataImportById(null);
            $this->fail('Throwable to \InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('customer_id is empty.', $e->getMessage());
        }
    }
}

