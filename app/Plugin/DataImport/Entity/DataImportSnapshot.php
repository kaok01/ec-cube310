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
 * Class DataImportSnapshot
 * @package Plugin\DataImport\Entity
 */
class DataImportSnapshot extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     */
    private $plg_dataimport_snapshot_id;
    /**
     * @var integer
     */
    private $plg_dataimport_use;
    /**
     * @var integer
     */
    private $plg_dataimport_current;
    /**
     * @var integer
     */
    private $plg_dataimport_add;
    /**
     * @var string
     */
    private $plg_dataimport_snap_action_name;
    /**
     * @var integer
     */
    private $order_id;
    /**
     * @var integer
     */
    private $customer_id;
    /**
     * @var \Eccube\Entity\Order
     */
    private $Order;
    /**
     * @var \Eccube\Entity\Custmer
     */
    private $Customer;
    /**
     * @var date
     */
    private $create_date;
    /**
     * @var date
     */
    private $update_date;

    /**
     * Set plg_dataimport_snapshot_id
     *
     * @param integer $plg_dataimport_snapshot_id
     * @return DataImportSnapshot
     */
    public function setPlgDataImportSnapshotId($plg_dataimport_snapshot_id)
    {
        $this->plg_dataimport_snapshot_id = $plg_dataimport_snapshot_id;

        return $this;
    }

    /**
     * Get plg_dataimport_snapshot_id
     * @return integer $plg_dataimport_snapshot_id
     */
    public function getPlgDataImportSnapshotId()
    {
        return $this->plg_dataimport_snapshot_id;
    }

    /**
     * Set plg_dataimport_use
     *
     * @param integer $plg_dataimport_use
     * @return DataImportSnapshot
     */
    public function setPlgDataImportUse($plg_dataimport_use)
    {
        $this->plg_dataimport_use = $plg_dataimport_use;

        return $this;
    }

    /**
     * Get plg_dataimport_use
     * @return integer $plg_dataimport_use
     */
    public function getPlgDataImportUse()
    {
        return $this->plg_dataimport_use;
    }

    /**
     * Set plg_dataimport_current
     *
     * @param integer $plg_dataimport_current
     * @return DataImportSnapshot
     */
    public function setPlgDataImportCurrent($plg_dataimport_current)
    {
        $this->plg_dataimport_current = $plg_dataimport_current;

        return $this;
    }

    /**
     * Get plg_dataimport_current
     * @return integer $plg_dataimport_current
     */
    public function getPlgDataImportCurrent()
    {
        return $this->plg_dataimport_current;
    }

    /**
     * Set plg_dataimport_add
     *
     * @param integer $plg_dataimport_add
     * @return DataImportSnapshot
     */
    public function setPlgDataImportAdd($plg_dataimport_add)
    {
        $this->plg_dataimport_add = $plg_dataimport_add;

        return $this;
    }

    /**
     * Get plg_dataimport_add
     * @return integer $plg_dataimport_add
     */
    public function getPlgDataImportAdd()
    {
        return $this->plg_dataimport_add;
    }

    /**
     * Set plg_dataimport_snap_action_name
     *
     * @param string $plg_dataimport_snap_action_name
     * @return DataImportSnapshot
     */
    public function setPlgDataImportSnapActionName($plg_dataimport_snap_action_name)
    {
        $this->plg_dataimport_snap_action_name = $plg_dataimport_snap_action_name;

        return $this;
    }

    /**
     * Get plg_dataimport_snap_action_name
     * @return string $plg_dataimport_snap_action_name
     */
    public function getPlgDataImportSnapActionName()
    {
        return $this->plg_dataimport_snap_action_name;
    }

    /**
     * Set order_id
     *
     * @param integer $order_id
     * @return DataImportSnapshot
     */
    public function setOrderId($order_id)
    {
        $this->order_id = $order_id;

        return $this;
    }

    /**
     * Get order_id
     * @return integer $order_id
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set customer_id
     *
     * @param integer $customer_id
     * @return DataImportSnapshot
     */
    public function setCustomerId($customer_id)
    {
        $this->customer_id = $customer_id;

        return $this;
    }

    /**
     * Get customer_id
     * @return integer $customer_id
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    /**
     * Set Order
     *
     * @param \Eccube\Entity\Order $Order
     * @return DataImportSnapshot
     */
    public function setOrder($Order)
    {
        $this->Order = $Order;

        return $this;
    }

    /**
     * Get Order
     * @return \Eccube\Entity\Order $Order
     */
    public function getOrder()
    {
        return $this->Order;
    }

    /**
     * Set Customer
     *
     * @param \Eccube\Entity\Customer $Customer
     * @return DataImportSnapshot
     */
    public function setCustomer($Customer)
    {
        $this->Customer = $Customer;

        return $this;
    }

    /**
     * Get Customer
     * @return \Eccube\Entity\Customer $Customer
     */
    public function getCustomer()
    {
        return $this->Customer;
    }

    /**
     * Set create_date
     *
     * @param timestamp $create_date
     * @return DataImportSnapshot
     */
    public function setCreateDate($create_date)
    {
        $this->create_date = $create_date;

        return $this;
    }

    /**
     * Get create_date
     * @return tmestamp create_date
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set update_date
     *
     * @param timestamp $update_date
     * @return DataImportSnapshot
     */
    public function setUpdateDate($update_date)
    {
        $this->update_date = $update_date;

        return $this;
    }

    /**
     * Get update_date
     * @return tmestamp update_date
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }
}
