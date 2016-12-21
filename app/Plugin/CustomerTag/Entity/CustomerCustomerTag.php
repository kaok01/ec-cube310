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

namespace Plugin\CustomerTag\Entity;

use Eccube\Util\EntityUtil;

class CustomerCustomerTag extends \Eccube\Entity\AbstractEntity
{
    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getMethod();
    }

    private $id;
    private $customertag_url;
    private $del_flg;
    private $create_date;
    private $update_date;
    private $CustomerTag;

    public function __construct()
    {
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setCustomerTagUrl($customertag_url)
    {
        $this->customertag_url = $customertag_url;
        return $this;
    }

    public function getCustomerTagUrl()
    {
        return $this->customertag_url;
    }

    public function setDelFlg($delFlg)
    {
        $this->del_flg = $delFlg;

        return $this;
    }

    public function getDelFlg()
    {
        return $this->del_flg;
    }

    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    public function getCreateDate()
    {
        return $this->create_date;
    }

    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    public function getUpdateDate()
    {
        return $this->update_date;
    }
    
    public function setCustomerTag(CustomerTag $customertag)
    {
        $this->CustomerTag = $customertag;

        return $this;
    }

    public function getCustomerTag()
    {
        if (EntityUtil::isEmpty($this->CustomerTag)) {
            return null;
        }

        return $this->CustomerTag;
    }

}
