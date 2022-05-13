<?php
declare(strict_types=1);
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20181101122205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'update structure of Change table (removing NodeIdentifier; Adding NodeAggregateIdentifier/OriginDimensionSpacePoint)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on "mysql".'
        );
        $this->addSql('TRUNCATE neos_contentrepository_projection_change');
        $this->addSql('ALTER TABLE neos_contentrepository_projection_change DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE neos_contentrepository_projection_change DROP nodeIdentifier');
        $this->addSql('ALTER TABLE neos_contentrepository_projection_change ADD nodeAggregateIdentifier VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE neos_contentrepository_projection_change ADD originDimensionSpacePoint TEXT NULL');
        $this->addSql('ALTER TABLE neos_contentrepository_projection_change ADD originDimensionSpacePointHash VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE neos_contentrepository_projection_change ADD PRIMARY KEY (contentStreamIdentifier, nodeAggregateIdentifier, originDimensionSpacePointHash)');
    }

    public function down(Schema $schema): void
    {
        throw new \Exception("TODO unsupported");
    }
}
