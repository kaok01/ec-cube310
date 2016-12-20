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
 * Class DataImportCustomer
 * @package Plugin\DataImport\Entity
 */
class DataImportOrder extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     */
    private $plg_dataimport_order_id;
    /**
     * @var integer
     */
    private $plg_dataimport_current;
    /**
     * @var integer
     */
    private $order_id;
    /**
     * @var \Eccube\Entity\Order
     */
    private $Order;
    /**
     * @var date
     */
    private $create_date;
    /**
     * @var date
     */
    private $update_date;

    /**
     * Set plg_dataimport_order_id
     *
     * @param integer $plg_dataimport_order_id
     * @return DataImportOrder
     */
    public function setPlgDataImportOrderId($plg_dataimport_order_id)
    {
        $this->plg_dataimport_order_id = $plg_dataimport_order_id;

        return $this;
    }

    /**
     * Get plg_dataimport_customer_id
     *
     * @return integer
     */
    public function getPlgDataImportOrderId()
    {
        return $this->plg_dataimport_order_id;
    }

    /**
     * Set plg_dataimport_current
     *
     * @param integer $plg_dataimport_current
     * @return DataImportCustomer
     */
    public function setPlgDataImportCurrent($plg_dataimport_current)
    {
        $this->plg_dataimport_current = $plg_dataimport_current;

        return $this;
    }

    /**
     * Get plg_dataimport_current
     *
     * @return integer plg_dataimport_current
     */
    public function getPlgDataImportCurrent()
    {
        return $this->plg_dataimport_current;
    }

    /**
     * Set customer_id
     *
     * @param integer $customer_id
     * @return DataImportCustomer
     */
    public function setOrderId($order_id)
    {
        $this->order_id = $order_id;

        return $this;
    }

    /**
     * Get customer_id
     *
     * @return integer
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set Customer
     *
     * @param \Eccube\Entity\Customer $Customer
     * @return DataImportCustomer
     */
    public function setOrder($Order)
    {
        $this->Order = $Order;

        return $this;
    }

    /**
     * Get customer
     *
     * @return \Eccube\Entity\Customer
     */
    public function getOrder()
    {
        return $this->Order;
    }

    /**
     * Set create_date
     *
     * @param date $create_date
     * @return DataImportCustomer
     */
    public function setCreateDate($create_date)
    {
        $this->create_date = $create_date;

        return $this;
    }

    /**
     * Get create_date
     *
     * @return date $create_date
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set update_date
     *
     * @param date $update_date
     * @return DataImportCustomer
     */
    public function setUpdateDate($update_date)
    {
        $this->update_date = $update_date;

        return $this;
    }

    /**
     * Get update_date
     *
     * @return date $update_date
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }
}
