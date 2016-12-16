<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Tools\SchemaTool;
use Eccube\Application;
use Plugin\DataImport\Entity;

/**
 * Class Version20160428120000
 * @package DoctrineMigrations
 */
class Version20160428120000 extends AbstractMigration
{
    // テーブル名称
    const PLG_POINT_STATUS = 'plg_dataimport_status';
    const PLG_POINT_ABUSE = 'plg_dataimport_abuse';

    /**
     * インストール時処理
     * @param Schema $schema
     * @return bool
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        if ($schema->hasTable(self::PLG_POINT_STATUS)) {
            return true;
        }
        $app = Application::getInstance();
        $em = $app['orm.em'];
        $classes = array(
            $em->getMetadataFactory()->getMetadataFor('Plugin\DataImport\Entity\DataImportStatus'),
        );
        $tool = new SchemaTool($em);
        $tool->createSchema($classes);

        // 不適切な受注記録テーブル
        if ($schema->hasTable(self::PLG_POINT_ABUSE)) {
            return true;
        }
        $app = Application::getInstance();
        $em = $app['orm.em'];
        $classes = array(
            $em->getMetadataFactory()->getMetadataFor('Plugin\DataImport\Entity\DataImportAbuse'),
        );
        $tool = new SchemaTool($em);
        $tool->createSchema($classes);
    }

    /**
     * アンインストール時処理
     * @param Schema $schema
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    public function down(Schema $schema)
    {
        if ($schema->hasTable(self::PLG_POINT_STATUS)) {
            $schema->dropTable(self::PLG_POINT_STATUS);

            if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
                $schema->dropSequence('plg_dataimport_status_dataimport_status_id_seq');
            }
        }

        if ($schema->hasTable(self::PLG_POINT_ABUSE)) {
            $schema->dropTable(self::PLG_POINT_ABUSE);

            if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
                $schema->dropSequence('plg_dataimport_abuse_dataimport_abuse_id_seq');
            }
        }
    }
}
