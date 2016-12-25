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
        $em->persist($KintoneTransAdmin);
        $em->flush();

        return true;
    }


}


