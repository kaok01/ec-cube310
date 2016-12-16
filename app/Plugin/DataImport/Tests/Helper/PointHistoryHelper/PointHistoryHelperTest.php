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
namespace Eccube\Tests\Helper\DataImportHistoryHelper;

use Eccube\Application;
use Eccube\Tests\EccubeTestCase;
use Plugin\DataImport\Entity\DataImport;
use Plugin\DataImport\Entity\DataImportInfo;
use Plugin\DataImport\Helper\DataImportHistoryHelper\DataImportHistoryHelper;

/**
 * Class DataImportHistoryHelperTest
 *
 * @package Eccube\Tests\Helper\DataImportHistoryHelper
 */
class DataImportHistoryHelperTest extends EccubeTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->Customer = $this->createCustomer();
        $this->Order = $this->createOrder($this->Customer);
    }

    /**
     * DataImportStatus のレコードが存在しない状態で fixDataImportStatus() をコールするテスト.
     */
    public function testFixDataImportStatusWithInitialOrder()
    {
        $this->app['eccube.plugin.dataimport.history.service']->addEntity($this->Order);
        $this->app['eccube.plugin.dataimport.history.service']->addEntity($this->Order->getCustomer());
        $this->app['eccube.plugin.dataimport.history.service']->fixDataImportStatus();

        $dataimportStatus = $this->app['eccube.plugin.dataimport.repository.dataimportstatus']->findOneBy(
            array('order_id' => $this->Order->getId())
        );

        $this->expected = $this->Customer->getId();
        $this->actual = $dataimportStatus->getCustomerId();
        $this->verify();

        $this->expected = $this->app['eccube.plugin.dataimport.repository.dataimportstatus']->getFixStatusValue();
        $this->actual = $dataimportStatus->getStatus();
        $this->verify();
    }
}
