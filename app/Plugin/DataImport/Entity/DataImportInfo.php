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
 * Class DataImportInfo
 * @package Plugin\DataImport\Entity
 */
class DataImportInfo extends \Eccube\Entity\AbstractEntity
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
    private $plg_dataimport_info_id;
    /**
     * @var integer
     */
    private $plg_basic_dataimport_rate;
    /**
     * @var integer
     */
    private $plg_dataimport_conversion_rate;
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
    private $plg_add_dataimport_status;
    /**
     * @var \Plugin\DataImport\Entity\DataImportInfoAddStatus
     */
    //private $DataImportInfoAddStatus;
    /**
     * @var timestamp
     */
    private $create_date;
    /**
     * @var timestamp
     */
    private $update_date;

    /**
     * Set plg_dataimport_info_id
     *
     * @param integer $plg_dataimport_info_id
     * @return DataImportInfo
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
     * Set plg_basic_dataimport_rate
     *
     * @param integer $plg_basic_dataimport_rate
     * @return DataImportInfo
     */
    public function setPlgBasicDataImportRate($plg_basic_dataimport_rate)
    {
        $this->plg_basic_dataimport_rate = $plg_basic_dataimport_rate;

        return $this;
    }

    /**
     * Get plg_basic_dataimport_rate
     *
     * @return integer
     */
    public function getPlgBasicDataImportRate()
    {
        return $this->plg_basic_dataimport_rate;
    }

    /**
     * Set plg_dataimport_conversion_rate
     *
     * @param integer $plg_dataimport_conversion_rate
     * @return DataImportInfo
     */
    public function setPlgDataImportConversionRate($plg_dataimport_conversion_rate)
    {
        $this->plg_dataimport_conversion_rate = $plg_dataimport_conversion_rate;

        return $this;
    }

    /**
     * Get plg_dataimport_conversion_rate
     *
     * @return integer
     */
    public function getPlgDataImportConversionRate()
    {
        return $this->plg_dataimport_conversion_rate;
    }

    /**
     * Set plg_round_type
     *
     * @param smallint $plg_round_type
     * @return DataImportInfo
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
     * @return DataImportInfo
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
     * Set plg_add_dataimport_status
     *
     * @param smallint $plg_add_dataimport_status
     * @return DataImportInfo
     */
    public function setPlgAddDataImportStatus($plg_add_dataimport_status)
    {
        $this->plg_add_dataimport_status = $plg_add_dataimport_status;

        return $this;
    }

    /**
     * Get plg_add_dataimport_status
     *
     * @return smallint
     */
    public function getPlgAddDataImportStatus()
    {
        return $this->plg_add_dataimport_status;
    }

    /**
     * Set create_date
     *
     * @param timestamp $create_date
     * @return DataImportInfo
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
     * @return DataImportInfo
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
