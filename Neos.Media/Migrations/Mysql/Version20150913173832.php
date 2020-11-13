<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * A properties configuration and configurationHash for Thumbnails
 */
class Version20150913173832 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail ADD configuration LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)', ADD configurationhash VARCHAR(32) NOT NULL");

        $thumbnailResult = $this->connection->executeQuery('SELECT * FROM typo3_media_domain_model_thumbnail');
        while ($thumbnailInfo = $thumbnailResult->fetch(\PDO::FETCH_ASSOC)) {
            $configurationArray = array_filter([
                'maximumWidth' => $thumbnailInfo['maximumwidth'],
                'maximumHeight' => $thumbnailInfo['maximumheight'],
                'ratioMode' => $thumbnailInfo['ratiomode'],
                'allowUpScaling' => $thumbnailInfo['allowupscaling'] === 1 ? true : false
            ], function ($value) {
                return $value !== null;
            });
            ksort($configurationArray);
            $configuration = json_encode($configurationArray, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE);
            $configurationHash = md5(json_encode($configurationArray));
            $this->addSql(sprintf("UPDATE typo3_media_domain_model_thumbnail SET configuration = '%s', configurationhash = '%s' WHERE persistence_object_identifier = '%s'", $configuration, $configurationHash, $thumbnailInfo['persistence_object_identifier']));
        }

        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail DROP maximumwidth, DROP maximumheight, DROP ratiomode, DROP allowupscaling");
        $this->addSql("CREATE INDEX originalasset_configurationhash ON typo3_media_domain_model_thumbnail (originalasset, configurationhash)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail ADD maximumwidth INT DEFAULT NULL, ADD maximumheight INT DEFAULT NULL, ADD ratiomode VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, ADD allowupscaling TINYINT(1) DEFAULT NULL");

        $thumbnailResult = $this->connection->executeQuery('SELECT * FROM typo3_media_domain_model_thumbnail');
        while ($thumbnailInfo = $thumbnailResult->fetch(\PDO::FETCH_ASSOC)) {
            $configuration = json_decode($thumbnailInfo['configuration'], true);
            $maximumWidth = isset($configuration['maximumWidth']) ? (integer)$configuration['maximumWidth'] : null;
            $maximumWidth = $maximumWidth === 0 ? null : $maximumWidth;
            $maximumHeight = isset($configuration['maximumHeight']) ? (integer)$configuration['maximumHeight'] : null;
            $maximumHeight = $maximumHeight === 0 ? null : $maximumHeight;
            $ratioMode = isset($configuration['ratioMode']) ? $configuration['ratioMode'] : null;
            $allowUpScaling = isset($configuration['allowUpScaling']) ? $configuration['allowUpScaling'] : null;
            $allowUpScaling = $allowUpScaling ? 1 : 0;
            $types = [\PDO::PARAM_NULL];
            $this->addSql("UPDATE typo3_media_domain_model_thumbnail SET maximumwidth = ?, maximumheight = ?, ratiomode = ?, allowupscaling = ?  WHERE persistence_object_identifier = ?", [$maximumWidth, $maximumHeight, $ratioMode, $allowUpScaling, $thumbnailInfo['persistence_object_identifier']], $types);
        }

        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail DROP INDEX originalasset_configurationhash");
        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail DROP configuration, DROP configurationhash");
    }
}
