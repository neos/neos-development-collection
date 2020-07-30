<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20181030124912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename dimensionspacepoint / dimensionspacepointhash to origindimensionspacepoint / origindimensionspacepointhash in node';
    }

    public function up(Schema $schema)
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );
        $this->addSql('ALTER TABLE neos_contentgraph_node CHANGE COLUMN dimensionspacepoint origindimensionspacepoint text DEFAULT NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_node CHANGE COLUMN dimensionspacepointhash origindimensionspacepointhash varchar(255) DEFAULT NULL');
    }

    public function down(Schema $schema)
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );

        $this->addSql('ALTER TABLE neos_contentgraph_node CHANGE COLUMN origindimensionspacepoint dimensionspacepoint text DEFAULT NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_node CHANGE COLUMN origindimensionspacepointhash dimensionspacepointhash varchar(255) DEFAULT NULL');
    }
}
