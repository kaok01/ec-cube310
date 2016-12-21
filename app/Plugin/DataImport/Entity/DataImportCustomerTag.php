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
class DataImportCustomerTag extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     */
    private $plg_dataimport_customertag_id;
    /**
     * @var integer
     */
    private $customer_id;
    /**
     * @var integer
     */
    private $customertag_id;
    /**
     * @var \Eccube\Entity\Customer
     */
    private $Customer;
    private $CustomerTag;

    /**
     * @var date
     */
    private $create_date;
    /**
     * @var date
     */
    private $update_date;


    /**
     * Set plg_dataimport_customer_id
     *
     * @param integer $plg_dataimport_customer_id
     * @return DataImportCustomer
     */
    public function setPlgDataImportCustomerTagId($plg_dataimport_customertag_id)
    {
        $this->plg_dataimport_customertag_id = $plg_dataimport_customertag_id;

        return $this;
    }

    /**
     * Get plg_dataimport_customer_id
     *
     * @return integer
     */
    public function getPlgDataImportCustomerTagId()
    {
        return $this->plg_dataimport_customertag_id;
    }



    /**
     * Set customer_id
     *
     * @param integer $customer_id
     * @return DataImportCustomer
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
     * Set Customer
     *
     * @param \Eccube\Entity\Customer $Customer
     * @return DataImportCustomer
     */
    public function setCustomer($Customer)
    {
        $this->Customer = $Customer;

        return $this;
    }

    /**
     * Get customer
     *
     * @return \Eccube\Entity\Customer
     */
    public function getCustomer()
    {
        return $this->Customer;
    }


    /**
     * Set customer_id
     *
     * @param integer $customer_id
     * @return DataImportCustomer
     */
    public function setCustomerTagId($customer_id)
    {
        $this->customertag_id = $customer_id;

        return $this;
    }

    /**
     * Get customer_id
     *
     * @return integer
     */
    public function getCustomerTagId()
    {
        return $this->customertag_id;
    }

    /**
     * Set Customer
     *
     * @param \Eccube\Entity\Customer $Customer
     * @return DataImportCustomer
     */
    public function setCustomerTag($CustomerTag)
    {
        $this->CustomerTag = $CustomerTag;

        return $this;
    }

    /**
     * Get customer
     *
     * @return \Eccube\Entity\Customer
     */
    public function getCustomerTag()
    {
        return $this->CustomerTag;
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
