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
class DataImportCustomerTagRepository extends EntityRepository
{
    /**
     * 保有ポイントの保存
     * @param $dataimport
     * @param $customer
     * @return bool|DataImportCustomer
     * @throws NoResultException
     */
    public function saveDataImport($customer, $customertag)
    {
        // 引数判定
        if ((!isset($customer) && $customer != 0) || empty($customer)) {
            return false;
        }
        if ((!isset($customertag) && $customertag != 0) || empty($customertag)) {
            return false;
        }

        $DataImportCustomerTag = new DataImportCustomerTag();
        $DataImportCustomerTag->setCustomer($customer);
        $DataImportCustomerTag->setCustomerTag($customertag);

        $em = $this->getEntityManager();
        $em->persist($DataImportCustomerTag);
        $em->flush($DataImportCustomerTag);

        return $DataImportCustomerTag;
    }


}
