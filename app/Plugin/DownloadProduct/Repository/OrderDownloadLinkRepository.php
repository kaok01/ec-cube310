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
use Plugin\DownloadProduct\Entity\OrderDownloadLink;

/**
 * Class DownloadProductCustomerRepository
 * @package Plugin\DownloadProduct\Repository
 */
class OrderDownloadLinkRepository extends EntityRepository
{
    public function create($productdownloadid, $order)
    {
        // 引数判定
        if ($productdownloadid=="" || empty($order)) {
            return false;
        }

        // $DownloadProductOrder = new DownloadProductOrder();
        // $DownloadProductOrder->setPlgDownloadProductOrderId($downloadproductid);
        // $DownloadProductOrder->setOrder($order);

        // $em = $this->getEntityManager();
        // $em->persist($DownloadProductOrder);
        // $em->flush($DownloadProductOrder);

        return $DownloadProductOrder;
    }


}
