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
use Plugin\DownloadProduct\Entity\DownloadProductCustomer;

/**
 * Class DownloadProductCustomerRepository
 * @package Plugin\DownloadProduct\Repository
 */
class DownloadProductCustomerRepository extends EntityRepository
{
    /**
     * 保有ダウンロード商品の保存
     * @param $downloadproduct
     * @param $customer
     * @return bool|DownloadProductCustomer
     * @throws NoResultException
     */
    public function create($downloadproductid, $customer)
    {
        // 引数判定
        if ($downloadproductid=="" || empty($customer)) {
            return false;
        }

        $DownloadProductCustomer = new DownloadProductCustomer();
        $DownloadProductCustomer->setPlgDownloadProductCustomerId($downloadproductid);
        $DownloadProductCustomer->setCustomer($customer);

        $em = $this->getEntityManager();
        $em->persist($DownloadProductCustomer);
        $em->flush($DownloadProductCustomer);

        return $DownloadProductCustomer;
    }



}
