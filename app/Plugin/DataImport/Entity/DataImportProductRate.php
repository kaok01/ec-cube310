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
 * Class DataImportProductRate
 * @package Plugin\DataImport\Entity
 */
class DataImportProductRate extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     */
    private $plg_dataimport_product_rate_id;
    /**
     * @var integer
     */
    private $product_id;
    /**
     * @var \Eccube\Entity\Product
     */
    private $Product;
    /**
     * @var integer
     */
    private $plg_dataimport_product_rate;
    /**
     * @var timestamp
     */
    private $create_date;
    /**
     * @var timstamp
     */
    private $update_date;

    /**
     * Get plg_dataimport_product_rate_id
     *
     * @param integer $plg_dataimport_product_rate_id
     *
     * @return DataImportProductRate
     */
    public function getPlgDataImportProductRateId()
    {
        return $this->plg_dataimport_product_rate_id;
    }

    /**
     * Set plg_dataimport_product_rate_id
     *
     * @param integer $plg_dataimport_product_rate_id
     * @return DataImportProductRate
     */
    public function setPlgDataImportProductRateId($plg_dataimport_product_rate_id)
    {
        $this->plg_dataimport_product_rate_id = $plg_dataimport_product_rate_id;

        return $this;
    }

    /**
     * Get product_id
     *
     * @param integer $product_id
     *
     * @return DataImportProductRate
     */
    public function getProductId()
    {
        return $this->product_id;
    }

    /**
     * Set product_id
     *
     * @param integer $product_id
     * @return DataImportProductRate
     */
    public function setProductId($product_id)
    {
        $this->product_id = $product_id;

        return $this;
    }

    /**
     * Get Product
     *
     * @param \Eccube\Entity\Product $product
     *
     * @return DataImportProductRate
     */
    public function getProduct()
    {
        return $this->Product;
    }

    /**
     * Set Product
     *
     * @param \Eccube\Entity\Product $product
     * @return DataImportProductRate
     */
    public function setProduct($product)
    {
        $this->Product = $product;

        return $this;
    }

    /**
     * Get plg_dataimport_product_rate
     *
     * @param integer $plg_dataimport_product_rate
     *
     * @return DataImportProductRate
     */
    public function getPlgDataImportProductRate()
    {
        return $this->plg_dataimport_product_rate;
    }

    /**
     * Set plg_dataimport_product_rate
     *
     * @param integer $plg_dataimport_product_rate
     * @return DataImportProductRate
     */
    public function setPlgDataImportProductRate($plg_dataimport_product_rate)
    {
        $this->plg_dataimport_product_rate = $plg_dataimport_product_rate;

        return $this;
    }

    /**
     * Set create_date
     *
     * @param timstamp $create_date
     * @return DataImportProduct
     */
    public function setCreateDate($create_date)
    {
        $this->create_date = $create_date;

        return $this;
    }

    /**
     * Get created_date
     *
     * @return timstamp $create_date
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set update_date
     *
     * @param timstamp $update_date
     * @return DataImportProduct
     */
    public function setUpdateDate($update_date)
    {
        $this->update_date = $update_date;

        return $this;
    }

    /**
     * Get update_date
     *
     * @return timstamp $update_date
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }
}
