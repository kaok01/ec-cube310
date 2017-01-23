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

namespace Plugin\DownloadProduct\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Plugin\DownloadProduct\Helper\DownloadProductHistoryHelper\DownloadProductHistoryHelper;

/**
 * Class DownloadProductRepository
 * @package Plugin\DownloadProduct\Repository
 */
class DownloadProductRepository extends EntityRepository
{
    /**
     * カスタマーIDを基準にダウンロード商品の合計を計算
     * @param int $customer_id
     * @param array $orderIds
     * @return int 保有ダウンロード商品
     */
    public function calcCurrentDownloadProduct($customer_id, array $orderIds)
    {
        try {
            // ログテーブルからダウンロード商品を計算
            $qb = $this->createQueryBuilder('p');
            $qb->select('SUM(p.plg_dynamic_downloadproduct) as downloadproduct_sum')
                ->where(
                    $qb->expr()->andX(
                        $qb->expr()->isNull('p.order_id'),
                        $qb->expr()->eq('p.customer_id', $customer_id)
                    )
                )
                ->orWhere(
                    $qb->expr()->andX(
                        $qb->expr()->eq('p.plg_downloadproduct_type', DownloadProductHistoryHelper::STATE_USE),
                        $qb->expr()->eq('p.customer_id', $customer_id)
                    )
                );
            if (!empty($orderIds)) {
                $qb->orWhere(
                    $qb->expr()->andX(
                        $qb->expr()->neq('p.plg_downloadproduct_type', DownloadProductHistoryHelper::STATE_PRE_USE),
                        $qb->expr()->in('p.order_id', $orderIds)
                    )
                );
            }
            // 合計ダウンロード商品
            $downloadproduct = $qb->getQuery()->getSingleScalarResult();
            return (int)$downloadproduct;
        } catch (NoResultException $e) {
            return 0;
        }
    }

    /**
     * 仮ダウンロード商品を会員IDを基に返却
     *  - 合計値
     * @param array $orderIds
     * @return int 仮ダウンロード商品
     */
    public function calcProvisionalAddDownloadProduct(array $orderIds)
    {
        if (count($orderIds) < 1) {
            return 0;
        }

        try {
            $qb = $this->createQueryBuilder('p');
            $qb->select('SUM(p.plg_dynamic_downloadproduct) as downloadproduct_sum')
                ->where($qb->expr()->in('p.order_id', $orderIds))
                ->andWhere($qb->expr()->neq('p.plg_downloadproduct_type', DownloadProductHistoryHelper::STATE_USE))
                ->andWhere($qb->expr()->neq('p.plg_downloadproduct_type', DownloadProductHistoryHelper::STATE_PRE_USE));

            $downloadproduct = $qb->getQuery()->getSingleScalarResult();

            return (int)$downloadproduct;
        } catch (NoResultException $e) {
            return 0;
        }
    }

    /**
     * 受注に対して行われた最後の付与ダウンロード商品を取得
     * @param $order
     * @param $default レコードがない時のデフォルト値 nullと0を区別したい際は、この引数を利用する
     * @return int 付与ダウンロード商品
     */
    public function getLatestAddDownloadProductByOrder(Order $Order, $default = 0)
    {
        try {
            // 受注をもとにその受注に対して行われた最後の付与ダウンロード商品を取得
            $qb = $this->createQueryBuilder('p')
                ->andWhere('p.customer_id = :customer_id')
                ->andWhere('p.order_id = :order_id')
                ->andWhere('p.plg_downloadproduct_type = :downloadproduct_type')
                ->setParameter('customer_id', $Order->getCustomer()->getId())
                ->setParameter('order_id', $Order->getId())
                ->setParameter('downloadproduct_type', DownloadProductHistoryHelper::STATE_ADD)
                ->orderBy('p.plg_downloadproduct_id', 'desc')
                ->setMaxResults(1);

            $DownloadProduct = $qb->getQuery()->getSingleResult();

            return $DownloadProduct->getPlgDynamicDownloadProduct();

        } catch (NoResultException $e) {
            return $default;
        }
    }

    /**
     * 最終利用ダウンロード商品を受注エンティティより取得
     * @param Order $order
     * @param $default レコードがない時のデフォルト値 nullと0を区別したい際は、この引数を利用する
     * @return int 利用ダウンロード商品
     */
    public function getLatestUseDownloadProduct(Order $Order, $default = 0)
    {
        $Customer = $Order->getCustomer();

        if (!$Customer instanceof Customer) {
            return 0;
        }

        try {
            // 履歴情報をもとに現在利用ダウンロード商品を計算し取得
            $qb = $this->createQueryBuilder('p')
                ->where('p.customer_id = :customerId')
                ->andWhere('p.order_id = :orderId')
                ->andWhere('p.plg_downloadproduct_type = :downloadproductType')
                ->setParameter('customerId', $Customer->getId())
                ->setParameter('orderId', $Order->getId())
                ->setParameter('downloadproductType', DownloadProductHistoryHelper::STATE_USE)
                ->orderBy('p.plg_downloadproduct_id', 'desc')
                ->setMaxResults(1);

            $DownloadProduct = $qb->getQuery()->getSingleResult();

            return $DownloadProduct->getPlgDynamicDownloadProduct();
        } catch (NoResultException $e) {
            return $default;
        }
    }

    /**
     * 最終仮利用ダウンロード商品を取得
     * @param Order $order
     * @return int 仮利用ダウンロード商品
     */
    public function getLatestPreUseDownloadProduct(Order $order)
    {
        try {
            // 履歴情報をもとに現在利用ダウンロード商品を計算し取得
            $qb = $this->createQueryBuilder('p')
                ->where('p.customer_id = :customerId')
                ->andWhere('p.order_id = :orderId')
                ->andWhere('p.plg_downloadproduct_type = :downloadproductType')
                ->setParameter('customerId', $order->getCustomer()->getId())
                ->setParameter('orderId', $order->getId())
                ->setParameter('downloadproductType', DownloadProductHistoryHelper::STATE_PRE_USE)
                ->orderBy('p.plg_downloadproduct_id', 'desc')
                ->setMaxResults(1);

            $DownloadProduct = $qb->getQuery()->getSingleResult();

            return $DownloadProduct->getPlgDynamicDownloadProduct();
        } catch (NoResultException $e) {
            return 0;
        }
    }
}
