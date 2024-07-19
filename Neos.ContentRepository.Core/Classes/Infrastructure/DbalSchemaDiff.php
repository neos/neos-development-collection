<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;

/**
 * @internal
 */
final class DbalSchemaDiff
{
    // This class only contains static members and should not be constructed
    private function __construct()
    {
    }

    /**
     * Compares the tables of the given $schema with existing tables for the given $connection
     * and returns an array of required CREATE and ALTER TABLE statements if they don't match
     *
     * @return array<string> Array of SQL statements that have to be executed in order to create/adjust the tables
     */
    public static function determineRequiredSqlStatements(Connection $connection, Schema $schema): array
    {
        $schemaManager = $connection->createSchemaManager();
        try {
            $platform = $connection->getDatabasePlatform();
        } catch (Exception $e) {
            throw new \RuntimeException(sprintf('Failed to retrieve Database platform: %s', $e->getMessage()), 1705679144, $e);
        }
        if ($platform === null) { // @phpstan-ignore-line This is not possible according to doc types, but there is no corresponding type hint in DBAL 2.x
            throw new \RuntimeException('Failed to retrieve Database platform', 1705679147);
        }
        $fromTableSchemas = [];
        foreach ($schema->getTables() as $tableSchema) {
            if ($schemaManager->tablesExist([$tableSchema->getName()])) {
                $fromTableSchemas[] = $schemaManager->listTableDetails($tableSchema->getName());
            }
        }
        $fromSchema = new Schema($fromTableSchemas, [], $schemaManager->createSchemaConfig());
        return (new Comparator())->compare($fromSchema, $schema)->toSql($platform);
    }
}
