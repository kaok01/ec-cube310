<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Eccube\Common\Constant;

class Version20160902204400 extends AbstractMigration
{
    protected $entities = array(
        'Plugin\ContactMailConfig\Entity\ContactMailConfig',
    );

    public function up(Schema $schema)
    {
        if (false && version_compare(Constant::VERSION, '3.0.9', '>=')) {
            // 3,0,9 以降の場合, dcm.ymlの定義からテーブル生成を行う.
            $app = \Eccube\Application::getInstance();
            $meta = $this->getMetadata($app['orm.em']);
            $tool = new SchemaTool($app['orm.em']);
            $tool->createSchema($meta);
        } else {
            // 3.0.0 - 3.0.8
            $table = $schema->createTable("plg_contact_mail_config");
            $table->addColumn('id', 'integer')->setAutoincrement(true);
            $table->addColumn('mail_type', 'string', array('length' => 100, 'unique' => true))->setNotnull(true);
            $table->addColumn('mail_subject', 'text')->setNotnull(false);
            $table->addColumn('mail_from', 'text')->setNotnull(false);
            $table->addColumn('mail_cc', 'text')->setNotnull(false);
            $table->addColumn('mail_bcc', 'text')->setNotnull(false);
            $table->addColumn('reply_to', 'text')->setNotnull(false);
            $table->addColumn('return_path', 'text')->setNotnull(false);
            $table->setPrimaryKey(array('id'));
        }
    }

    public function down(Schema $schema)
    {
        if (false && version_compare(Constant::VERSION, '3.0.9', '>=')) {
            // 3,0,9 以降の場合, dcm.ymlの定義からテーブル/シーケンスの削除を行う
            $app = \Eccube\Application::getInstance();
            $meta = $this->getMetadata($app['orm.em']);

            $tool = new SchemaTool($app['orm.em']);
            $schemaFromMetadata = $tool->getSchemaFromMetadata($meta);

            // テーブル削除
            foreach ($schemaFromMetadata->getTables() as $table) {
                if ($schema->hasTable($table->getName())) {
                    $schema->dropTable($table->getName());
                }
            }

            // シーケンス削除
            foreach ($schemaFromMetadata->getSequences() as $sequence) {
                if ($schema->hasSequence($sequence->getName())) {
                    $schema->dropSequence($sequence->getName());
                }
            }
        } else {
            // 3.0.0 - 3.0.8
            $schema->dropTable('plg_contact_mail_config');
        }
    }

    protected function getMetadata(EntityManager $em)
    {
        $meta = array();
        foreach ($this->entities as $entity) {
            $meta[] = $em->getMetadataFactory()->getMetadataFor($entity);
        }

        return $meta;
    }
}