<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Tools\SchemaTool;
use Eccube\Application;
use Plugin\DataImport\Entity;

/**
 * Class Version20151215144009
 * @package DoctrineMigrations
 */
class Version20151215144009 extends AbstractMigration
{
    protected $tables = array();

    protected $entities = array();

    protected $sequences = array();

    public function __construct()
    {
        $this->tables = array(
            'plg_dataimport',
            'plg_dataimport_customer',
            'plg_dataimport_info',
            'plg_dataimport_product_rate',
            'plg_dataimport_snapshot',
        );

        $this->entities = array(
            'Plugin\DataImport\Entity\DataImport',
            'Plugin\DataImport\Entity\DataImportCustomer',
            'Plugin\DataImport\Entity\DataImportInfo',
            'Plugin\DataImport\Entity\DataImportProductRate',
            'Plugin\DataImport\Entity\DataImportSnapshot',
        );

        $this->sequences = array(
            'plg_dataimport_plg_dataimport_id_seq',
            'plg_dataimport_customer_plg_dataimport_customer_id_seq',
            'plg_dataimport_info_plg_dataimport_info_id_seq',
            'plg_dataimport_product_rate_plg_dataimport_product_rate_id_seq',
            'plg_dataimport_snapshot_plg_dataimport_snapshot_id_seq',
        );
    }

    /**
     * インストール時処理
     * @param Schema $schema
     * @return bool
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    public function up(Schema $schema)
    {
        $app = Application::getInstance();
        $em = $app['orm.em'];
        $classes = array();
        foreach ($this->entities as $entity) {
            $classes[] = $em->getMetadataFactory()->getMetadataFor($entity);
        }

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
        foreach ($this->tables as $table) {
            if ($schema->hasTable($table)) {
                $schema->dropTable($table);
            }
        }
        foreach ($this->sequences as $sequence) {
            if ($schema->hasSequence($sequence)) {
                $schema->dropSequence($sequence);
            }
        }
    }
}
