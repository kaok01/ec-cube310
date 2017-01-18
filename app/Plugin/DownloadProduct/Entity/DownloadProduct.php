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
namespace Plugin\DownloadProduct\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class DownloadProduct
 * @package Plugin\DownloadProduct\Entity
 */
class DownloadProduct extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     */
    private $plg_downloadproduct_id;
    /**
     * @var integer
     */
    private $plg_dynamic_downloadproduct;
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
    private $plg_downloadproduct_info_id;
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
    private $plg_downloadproduct_type;
    /**
     * @var string
     */
    private $plg_downloadproduct_action_name;
    /**
     * @var \Plugin\DownloadProduct\Entity\DownloadProductInfo
     */
    private $DownloadProductInfo;
    /**
     * @var timestamp
     */
    private $create_date;
    /**
     * @var timestamp
     */
    private $update_date;

    /**
     * Set plg_downloadproduct_id
     *
     * @param integer $plg_downloadproduct_id
     * @return DownloadProduct
     */
    public function setPlgDownloadProductId($plg_downloadproduct_id)
    {
        $this->plg_downloadproduct_id = $plg_downloadproduct_id;

        return $this;
    }

    /**
     * Get plg_downloadproduct_id
     *
     * @return integer
     */
    public function getPlgDownloadProductId()
    {
        return $this->plg_downloadproduct_id;
    }

    /**
     * Set order_id
     *
     * @param integer $order_id
     * @return DownloadProduct
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
     * @return DownloadProduct
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
     * Set plg_downloadproduct_info_id
     *
     * @param integer $plg_downloadproduct_info_id
     * @return DownloadProduct
     */
    public function setPlgDownloadProductInfoId($plg_downloadproduct_info_id)
    {
        $this->plg_downloadproduct_info_id = $plg_downloadproduct_info_id;

        return $this;
    }

    /**
     * Get plg_downloadproduct_info_id
     *
     * @return integer
     */
    public function getPlgDownloadProductInfoId()
    {
        return $this->plg_downloadproduct_info_id;
    }

    /**
     * Set plg_dynamic_downloadproduct
     *
     * @param integer $plg_dynamic_downloadproduct
     * @return DownloadProduct
     */
    public function setPlgDynamicDownloadProduct($plg_dynamic_downloadproduct)
    {
        $this->plg_dynamic_downloadproduct = $plg_dynamic_downloadproduct;

        return $this;
    }

    /**
     * Get plg_dynamic_downloadproduct
     *
     * @return integer
     */
    public function getPlgDynamicDownloadProduct()
    {
        return $this->plg_dynamic_downloadproduct;
    }

    /**
     * Set Order
     *
     * @param \Eccube\Entity\Order $Order
     * @return DownloadProduct
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
     * @return DownloadProduct
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
     * Set plg_downloadproduct_type
     *
     * @param smallint
     * @return DownloadProduct
     */
    public function setPlgDownloadProductType($plg_downloadproduct_type)
    {
        $this->plg_downloadproduct_type = $plg_downloadproduct_type;

        return $this;
    }

    /**
     * Get plg_downloadproduct_type
     *
     * @return smallint
     */
    public function getPlgDownloadProductType()
    {
        return $this->plg_downloadproduct_type;
    }

    /**
     * Set plg_downloadproduct_action_name
     *
     * @param string
     * @return DownloadProduct
     */
    public function setPlgDownloadProductActionName($plg_downloadproduct_action_name)
    {
        $this->plg_downloadproduct_action_name = $plg_downloadproduct_action_name;

        return $this;
    }

    /**
     * Get plg_downloadproduct_action_name
     *
     * @return string
     */
    public function getPlgDownloadProductActionName()
    {
        return $this->plg_downloadproduct_action_name;
    }

    /**
     * Set DownloadProductInfo
     *
     * @param Eccube\Plugin\DownloadProduct\Entity\DownloadProductInfo
     * @return DownloadProduct
     */
    public function setDownloadProductInfo($DownloadProductInfo)
    {
        $this->DownloadProductInfo = $DownloadProductInfo;

        return $this;
    }

    /**
     * Get DownloadProductInfo
     *
     * @return \Plugin\DownloadProduct\Entity\DownloadProductInfo
     */
    public function getDownloadProductInfo()
    {
        return $this->DownloadProductInfo;
    }

    /**
     * Set create_date
     *
     * @param integer $create_date
     * @return DownloadProduct
     */
    public function setCreateDate($create_date)
    {
        $this->create_date = $create_date;

        return $this;
    }

    /**
     * Get create_date
     *
     * @return DownloadProduct
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set update_date
     *
     * @param integer $update_date
     * @return DownloadProduct
     */
    public function setUpdateDate($update_date)
    {
        $this->update_date = $update_date;

        return $this;
    }

    /**
     * Get update_date
     *
     * @return DownloadProduct
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }
}
