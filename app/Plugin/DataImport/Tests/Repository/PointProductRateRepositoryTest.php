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
 * Class DataImportInfoRepositoryTest
 *
 * @package Eccube\Tests\Repository
 */
class DataImportProductRateRepositoryTest extends EccubeTestCase
{
    public function testSaveDataImportProductRate(){
        $Product = $this->createProductRate();
        $repository = $this->app['eccube.plugin.dataimport.repository.dataimportproductrate'];
        $DataImportProduct = $repository->findOneBy(
            array('Product' => $Product)
        );
        $this->expected = 2;
        $this->actual = $DataImportProduct->getPlgDataImportProductRate();
        $this->verify();
    }

    public function testIsSameDataImport(){
        $Product = $this->createProductRate();
        $repository = $this->app['eccube.plugin.dataimport.repository.dataimportproductrate'];
        $isSameDataImport = $repository->isSameDataImport(2, $Product->getId());
        $this->assertTrue($isSameDataImport);
    }

    public function testGetLastDataImportProductRateById(){
        $Product = $this->createProductRate();
        $repository = $this->app['eccube.plugin.dataimport.repository.dataimportproductrate'];
        $productRateDataImport = $repository->getLastDataImportProductRateById($Product->getId());
        $this->expected = 2;
        $this->actual = $productRateDataImport;
        $this->verify();
    }

    public function testGetDataImportProductRateByEntity(){
        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);
        $repository = $this->app['eccube.plugin.dataimport.repository.dataimportproductrate'];
        $OrderDetails = $Order->getOrderDetails();
        $Product = $OrderDetails[0]->getProduct();
        $products = array();
        $products[$Product->getId()] = $OrderDetails[0];
        $repository->saveDataImportProductRate(2, $Product);
        $productRates = $repository->getDataImportProductRateByEntity($products);
        $this->expected = 2;
        $this->actual = $productRates[$Product->getId()];
        $this->verify();
    }

    public function createProductRate(){
        $Product = $this->createProduct();
        $repository = $this->app['eccube.plugin.dataimport.repository.dataimportproductrate'];
        $repository->saveDataImportProductRate(2, $Product);
        return $Product;
    }


}

