<?php
/*
* This file is part of EC-CUBE
*
* Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
* http://www.lockon.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\MailMagazine\Entity;

/**
 * SendHistory
 * Plugin MailMagazine
 */
class MailMagazineSendScheduleComplete extends \Eccube\Entity\AbstractEntity
{
    /**
    * @var integer
    */
    private $id;

    /**
    * @var integer
    */
    private $Schedule;

    /**
    * @var \DateTime
    */
    private $schedule_date;

    /**
    * @var \DateTime
    */
    private $create_date;

    /**
    * @var \DateTime
    */
    private $update_date;


    /**
    * Get id
    *
    * @return integer
    */
    public function getId()
    {
        return $this->id;
    }


    public function setSchedule(\Plugin\MailMagazine\Entity\MailMagazineSendSchedule $v=null)
    {
        $this->Schedule=$v;
        return $this;
    }
    public function getSchedule()
    {
        return $this->Schedule;
    }

    public function setScheduleDate($v)
    {
        $this->schedule_date=$v;
        return $this;
    }
    public function getScheduleDate()
    {
        return $this->schedule_date;
    }

    /**
    * Set create_date
    *
    * @param  \DateTime   $createDate
    * @return SendHistory
    */
    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
    * Get create_date
    *
    * @return \DateTime
    */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
    * Set update_date
    *
    * @param  \DateTime   $updateDate
    * @return SendHistory
    */
    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
    * Get update_date
    *
    * @return \DateTime
    */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

}
