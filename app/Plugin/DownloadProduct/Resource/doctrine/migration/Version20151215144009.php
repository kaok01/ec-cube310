<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Tools\SchemaTool;
use Eccube\Application;
use Plugin\DownloadProduct\Entity;

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
            'plg_downloadproduct',
            'plg_downloadproduct_customer',
            'plg_downloadproduct_order',
            'plg_downloadproduct_info',
            // 'plg_downloadproduct_product_rate',
            // 'plg_downloadproduct_snapshot',
            // 'plg_downloadproduct_customertag',
            // 'plg_downloadproduct_mtb_customertag',
        );

        $this->entities = array(
            'Plugin\DownloadProduct\Entity\DownloadProduct',
            'Plugin\DownloadProduct\Entity\DownloadProductCustomer',
            'Plugin\DownloadProduct\Entity\DownloadProductOrder',
            'Plugin\DownloadProduct\Entity\DownloadProductInfo',
            // 'Plugin\DownloadProduct\Entity\DownloadProductProductRate',
            // 'Plugin\DownloadProduct\Entity\DownloadProductSnapshot',
            // 'Plugin\DownloadProduct\Entity\DownloadProductCustomerTag',
            // 'Plugin\DownloadProduct\Entity\CustomerTag',
        );

        $this->sequences = array(
            'plg_downloadproduct_plg_downloadproduct_id_seq',
            'plg_downloadproduct_customer_plg_downloadproduct_customer_id_seq',
            'plg_downloadproduct_customer_plg_downloadproduct_order_id_seq',
            'plg_downloadproduct_info_plg_downloadproduct_info_id_seq',
            // 'plg_downloadproduct_product_rate_plg_downloadproduct_product_rate_id_seq',
            // 'plg_downloadproduct_snapshot_plg_downloadproduct_snapshot_id_seq',
            // 'plg_downloadproduct_customertag_plg_downloadproduct_customertag_id_seq',
            // 'plg_downloadproduct_mtb_customertag_customertag_id_seq',
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
        return;

        $app = Application::getInstance();
        $em = $app['orm.em'];
        $classes = array();
        foreach ($this->entities as $entity) {
            $classes[] = $em->getMetadataFactory()->getMetadataFor($entity);
        }

        $tool = new SchemaTool($em);
        $tool->createSchema($classes);

        // this up() migration is auto-generated, please modify it to your needs
        $this->createProductMap($schema);

    }

    /**
     * アンインストール時処理
     * @param Schema $schema
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    public function down(Schema $schema)
    {
        return;

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
        // this down() migration is auto-generated, please modify it to your needs
        $schema->dropTable('plg_downloadproduct_productmap');
        $schema->dropSequence('plg_downloadproduct_productmap_productmap_product_id_seq');

    }


    /**
     * 連携商品テーブル作成
     * @param Schema $schema
     */
    protected function createProductMap(Schema $schema)
    {
        $table = $schema->createTable("plg_downloadproduct_productmap");
        $table->addColumn('productmap_product_id', 'integer', array(
            'autoincrement' => true,
            'notnull' => true,
        ));

        $table->addColumn('product_id', 'integer', array(
            'notnull' => true,
            'unsigned' => false,
        ));

        $table->addColumn('refid', 'text', array(
            'notnull' => false,
        ));

        $table->addColumn('create_date', 'datetime', array(
            'notnull' => true,
            'unsigned' => false,
        ));

        $table->addColumn('update_date', 'datetime', array(
            'notnull' => true,
            'unsigned' => false,
        ));

        $table->setPrimaryKey(array('productmap_product_id'));
    }


}
