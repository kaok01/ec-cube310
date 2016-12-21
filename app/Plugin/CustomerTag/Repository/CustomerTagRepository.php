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

namespace Plugin\CustomerTag\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

/**
 * CustomerTag
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CustomerTagRepository extends EntityRepository
{
    /**
     * find all
     *
     * @return type
     */
    public function findAll()
    {

        $query = $this
            ->getEntityManager()
            ->createQuery('SELECT m FROM Plugin\CustomerTag\Entity\CustomerTag m ORDER BY m.rank DESC');
        $result = $query
            ->getResult(Query::HYDRATE_ARRAY);

        return $result;
    }

    /**
     * @param  \Plugin\CustomerTag\Entity\CustomerTag $CustomerTag
     * @return void
     */
    public function up(\Plugin\CustomerTag\Entity\CustomerTag $CustomerTag)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            $rank = $CustomerTag->getRank();

            $CustomerTagUp = $this->createQueryBuilder('m')
                ->where('m.rank > :rank')
                ->setParameter('rank', $rank)
                ->orderBy('m.rank', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleResult();

            $CustomerTag->setRank($CustomerTagUp->getRank());
            $CustomerTagUp->setRank($rank);

            $em->persist($CustomerTag);
            $em->persist($CustomerTagUp);

            $em->flush();
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();

            return false;
        }

        return true;
	}

    /**
     * @param  \Plugin\CustomerTag\Entity\CustomerTag $CustomerTag
     * @return bool
     */
    public function down(\Plugin\CustomerTag\Entity\CustomerTag $CustomerTag)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            $rank = $CustomerTag->getRank();

            $CustomerTagDown = $this->createQueryBuilder('m')
                ->where('m.rank < :rank ')
                ->setParameter('rank', $rank)
                ->orderBy('m.rank', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleResult();

            $CustomerTag->setRank($CustomerTagDown->getRank());
            $CustomerTagDown->setRank($rank);

            $em->persist($CustomerTag);
            $em->persist($CustomerTagDown);
            $em->flush();

            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();

            return false;
        }

        return true;
    }

    /**
     * @param  \Plugin\CustomerTag\Entity\CustomerTag $CustomerTag
     * @return bool
     */
    public function save(\Plugin\CustomerTag\Entity\CustomerTag $CustomerTag)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            if (!$CustomerTag->getId()) {
                $rank = $this->createQueryBuilder('m')
                    ->select('MAX(m.rank)')
                    ->getQuery()
                    ->getSingleScalarResult();
                if (!$rank) {
                    $rank = 0;
                }
                $CustomerTag->setRank($rank + 1);
                $CustomerTag->setDelFlg(0);

                $em->createQueryBuilder()
                    ->update('Plugin\CustomerTag\Entity\CustomerTag', 'm')
                    ->set('m.rank', 'm.rank + 1')
                    ->where('m.rank > :rank')
                    ->setParameter('rank', $rank)
                    ->getQuery()
                    ->execute();
            }

            $em->persist($CustomerTag);
            $em->flush();

            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();

            return false;
        }
        return true;
    }

    /**
     * @param  \Plugin\CustomerTag\Entity\CustomerTag $CustomerTag
     * @return bool
     */
    public function delete(\Plugin\CustomerTag\Entity\CustomerTag $CustomerTag)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            $CustomerTag->setDelFlg(1);
            $em->persist($CustomerTag);
            $em->flush();

            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();

            return false;
        }

        return true;
    }

}
