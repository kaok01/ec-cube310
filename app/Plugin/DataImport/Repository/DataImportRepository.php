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
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Plugin\DataImport\Helper\DataImportHistoryHelper\DataImportHistoryHelper;

/**
 * Class DataImportRepository
 * @package Plugin\DataImport\Repository
 */
class DataImportRepository extends EntityRepository
{
    /**
     * カスタマーIDを基準にデータインポートの合計を計算
     * @param int $customer_id
     * @param array $orderIds
     * @return int 保有データインポート
     */
    public function calcCurrentDataImport($customer_id, array $orderIds)
    {
        try {
            // ログテーブルからデータインポートを計算
            $qb = $this->createQueryBuilder('p');
            $qb->select('SUM(p.plg_dynamic_dataimport) as dataimport_sum')
                ->where(
                    $qb->expr()->andX(
                        $qb->expr()->isNull('p.order_id'),
                        $qb->expr()->eq('p.customer_id', $customer_id)
                    )
                )
                ->orWhere(
                    $qb->expr()->andX(
                        $qb->expr()->eq('p.plg_dataimport_type', DataImportHistoryHelper::STATE_USE),
                        $qb->expr()->eq('p.customer_id', $customer_id)
                    )
                );
            if (!empty($orderIds)) {
                $qb->orWhere(
                    $qb->expr()->andX(
                        $qb->expr()->neq('p.plg_dataimport_type', DataImportHistoryHelper::STATE_PRE_USE),
                        $qb->expr()->in('p.order_id', $orderIds)
                    )
                );
            }
            // 合計データインポート
            $dataimport = $qb->getQuery()->getSingleScalarResult();
            return (int)$dataimport;
        } catch (NoResultException $e) {
            return 0;
        }
    }

    /**
     * 仮データインポートを会員IDを基に返却
     *  - 合計値
     * @param array $orderIds
     * @return int 仮データインポート
     */
    public function calcProvisionalAddDataImport(array $orderIds)
    {
        if (count($orderIds) < 1) {
            return 0;
        }

        try {
            $qb = $this->createQueryBuilder('p');
            $qb->select('SUM(p.plg_dynamic_dataimport) as dataimport_sum')
                ->where($qb->expr()->in('p.order_id', $orderIds))
                ->andWhere($qb->expr()->neq('p.plg_dataimport_type', DataImportHistoryHelper::STATE_USE))
                ->andWhere($qb->expr()->neq('p.plg_dataimport_type', DataImportHistoryHelper::STATE_PRE_USE));

            $dataimport = $qb->getQuery()->getSingleScalarResult();

            return (int)$dataimport;
        } catch (NoResultException $e) {
            return 0;
        }
    }

    /**
     * 受注に対して行われた最後の付与データインポートを取得
     * @param $order
     * @param $default レコードがない時のデフォルト値 nullと0を区別したい際は、この引数を利用する
     * @return int 付与データインポート
     */
    public function getLatestAddDataImportByOrder(Order $Order, $default = 0)
    {
        try {
            // 受注をもとにその受注に対して行われた最後の付与データインポートを取得
            $qb = $this->createQueryBuilder('p')
                ->andWhere('p.customer_id = :customer_id')
                ->andWhere('p.order_id = :order_id')
                ->andWhere('p.plg_dataimport_type = :dataimport_type')
                ->setParameter('customer_id', $Order->getCustomer()->getId())
                ->setParameter('order_id', $Order->getId())
                ->setParameter('dataimport_type', DataImportHistoryHelper::STATE_ADD)
                ->orderBy('p.plg_dataimport_id', 'desc')
                ->setMaxResults(1);

            $DataImport = $qb->getQuery()->getSingleResult();

            return $DataImport->getPlgDynamicDataImport();

        } catch (NoResultException $e) {
            return $default;
        }
    }

    /**
     * 最終利用データインポートを受注エンティティより取得
     * @param Order $order
     * @param $default レコードがない時のデフォルト値 nullと0を区別したい際は、この引数を利用する
     * @return int 利用データインポート
     */
    public function getLatestUseDataImport(Order $Order, $default = 0)
    {
        $Customer = $Order->getCustomer();

        if (!$Customer instanceof Customer) {
            return 0;
        }

        try {
            // 履歴情報をもとに現在利用データインポートを計算し取得
            $qb = $this->createQueryBuilder('p')
                ->where('p.customer_id = :customerId')
                ->andWhere('p.order_id = :orderId')
                ->andWhere('p.plg_dataimport_type = :dataimportType')
                ->setParameter('customerId', $Customer->getId())
                ->setParameter('orderId', $Order->getId())
                ->setParameter('dataimportType', DataImportHistoryHelper::STATE_USE)
                ->orderBy('p.plg_dataimport_id', 'desc')
                ->setMaxResults(1);

            $DataImport = $qb->getQuery()->getSingleResult();

            return $DataImport->getPlgDynamicDataImport();
        } catch (NoResultException $e) {
            return $default;
        }
    }

    /**
     * 最終仮利用データインポートを取得
     * @param Order $order
     * @return int 仮利用データインポート
     */
    public function getLatestPreUseDataImport(Order $order)
    {
        try {
            // 履歴情報をもとに現在利用データインポートを計算し取得
            $qb = $this->createQueryBuilder('p')
                ->where('p.customer_id = :customerId')
                ->andWhere('p.order_id = :orderId')
                ->andWhere('p.plg_dataimport_type = :dataimportType')
                ->setParameter('customerId', $order->getCustomer()->getId())
                ->setParameter('orderId', $order->getId())
                ->setParameter('dataimportType', DataImportHistoryHelper::STATE_PRE_USE)
                ->orderBy('p.plg_dataimport_id', 'desc')
                ->setMaxResults(1);

            $DataImport = $qb->getQuery()->getSingleResult();

            return $DataImport->getPlgDynamicDataImport();
        } catch (NoResultException $e) {
            return 0;
        }
    }
}
