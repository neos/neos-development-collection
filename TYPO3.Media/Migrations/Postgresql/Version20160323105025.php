<?php

namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Migration to switch between static schema and json configuration for image adjustments
 */
class Version20160323105025 extends AbstractMigration
{
    /**
     * Transforms image adjustments from static schema to json configuration
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_adjustment_abstractimageadjustment ADD configuration LONGTEXT NOT NULL COMMENT '(DC2Type:flow_json_array)', ADD configurationhash VARCHAR(255) NOT NULL");

        $imageAdjustments = $this->connection->executeQuery("SELECT * FROM typo3_media_domain_model_adjustment_abstractimageadjustment")->fetchAll(\PDO::FETCH_ASSOC);
        if (count($imageAdjustments) > 0) {
            foreach ($imageAdjustments as $imageAdjustment) {
                $configuration = [];

                foreach ([
                             'allowupscaling' => 'allowUpScaling',
                             'height' => 'height',
                             'maximumheight' => 'maximumHeight',
                             'maximumwidth' => 'maximumWidth',
                             'minimumheight' => 'minimumHeight',
                             'minimumwidth' => 'minimumWidth',
                             'ratiomode' => 'ratioMode',
                             'width' => 'width',
                             'x' => 'x',
                             'y' => 'y',
                         ] as $column => $configurationKey) {
                    if (!is_null($imageAdjustment[$column])) {
                        $configuration[$configurationKey] = $imageAdjustment[$column];
                    }
                }

                $serializedConfiguration = json_encode($configuration, JSON_NUMERIC_CHECK + JSON_PRETTY_PRINT);
                $configurationHash = md5($serializedConfiguration);

                $this->addSql('UPDATE typo3_media_domain_model_adjustment_abstractimageadjustment SET configuration = \'' . $serializedConfiguration . '\', configurationhash = \'' . $configurationHash . '\' WHERE persistence_object_identifier = \'' . $imageAdjustment['persistence_object_identifier'] . '\'');
            }
        }
        $this->addSql("ALTER TABLE typo3_media_domain_model_adjustment_abstractimageadjustment DROP x, DROP y, DROP width, DROP height, DROP maximumwidth, DROP maximumheight, DROP minimumwidth, DROP minimumheight, DROP ratiomode, DROP allowupscaling");
    }

    /**
     * Transforms image adjustments from json configuration to static schema
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_adjustment_abstractimageadjustment ADD x INT DEFAULT NULL, ADD y INT DEFAULT NULL, ADD width INT DEFAULT NULL, ADD height INT DEFAULT NULL, ADD maximumwidth INT DEFAULT NULL, ADD maximumheight INT DEFAULT NULL, ADD minimumwidth INT DEFAULT NULL, ADD minimumheight INT DEFAULT NULL, ADD ratiomode VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, ADD allowupscaling TINYINT(1) DEFAULT NULL");

        $imageAdjustments = $this->connection->executeQuery("SELECT * FROM typo3_media_domain_model_adjustment_abstractimageadjustment")->fetchAll(\PDO::FETCH_ASSOC);
        $supportedConfigurationKeys = ['x', 'y', 'width', 'height', 'maximumWidth', 'maximumHeight', 'minimumWidth', 'minimumHeight', 'ratioMode', 'allowUpScaling'];
        if (count($imageAdjustments) > 0) {
            foreach ($imageAdjustments as $imageAdjustment) {
                $configuration = json_decode($imageAdjustment['configuration'], true);

                $sql = 'UPDATE typo3_media_domain_model_adjustment_abstractimageadjustment SET ';

                $parts = [];
                foreach ($supportedConfigurationKeys as $key) {
                    if (isset($configuration[$key])) {
                        $value = is_string($configuration[$key]) ? '\'' . $configuration[$key] . '\'' : $configuration[$key];
                        $parts[] = strtolower($key) . ' = ' . $value;
                    }
                }
                $sql .= implode(', ', $parts);

                $this->addSql($sql . ' WHERE persistence_object_identifier = \'' . $imageAdjustment['persistence_object_identifier'] . '\'');
            }
        }

        $this->addSql("ALTER TABLE typo3_media_domain_model_adjustment_abstractimageadjustment DROP configuration, DROP configurationhash");
    }
}
