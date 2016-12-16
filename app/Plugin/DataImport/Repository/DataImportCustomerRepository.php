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
     * 保有ポイントの保存
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

    /**
     * 前回保存のポイントと今回保存のポイントの値を判定
     * @param $dataimport
     * @param $customerId
     * @return bool
     */
    public function isSameDataImport($dataimport, $customerId)
    {
        // 最終設定値を会員IDから取得
        $lastDataImport = $this->getLastDataImportById($customerId);

        // 値が同じ場合
        if ((integer)$dataimport === (integer)$lastDataImport) {
            return true;
        }

        return false;
    }

    /**
     * 会員IDをもとに一番最後に保存した保有ポイントを取得.
     *
     * @param $customerId 会員ID
     * @return integer
     * @throws \InvalidArgumentException
     */
    public function getLastDataImportById($customerId)
    {
        // 引数判定
        if (empty($customerId)) {
            throw new \InvalidArgumentException('customer_id is empty.');
        }

        try {
            // 会員IDをもとに最終保存の保有ポイントを取得
            $qb = $this->createQueryBuilder('pc');
            $qb->where('pc.customer_id = :customerId')
                ->setParameter('customerId', $customerId)
                ->orderBy('pc.plg_dataimport_customer_id', 'desc')
                ->setMaxResults(1);

            $DataImportCustomer = $qb->getQuery()->getSingleResult();

            return $DataImportCustomer->getPlgDataImportCurrent();
        } catch (NoResultException $e) {
            return 0;
        }
    }
}
