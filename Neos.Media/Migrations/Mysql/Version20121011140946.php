<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Migration that renames occurrences of "FLOW3" legacies inside the imageVariants property
 * of the Image entity (which is a serialized array collection)
 */
class Version20121011140946 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("UPDATE typo3_media_domain_model_image SET imagevariants = REPLACE(imagevariants, 's:31:\"\0*\0FLOW3_Persistence_Identifier\";', 's:32:\"\0*\0Persistence_Object_Identifier\";') WHERE imagevariants LIKE '%s:31:\"\0*\0FLOW3_Persistence_Identifier\";%'");
        $this->addSql("UPDATE typo3_media_domain_model_image SET imagevariants = REPLACE(imagevariants, 's:33:\"FLOW3_Persistence_RelatedEntities\";', 's:32:\"Flow_Persistence_RelatedEntities\";') WHERE imagevariants LIKE '%s:33:\"FLOW3_Persistence_RelatedEntities\";%'");
        $this->addSql("UPDATE typo3_media_domain_model_image SET imagevariants = REPLACE(imagevariants, 's:29:\"TYPO3\\\\FLOW3\\\\Resource\\\\Resource\";', 's:28:\"TYPO3\\\\Flow\\\\Resource\\\\Resource\";') WHERE imagevariants LIKE '%s:29:\"TYPO3\\\\\\\\FLOW3\\\\\\\\Resource\\\\\\\\Resource\";%'");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("UPDATE typo3_media_domain_model_image SET imagevariants = REPLACE(imagevariants, 's:28:\"TYPO3\\\\Flow\\\\Resource\\\\Resource\";', 's:29:\"TYPO3\\\\FLOW3\\\\Resource\\\\Resource\";') WHERE imagevariants LIKE '%s:28:\"TYPO3\\\\\\\\Flow\\\\\\\\Resource\\\\\\\\Resource\";%'");
        $this->addSql("UPDATE typo3_media_domain_model_image SET imagevariants = REPLACE(imagevariants, 's:32:\"Flow_Persistence_RelatedEntities\";', 's:33:\"FLOW3_Persistence_RelatedEntities\";') WHERE imagevariants LIKE '%s:32:\"Flow_Persistence_RelatedEntities\";%'");
        $this->addSql("UPDATE typo3_media_domain_model_image SET imagevariants = REPLACE(imagevariants, 's:32:\"\0*\0Persistence_Object_Identifier\";', 's:31:\"\0*\0FLOW3_Persistence_Identifier\";') WHERE imagevariants LIKE '%s:32:\"\0*\0Persistence_Object_Identifier\";%'");
    }
}
