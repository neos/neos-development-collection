<?php
declare(strict_types=1);
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221501211812 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add removalAttachmentPoint to change projection';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('alter table neos_contentrepository_projection_change add removalAttachmentPoint varchar(255) null');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('alter table neos_contentrepository_projection_change drop column removalAttachmentPoint');
    }
}
