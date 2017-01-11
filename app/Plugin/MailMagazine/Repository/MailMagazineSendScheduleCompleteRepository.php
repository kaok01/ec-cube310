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

namespace Plugin\MailMagazine\Repository;

use Doctrine\ORM\EntityRepository;

class MailMagazineSendScheduleCompleteRepository extends EntityRepository
{

    public function create(\Plugin\MailMagazine\Entity\MailMagazineSendScheduleComplete $sendSchedule)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            $em->persist($sendSchedule);
            $em->flush();

            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            throw $e;

        }

        return true;
    }

    public function update(\Plugin\MailMagazine\Entity\MailMagazineSendScheduleComplete $sendSchedule)
    {
        return $this->createSendSchedule($sendSchedule);
    }


    /**
    * phigical delete.
    * @return bool
    */
    public function delete(\Plugin\MailMagazine\Entity\MailMagazineSendScheduleComplete $MailMagazineSendSchedule)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {

            $em->remove($MailMagazineSendSchedule);
            $em->flush();

            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            throw $e;
            return false;
        }

        return true;
    }

    /**
    * 更新を行う.
    * @param \Plugin\MailMagazine\Entity\MailMagazineSendSchedule $MailMagazineSendSchedule
    * @return boolean
    */
    public function update(\Plugin\MailMagazine\Entity\MailMagazineSendSchedule $MailMagazineSendSchedule) {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            if(is_array($MailMagazineSendSchedule->getSendWeek())){
                $v=$MailMagazineSendSchedule->getSendWeek();
                $v = base64_encode(serialize($v));
                $MailMagazineSendSchedule->setSendWeek($v);

            }
            $em->persist($MailMagazineSendSchedule);
            $em->flush();

            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            throw $e;

            return false;
        }

        return true;

    }    
}
