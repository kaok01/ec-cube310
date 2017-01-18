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
 * Class DownloadProductInfo
 * @package Plugin\DownloadProduct\Entity
 */
class DownloadProductInfo extends \Eccube\Entity\AbstractEntity
{
    const ADD_STATUS_FIX = 0;
    const ADD_STATUS_NON_FIX = 1;

    const POINT_ROUND_FLOOR = 0;
    const POINT_ROUND_CEIL = 1;
    const POINT_ROUND_ROUND = 2;

    const POINT_CALCULATE_SUBTRACTION = 0;
    const POINT_CALCULATE_NORMAL = 1;
    const POINT_CALCULATE_FRONT_COMMON = 3;
    const POINT_CALCULATE_FRONT_CART = 4;
    const POINT_CALCULATE_ADMIN_ORDER_NON_SUBTRACTION = 5;
    const POINT_CALCULATE_ADMIN_ORDER_SUBTRACTION = 6;

    /**
     * @var integer
     */
    private $plg_downloadproduct_info_id;
    /**
     * @var integer
     */
    private $plg_basic_downloadproduct_rate;
    /**
     * @var integer
     */
    private $plg_downloadproduct_conversion_rate;
    /**
     * @var smallint
     */
    private $plg_round_type;
    /**
     * @var smallint
     */
    private $plg_calculation_type;
    /**
     * @var smallint
     */
    private $plg_add_downloadproduct_status;
    /**
     * @var \Plugin\DownloadProduct\Entity\DownloadProductInfoAddStatus
     */
    //private $DownloadProductInfoAddStatus;
    /**
     * @var timestamp
     */
    private $create_date;
    /**
     * @var timestamp
     */
    private $update_date;

    /**
     * Set plg_downloadproduct_info_id
     *
     * @param integer $plg_downloadproduct_info_id
     * @return DownloadProductInfo
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
     * Set plg_basic_downloadproduct_rate
     *
     * @param integer $plg_basic_downloadproduct_rate
     * @return DownloadProductInfo
     */
    public function setPlgBasicDownloadProductRate($plg_basic_downloadproduct_rate)
    {
        $this->plg_basic_downloadproduct_rate = $plg_basic_downloadproduct_rate;

        return $this;
    }

    /**
     * Get plg_basic_downloadproduct_rate
     *
     * @return integer
     */
    public function getPlgBasicDownloadProductRate()
    {
        return $this->plg_basic_downloadproduct_rate;
    }

    /**
     * Set plg_downloadproduct_conversion_rate
     *
     * @param integer $plg_downloadproduct_conversion_rate
     * @return DownloadProductInfo
     */
    public function setPlgDownloadProductConversionRate($plg_downloadproduct_conversion_rate)
    {
        $this->plg_downloadproduct_conversion_rate = $plg_downloadproduct_conversion_rate;

        return $this;
    }

    /**
     * Get plg_downloadproduct_conversion_rate
     *
     * @return integer
     */
    public function getPlgDownloadProductConversionRate()
    {
        return $this->plg_downloadproduct_conversion_rate;
    }

    /**
     * Set plg_round_type
     *
     * @param smallint $plg_round_type
     * @return DownloadProductInfo
     */
    public function setPlgRoundType($plg_round_type)
    {
        $this->plg_round_type = $plg_round_type;

        return $this;
    }

    /**
     * Get plg_round_type
     *
     * @return smallint
     */
    public function getPlgRoundType()
    {
        return $this->plg_round_type;
    }

    /**
     * Set plg_calculation_type
     *
     * @param smallint $plg_calculation_type
     * @return DownloadProductInfo
     */
    public function setPlgCalculationType($plg_calculation_type)
    {
        $this->plg_calculation_type = $plg_calculation_type;

        return $this;
    }

    /**
     * Get plg_calculation_type
     *
     * @return smallint
     */
    public function getPlgCalculationType()
    {
        return $this->plg_calculation_type;
    }

    /**
     * Set plg_add_downloadproduct_status
     *
     * @param smallint $plg_add_downloadproduct_status
     * @return DownloadProductInfo
     */
    public function setPlgAddDownloadProductStatus($plg_add_downloadproduct_status)
    {
        $this->plg_add_downloadproduct_status = $plg_add_downloadproduct_status;

        return $this;
    }

    /**
     * Get plg_add_downloadproduct_status
     *
     * @return smallint
     */
    public function getPlgAddDownloadProductStatus()
    {
        return $this->plg_add_downloadproduct_status;
    }

    /**
     * Set create_date
     *
     * @param timestamp $create_date
     * @return DownloadProductInfo
     */
    public function setCreateDate($create_date)
    {
        $this->create_date = $create_date;

        return $this;
    }

    /**
     * Get create_date
     *
     * @return timstamp
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set update_date
     *
     * @param timestamp $update_date
     * @return DownloadProductInfo
     */
    public function setUpdateDate($update_date)
    {
        $this->update_date = $update_date;

        return $this;
    }

    /**
     * Get update_date
     *
     * @return timstamp
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }
}
