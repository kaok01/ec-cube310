<?php

namespace Plugin\CustomerTag\Service;

use Eccube\Application;
use Eccube\Common\Constant;

class CustomerTagService
{
    /** @var \Eccube\Application */
    public $app;

    /** @var \Eccube\Entity\BaseInfo */
    public $BaseInfo;

    /**
     * コンストラクタ
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->BaseInfo = $app['eccube.repository.base_info']->get();
    }

    /**
     * @param $data
     * @return bool
     */
    public function createCustomerTagsByCsv(\Eccube\Entity\Customer $Customer,$csvdata) {
        $em = $this->app['orm.em'];
        $CustomerTagRepo = $this->app['eccube.plugin.customertag.repository.customer_customertag']
                        ->findBy(array('Customer'=>$Customer));
        if($CustomerTagRepo){
            foreach($CustomerTagRepo as $row){
                $em->remove($row);
            }
            $em->flush();
        }


        $csvdataarr = explode(",",$csvdata);
        if(count($csvdataarr)>0){
            foreach($csvdataarr as $cr){
                if(!empty($cr)){
                    $ctag = $this->app['eccube.plugin.customertag.repository.customertag']->findBy(array('name'=>$cr));
                    if($ctag){
                        $ctag = $ctag[0];


                    }else{
                        $ctag = new \Plugin\CustomerTag\Entity\CustomerTag();
                        $ctag->setName($cr);
                        $ctag->setCreateDate(new \Datetime());
                        $ctag->setUpdateDate(new \Datetime());
                        $ctag->setDelFlg(0);

                        $ctagRankMax = $this->app['eccube.plugin.customertag.repository.customertag']
                                            ->createQueryBuilder('m')
                                                    ->orderBy('m.rank', 'DESC')
                                                    ->setMaxResults(1)
                                                    ->getQuery()
                                                    ->getResult();
                        if($ctagRankMax){
                            $ctag->setRank($ctagRankMax[0]->getRank()+1);

                        }else{
                            $ctag->setRank(1);

                        }

                        $em->persist($ctag);
                        $em->flush();

     
                    }

                    $cctag = new \Plugin\CustomerTag\Entity\CustomerCustomerTag();
                    $cctag->setCustomer($Customer);
                    $cctag->setCustomerTag($ctag);
                    $cctag->setCreateDate(new \Datetime());

                    $em->persist($cctag);

                    $em->flush();

                }

            }
        }

        return true;
    }

    /**
     * @param $data
     * @return bool
     */
    public function getCustomerTagAll() {
        $em = $this->app['orm.em'];
        $CustomerTagRepo = $this->app['eccube.plugin.customertag.repository.customer_customertag']
                                    ->createQueryBuilder('m')
                                    ->orderBy('m.Customer', 'ASC')
                                    ->getQuery()
                                    ->getResult();
        if($CustomerTagRepo){
            $datas = array();
            $dataTags = array();

            foreach($CustomerTagRepo as $CustomerTag){
                $id = $CustomerTag->getCustomerTag()->getId();
                $ctag = $this->app['eccube.plugin.customertag.repository.customertag']
                                ->find($id);
                if($ctag){
                    $dataTags[$CustomerTag->getCustomer()->getId()] .= $ctag->getName().",";
                }

            }
            foreach($dataTags as $key=>$row){
                $datas[] = array('id'=>$key,
                                    'refid'=> $row
                                    );
            }

        }



        return $datas;
    }

}


