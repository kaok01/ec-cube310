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
class MailMagazineSendSchedule extends \Eccube\Entity\AbstractEntity
{
    /**
    * @var integer
    */
    private $id;

    /**
    * @var integer
    */
    private $schedule_name;

    /**
    * @var string
    */
    private $send_week;

    /**
    * @var \DateTime
    */
    private $send_time;

    /**
    * @var \DateTime
    */
    private $send_start;

    /**
    * @var \DateTime
    */
    private $send_end;

    /**
    * @var integer
    */
    private $sendrepeat_flg;

    /**
    * @var integer
    */
    private $enable_flg;

    /**
    * @var integer
    */
    private $del_flg;

    /**
    * @var \DateTime
    */
    private $create_date;

    /**
    * @var \DateTime
    */
    private $update_date;

    /**
    * @var \Eccube\Entity\Member
    */
    private $Creator;

    /**
    * @var \Plugin\MailMagazine\Entity\MailMagazineSendHistory
    */
    private $SendHistory;

    /**
    * Get id
    *
    * @return integer
    */
    public function getId()
    {
        return $this->id;
    }


    public function setScheduleName($v)
    {
        $this->schedule_name=$v;
        return $this;
    }
    public function getScheduleName()
    {
        return $this->schedule_name;
    }

    public function setSendWeek($v)
    {
        /*
        if(is_array(unserialize(base64_decode($v)))
            ){
        $this->send_week=unserialize(base64_decode($v));

        }else{
        $this->send_week=$v;

        }
        */
        $this->send_week=$v;
        
        return $this;
    }
    public function getSendWeek()
    {
        /*
        if(is_array(unserialize(base64_decode($this->send_week)))
            ){
        return unserialize(base64_decode($this->send_week));


        }else{
        return $this->send_week;

        }
        */

        return $this->send_week;

    }

    public function setSendTime($v)
    {
        $this->send_time=$v;
        return $this;
    }
    public function getSendTime()
    {
        return $this->send_time;
    }
    public function setSendStart($v)
    {
        $this->send_start=$v;
        return $this;
    }
    public function getSendStart()
    {
        return $this->send_start;
    }
    public function setSendEnd($v)
    {
        $this->send_end=$v;
        return $this;
    }
    public function getSendEnd()
    {
        return $this->send_end;
    }
    public function setSendRepeatFlg($v)
    {
        $this->sendrepeat_flg=$v;
        return $this;
    }
    public function getSendRepeatFlg()
    {
        return $this->sendrepeat_flg;
    }

    public function setEnableFlg($v)
    {
        $this->enable_flg=$v;
        return $this;
    }
    public function getEnableFlg()
    {
        return $this->enable_flg;
    }

    public function setDelFlg($v)
    {

        $this->del_flg=$v;
        return $this;
    }
    public function getDelFlg()
    {
        return $this->del_flg;
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

    /**
    * Set Creator
    *
    * @param  \Eccube\Entity\Member $creator
    * @return SendHistory
    */
    public function setCreator(\Eccube\Entity\Member $creator = null)
    {
        $this->Creator = $creator;

        return $this;
    }

    /**
    * Get Creator
    *
    * @return \Eccube\Entity\Member
    */
    public function getCreator()
    {
        return $this->Creator;
    }

    /**
    * Set Creator
    *
    * @param  \Eccube\Entity\Member $creator
    * @return SendHistory
    */
    public function setSendHistory(\Plugin\MailMagazine\Entity\MailMagazineSendHistory $sendhistory = null)
    {
        $this->SendHistory = $sendhistory;

        return $this;
    }

    /**
    * Get Creator
    *
    * @return \Eccube\Entity\Member
    */
    public function getSendHistory()
    {
        return $this->SendHistory;
    }    
}
