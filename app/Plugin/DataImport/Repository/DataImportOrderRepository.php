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
use Plugin\DataImport\Entity\DataImportOrder;

/**
 * Class DataImportCustomerRepository
 * @package Plugin\DataImport\Repository
 */
class DataImportOrderRepository extends EntityRepository
{
    /**
     * 保有ポイントの保存
     * @param $dataimport
     * @param $customer
     * @return bool|DataImportCustomer
     * @throws NoResultException
     */
    public function saveDataImport($dataimport, $order)
    {
        // 引数判定
        if ((!isset($dataimport) && $dataimport != 0) || empty($order)) {
            return false;
        }

        $DataImportOrder = new DataImportOrder();
        $DataImportOrder->setPlgDataImportCurrent((integer)$dataimport);
        $DataImportOrder->setOrder($order);

        $em = $this->getEntityManager();
        $em->persist($DataImportOrder);
        $em->flush($DataImportOrder);

        return $DataImportOrder;
    }

}
