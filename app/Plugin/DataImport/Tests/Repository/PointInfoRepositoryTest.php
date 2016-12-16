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
use Plugin\DataImport\Entity\DataImportInfo;

/**
 * Class DataImportInfoRepositoryTest
 *
 * @package Eccube\Tests\Repository
 */
class DataImportInfoRepositoryTest extends EccubeTestCase
{

    public function testGetLastInsertData()
    {
        $DataImportInfo = $this->app['eccube.plugin.dataimport.repository.dataimportinfo']->getLastInsertData();
        // インストール時に初期データが投入されるため, null ではなく オブジェクトが返却されることを確認する.
        $this->assertNotNull($DataImportInfo);;
    }

    public function testSave(){
        $DataImportInfo = $this->createDataImportInfo();
        $this->app['eccube.plugin.dataimport.repository.dataimportinfo']->save($DataImportInfo);

        $DataImportInfo2 = $this->app['eccube.plugin.dataimport.repository.dataimportinfo']->getLastInsertData();
        $this->assertEquals($DataImportInfo->getPlgAddDataImportStatus(), $DataImportInfo2->getPlgAddDataImportStatus());
        $this->assertEquals($DataImportInfo->getPlgBasicDataImportRate(), $DataImportInfo2->getPlgBasicDataImportRate());
        $this->assertEquals($DataImportInfo->getPlgCalculationType(), $DataImportInfo2->getPlgCalculationType());
        $this->assertEquals($DataImportInfo->getPlgDataImportConversionRate(), $DataImportInfo2->getPlgDataImportConversionRate());
        $this->assertEquals($DataImportInfo->getPlgRoundType(), $DataImportInfo2->getPlgRoundType());
    }

    public function createDataImportInfo(){
        $DataImportInfo = new DataImportInfo();
        $DataImportInfo
            ->setPlgAddDataImportStatus(100)
            ->setPlgBasicDataImportRate(100)
            ->setPlgCalculationType(100)
            ->setPlgDataImportConversionRate(100)
            ->setPlgRoundType(100);
        return $DataImportInfo;
    }
}

