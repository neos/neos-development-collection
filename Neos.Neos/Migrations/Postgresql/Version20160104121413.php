<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Neos\Neos\Utility\User as UserUtility;

/**
 * Set the Workspace "owner" field for all personal workspaces with special characters in the username
 */
class Version20160104121413 extends AbstractMigration
{

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $workspacesWithoutOwnerQuery = $this->connection->executeQuery("SELECT name FROM typo3_typo3cr_domain_model_workspace t0 WHERE t0.name LIKE 'user-%' AND t0.owner = ''");
        $workspacesWithoutOwner = $workspacesWithoutOwnerQuery->fetchAll(\PDO::FETCH_ASSOC);
        if ($workspacesWithoutOwner === []) {
            return;
        }

        $neosAccountQuery = $this->connection->executeQuery('SELECT t0.party_abstractparty, t1.accountidentifier FROM typo3_party_domain_model_abstractparty_accounts_join t0 JOIN typo3_flow_security_account t1 ON t0.flow_security_account = t1.persistence_object_identifier WHERE t1.authenticationprovidername = \'Typo3BackendProvider\'');
        while ($account = $neosAccountQuery->fetch(\PDO::FETCH_ASSOC)) {
            $normalizedUsername = UserUtility::slugifyUsername($account['accountidentifier']);

            foreach ($workspacesWithoutOwner as $workspaceWithoutOwner) {
                if ($workspaceWithoutOwner['name'] === 'user-' . $normalizedUsername) {
                    $this->addSql('UPDATE typo3_typo3cr_domain_model_workspace SET owner = \'' . $account['party_abstractparty'] . '\' WHERE name = \'user-' . $normalizedUsername . '\'');
                    continue 2;
                }
            }
        }
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");
        $this->addSql('UPDATE typo3_typo3cr_domain_model_workspace SET owner = NULL');
    }
}
