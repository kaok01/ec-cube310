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

namespace Plugin\DataImport\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Plugin\DataImport\Entity\DataImportInfo;

/**
 * Class DataImportInfoRepository
 * @package Plugin\DataImport\Repository
 */
class DataImportInfoRepository extends EntityRepository
{
    /**
     * DataImportInfoRepository constructor.
     * @param EntityManager $em
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class
     */
    public function __construct(EntityManager $em, \Doctrine\ORM\Mapping\ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    /**
     * ポイント基本情報を保存
     *  - 受注ステータス・ユーザー設定不可項目をダミーとして追加
     * @param \Plugin\DataImport\Entity\DataImportInfo $dataimportInfo
     * @return bool
     * @throws NoResultException
     */
    public function save(\Plugin\DataImport\Entity\DataImportInfo $src)
    {
        $dist = new DataImportInfo();
        $dist->copyProperties($src, array('plg_dataimport_info_id'));
        $dist->setCreateDate(new \DateTime());
        $dist->setUpdateDate(new \DateTime());
        $em = $this->getEntityManager();
        $em->persist($dist);
        $em->flush($dist);
    }

    /**
     * ポイント機能基本設定情報で最後に設定した内容を取得
     * @return mixed|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLastInsertData()
    {
        try {
            $qb = $this->createQueryBuilder('pi')
                ->orderBy('pi.plg_dataimport_info_id', 'DESC')
                ->setMaxResults(1);

            $DataImportInfo = $qb->getQuery()->getSingleResult();

            return $DataImportInfo;
        } catch (NoResultException $e) {
            return null;
        }
    }
}
