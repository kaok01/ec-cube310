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

namespace Plugin\ContactMailConfig;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Entity\CartItem;
use Eccube\Entity\Category;
use Eccube\Entity\Order;
use Eccube\Entity\OrderDetail;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Eccube\Service\CartService;
use Eccube\Util\Str;
use Plugin\ContactMailConfig\Entity\ContactMailConfig;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\DomCrawler\Crawler;

class ContactMailConfigEvent
{


    /**
     * @var \Eccube\Application
     */
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

	public function onMailContact(EventArgs $event)
	{
		$app = $this->app;

		/** @var \Swift_Message $message */
		$message = $event->getArgument('message');

		/** @var ContactMailConfig $MailConfig */
		$MailConfig = $app['eccube.repository.plugin.ContactMailConfig']->findOneBy(array('type' => 'contact'));

		$subject = $MailConfig->getSubject();

		if (Str::isNotBlank(Str::trimAll($subject))){
			$message->setSubject(Str::trimAll($subject));
		}

		$validator = $app['validator'];
		$constraints = array(
			new \Symfony\Component\Validator\Constraints\Email(),
			new \Symfony\Component\Validator\Constraints\NotBlank()
		);


		$bcc = $MailConfig->getBcc();
		if($bcc){
			$addresses = explode(',', $bcc);
			$addresses = array_filter($addresses, function($e) use ($validator, $constraints){
				$error = $validator->validateValue(Str::trimAll($e), $constraints);
				return (count($error) === 0);
			});

			if(!empty($addresses)){
				$message->setBcc($addresses);
			}
		}
	}

}
