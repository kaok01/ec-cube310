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
 * Class DataImport
 * @package Plugin\DataImport\Entity
 */
class DataImport extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     */
    private $plg_dataimport_id;
    /**
     * @var integer
     */
    private $plg_dynamic_dataimport;
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
    private $plg_dataimport_info_id;
    /**
     * @var \Eccube\Entity\Order
     */
    private $Order;
    /**
     * @var \Eccube\Entity\Customer
     */
    private $Customer;
    /**
     * @var smallint
     */
    private $plg_dataimport_type;
    /**
     * @var string
     */
    private $plg_dataimport_action_name;
    /**
     * @var \Plugin\DataImport\Entity\DataImportProductRate
     */
    private $DataImportProductRate;
    /**
     * @var \Plugin\DataImport\Entity\DataImportInfo
     */
    private $DataImportInfo;
    /**
     * @var timestamp
     */
    private $create_date;
    /**
     * @var timestamp
     */
    private $update_date;

    /**
     * Set plg_dataimport_id
     *
     * @param integer $plg_dataimport_id
     * @return DataImport
     */
    public function setPlgDataImportId($plg_dataimport_id)
    {
        $this->plg_dataimport_id = $plg_dataimport_id;

        return $this;
    }

    /**
     * Get plg_dataimport_id
     *
     * @return integer
     */
    public function getPlgDataImportId()
    {
        return $this->plg_dataimport_id;
    }

    /**
     * Set order_id
     *
     * @param integer $order_id
     * @return DataImport
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
     * @return DataImport
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
     * Set plg_dataimport_info_id
     *
     * @param integer $plg_dataimport_info_id
     * @return DataImport
     */
    public function setPlgDataImportInfoId($plg_dataimport_info_id)
    {
        $this->plg_dataimport_info_id = $plg_dataimport_info_id;

        return $this;
    }

    /**
     * Get plg_dataimport_info_id
     *
     * @return integer
     */
    public function getPlgDataImportInfoId()
    {
        return $this->plg_dataimport_info_id;
    }

    /**
     * Set plg_dynamic_dataimport
     *
     * @param integer $plg_dynamic_dataimport
     * @return DataImport
     */
    public function setPlgDynamicDataImport($plg_dynamic_dataimport)
    {
        $this->plg_dynamic_dataimport = $plg_dynamic_dataimport;

        return $this;
    }

    /**
     * Get plg_dynamic_dataimport
     *
     * @return integer
     */
    public function getPlgDynamicDataImport()
    {
        return $this->plg_dynamic_dataimport;
    }

    /**
     * Set Order
     *
     * @param \Eccube\Entity\Order $Order
     * @return DataImport
     */
    public function setOrder($Order)
    {
        $this->Order = $Order;

        return $this;
    }

    /**
     * Get Order
     *
     * @return \Eccube\Entity\Order
     */
    public function getOrder()
    {
        return $this->Order;
    }

    /**
     * Set Customer
     *
     * @param \Eccube\Entity\Customer $Customer
     * @return DataImport
     */
    public function setCustomer($Customer)
    {
        $this->Customer = $Customer;

        return $this;
    }

    /**
     * Get Customer
     *
     * @return \Eccube\Entity\Customer
     */
    public function getCustomer()
    {
        return $this->Customer;
    }

    /**
     * Set plg_dataimport_type
     *
     * @param smallint
     * @return DataImport
     */
    public function setPlgDataImportType($plg_dataimport_type)
    {
        $this->plg_dataimport_type = $plg_dataimport_type;

        return $this;
    }

    /**
     * Get plg_dataimport_type
     *
     * @return smallint
     */
    public function getPlgDataImportType()
    {
        return $this->plg_dataimport_type;
    }

    /**
     * Set plg_dataimport_action_name
     *
     * @param string
     * @return DataImport
     */
    public function setPlgDataImportActionName($plg_dataimport_action_name)
    {
        $this->plg_dataimport_action_name = $plg_dataimport_action_name;

        return $this;
    }

    /**
     * Get plg_dataimport_action_name
     *
     * @return string
     */
    public function getPlgDataImportActionName()
    {
        return $this->plg_dataimport_action_name;
    }

    /**
     * Set DataImportProductRate
     *
     * @param \Plugin\DataImport\Entity\DataImportProductRate
     * @return DataImport
     */
    public function setDataImportProductRate($DataImportProductRate)
    {
        $this->DataImportProductRate = $DataImportProductRate;

        return $this;
    }

    /**
     * Get DataImportProductRate
     *
     * @return \Plugin\DataImport\Entity\DataImportProductRate
     */
    public function getDataImportProductRate()
    {
        return $this->DataImportProductRate;
    }

    /**
     * Set DataImportInfo
     *
     * @param Eccube\Plugin\DataImport\Entity\DataImportInfo
     * @return DataImport
     */
    public function setDataImportInfo($DataImportInfo)
    {
        $this->DataImportInfo = $DataImportInfo;

        return $this;
    }

    /**
     * Get DataImportInfo
     *
     * @return \Plugin\DataImport\Entity\DataImportInfo
     */
    public function getDataImportInfo()
    {
        return $this->DataImportInfo;
    }

    /**
     * Set create_date
     *
     * @param integer $create_date
     * @return DataImport
     */
    public function setCreateDate($create_date)
    {
        $this->create_date = $create_date;

        return $this;
    }

    /**
     * Get create_date
     *
     * @return DataImport
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set update_date
     *
     * @param integer $update_date
     * @return DataImport
     */
    public function setUpdateDate($update_date)
    {
        $this->update_date = $update_date;

        return $this;
    }

    /**
     * Get update_date
     *
     * @return DataImport
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }
}
