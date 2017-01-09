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

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version201601071211 extends AbstractMigration
{

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {

        $this->createPlgSendSchedule($schema);
        $table = $schema->getTable('plg_send_schedule');
        $table->addIndex(
            array('send_id', 'creator_id')
        );

        // create Sequence MailMagazine Plug-in
        $this->createPlgplgSendScheduleScheduleIdSeq($schema);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

        // drop Sequence MailMagazine Plug-in
        $schema->dropTable('plg_send_schedule');

        // drop sequence.
        $schema->dropSequence('plg_send_schedule_schedule_id_seq');
    }

    /**
     * plg_send_historyテーブルの作成
     * @param Schema $schema
     */
    protected function createPlgSendSchedule(Schema $schema) {
        $table = $schema->createTable("plg_send_schedule");
        $table->addColumn('schedule_id', 'integer', array(
            'notnull' => true,
            'autoincrement' => true,            
        ));
        $table->addColumn('send_id', 'integer', array(
            'notnull' => false,
        ));        $table->addColumn('creator_id', 'integer', array(
            'notnull' => false,
        ));
        $table->addColumn('schedule_name', 'text', array(
            'notnull' => false,
        ));
        $table->addColumn('send_week', 'text', array(
            'notnull' => false,
        ));
        $table->addColumn('send_time', 'datetime', array(
            'unsigned' => false,
            'notnull' => false,
        ));
        $table->addColumn('send_start', 'datetime', array(
            'unsigned' => false,
            'notnull' => false,
        ));
        $table->addColumn('send_end', 'datetime', array(
            'unsigned' => false,
            'notnull' => false,
        ));
        $table->addColumn('sendrepeat_flg', 'smallint', array(
            'notnull' => true,
            'unsigned' => false,
            'default' => 0,
        ));
        $table->addColumn('enable_flg', 'smallint', array(
            'notnull' => true,
            'unsigned' => false,
            'default' => 1,
        ));
        $table->addColumn('del_flg', 'smallint', array(
            'notnull' => true,
            'unsigned' => false,
            'default' => 0,
        ));
        $table->addColumn('create_date', 'datetime', array(
            'notnull' => true,
            'unsigned' => false,
        ));
        $table->addColumn('update_date', 'datetime', array(
            'notnull' => true,
            'unsigned' => false,
        ));
        $table->setPrimaryKey(array('schedule_id'));

        // Indexの作成(creator_id)
        $table->addIndex(
            array('creator_id')
        );

    }

    /**
     * plg_send_schedule_schedule_id_seqの作成
     * @param Schema $schema
     */
    protected function createPlgplgSendScheduleScheduleIdSeq(Schema $schema) {
        $seq = $schema->createSequence("plg_send_schedule_schedule_id_seq");
    }


    function getMailMagazineCode()
    {
        $config = \Eccube\Application::alias('config');

        return "";
    }
}