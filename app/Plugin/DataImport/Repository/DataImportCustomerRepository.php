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
namespace Plugin\DataImport\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Plugin\DataImport\Entity\DataImportCustomer;

/**
 * Class DataImportCustomerRepository
 * @package Plugin\DataImport\Repository
 */
class DataImportCustomerRepository extends EntityRepository
{
    /**
     * 保有データインポートの保存
     * @param $dataimport
     * @param $customer
     * @return bool|DataImportCustomer
     * @throws NoResultException
     */
    public function saveDataImport($dataimport, $customer)
    {
        // 引数判定
        if ((!isset($dataimport) && $dataimport != 0) || empty($customer)) {
            return false;
        }

        $DataImportCustomer = new DataImportCustomer();
        $DataImportCustomer->setPlgDataImportCurrent((integer)$dataimport);
        $DataImportCustomer->setCustomer($customer);

        $em = $this->getEntityManager();
        $em->persist($DataImportCustomer);
        $em->flush($DataImportCustomer);

        return $DataImportCustomer;
    }


}
