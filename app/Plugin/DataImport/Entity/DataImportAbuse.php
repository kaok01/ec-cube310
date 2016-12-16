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
 * Class DataImportAbuse
 * @package Plugin\DataImportStatus\Entity
 */
class DataImportAbuse extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     */
    private $dataimport_abuse_id;
    /**
     * @var integer
     */
    private $order_id;

    /**
     * DataImportAbuse constructor.
     * @param int $order_id
     */
    public function __construct($order_id)
    {
        $this->order_id = $order_id;
    }

    /**
     * Set dataimport_status_id
     *
     * @param int $dataimport_abuse_id
     * @return DataImportStatus
     */
    public function setPlgDataImportAbuseId($dataimport_abuse_id)
    {
        $this->dataimport_abuse_id = $dataimport_abuse_id;

        return $this;
    }

    /**
     * Get dataimport_abuse_id
     *
     * @return integer
     */
    public function getPlgDataImportAbuseId()
    {
        return $this->dataimport_abuse_id;
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
}
