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

namespace Plugin\DataImport\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class DataImportStatus
 * @package Plugin\DataImportStatus\Entity
 */
class DataImportStatus extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     */
    private $dataimport_status_id;
    /**
     * @var integer
     */
    private $order_id;
    /**
     * @var integer
     */
    private $customer_id;
    /**
     * @var integer
     */
    private $status;
    /**
     * @var integer
     */
    private $del_flg;
    /**
     * @var timestamp
     */
    private $dataimport_fix_date;

    /**
     * Set dataimport_status_id
     *
     * @param integer $dataimport_status_id
     * @return DataImportStatus
     */
    public function setPlgDataImportStatusId($dataimport_status_id)
    {
        $this->dataimport_status_id = $dataimport_status_id;

        return $this;
    }

    /**
     * Get dataimport_status_id
     *
     * @return integer
     */
    public function getPlgDataImportStatusId()
    {
        return $this->dataimport_status_id;
    }

    /**
     * Set order_id
     *
     * @param integer $order_id
     * @return DataImportStatus
     */
    public function setOrderId($order_id)
    {
        $this->order_id = $order_id;

        return $this;
    }

    /**
     * Get order_id
     *
     * @return integer
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set customer_id
     *
     * @param integer $customer_id
     * @return DataImportStatus
     */
    public function setCustomerId($customer_id)
    {
        $this->customer_id = $customer_id;

        return $this;
    }

    /**
     * Get customer_id
     *
     * @return integer
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return DataImportStatus
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set del_flg
     *
     * @param integer $del_flg
     * @return DataImportStatus
     */
    public function setDelFlg($del_flg)
    {
        $this->del_flg = $del_flg;

        return $this;
    }

    /**
     * Get del_flg
     *
     * @return integer
     */
    public function getDelFlg()
    {
        return $this->del_flg;
    }

    /**
     * Set dataimport_fix_date
     *
     * @param datetime $dataimport_fix_date
     * @return DataImportStatus
     */
    public function setDataImportFixDate($dataimport_fix_date)
    {
        $this->dataimport_fix_date = $dataimport_fix_date;

        return $this;
    }

    /**
     * Get dataimport_fix_date
     *
     * @return datetime
     */
    public function getDataImportFixDate()
    {
        return $this->dataimport_fix_date;
    }
}
