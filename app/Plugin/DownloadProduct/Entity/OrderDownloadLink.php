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
 */
class OrderDownloadLink extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var string
     */
    private $plg_downloadproduct_order_id;

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
     * Set plg_downloadproduct_order_id
     *
     * @param integer $plg_downloadproduct_order_id
     * @return DownloadProductOrder
     */
    public function setPlgDownloadProductOrderId($plg_downloadproduct_order_id)
    {
        $this->plg_downloadproduct_order_id = $plg_downloadproduct_order_id;

        return $this;
    }

    /**
     * Get plg_downloadproduct_customer_id
     *
     * @return integer
     */
    public function getPlgDownloadProductOrderId()
    {
        return $this->plg_downloadproduct_order_id;
    }

    /**
     * Set Order
     *
     * @param \Eccube\Entity\Order $Order
     * @return DownloadProductCustomer
     */
    public function setOrder($Order)
    {
        $this->Order = $Order;

        return $this;
    }

    /**
     * Get customer
     *
     * @return \Eccube\Entity\Order
     */
    public function getOrder()
    {
        return $this->Order;
    }

    /**
     * Set create_date
     *
     * @param date $create_date
     * @return DownloadProductCustomer
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
     * @return DownloadProductCustomer
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
