<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Core\Tests\Unit\Infrastructure;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaDiff;
use PHPUnit\Framework\TestCase;

class DbalSchemaDiffTest extends TestCase
{
    private Connection $connection;

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
    }

    public static function determineRequiredSqlStatements_dataProvider(): iterable
    {
        $tableSchema1 = new Table('some_table', [new Column('id', Type::getType(Types::INTEGER)), new Column('name', Type::getType(Types::STRING))]);
        $tableSchema2 = new Table('some_other_table', [new Column('id', Type::getType(Types::STRING)), new Column('name', Type::getType(Types::STRING))]);
        yield 'empty schema' => [new Schema(), [], 'expectedSqlStatements' => []];
        yield 'no existing tables' => [new Schema([$tableSchema1, $tableSchema2]), [], ['CREATE TABLE some_table (id INTEGER NOT NULL, name VARCHAR(255) NOT NULL)', 'CREATE TABLE some_other_table (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL)']];
        yield 'one existing' => [new Schema([$tableSchema1, $tableSchema2]), ['CREATE TABLE some_table (id INTEGER NOT NULL, name VARCHAR(255) NOT NULL)'], ['CREATE TABLE some_other_table (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL)']];
        yield 'one altered' => [new Schema([$tableSchema1]), ['CREATE TABLE some_table (id INTEGER NOT NULL)'], ['ALTER TABLE some_table ADD COLUMN name VARCHAR(255) NOT NULL']];
        yield 'one altered, one missing' => [new Schema([$tableSchema1, $tableSchema2]), ['CREATE TABLE some_table (id INTEGER NOT NULL)'], ['CREATE TABLE some_other_table (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL)', 'ALTER TABLE some_table ADD COLUMN name VARCHAR(255) NOT NULL']];
        yield 'one altered, one up to date' => [new Schema([$tableSchema1, $tableSchema2]), ['CREATE TABLE some_table (id INTEGER NOT NULL)', 'CREATE TABLE some_other_table (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL)'], ['ALTER TABLE some_table ADD COLUMN name VARCHAR(255) NOT NULL']];
        yield 'two altered' => [new Schema([$tableSchema1, $tableSchema2]), ['CREATE TABLE some_table (id INTEGER NOT NULL)', 'CREATE TABLE some_other_table (id VARCHAR(255) NOT NULL)'], ['ALTER TABLE some_table ADD COLUMN name VARCHAR(255) NOT NULL', 'ALTER TABLE some_other_table ADD COLUMN name VARCHAR(255) NOT NULL']];
    }

    /**
     * @test
     * @dataProvider determineRequiredSqlStatements_dataProvider
     */
    public function determineRequiredSqlStatements_tests(Schema $schema, array $preTestSqlStatements, array $expectedSqlStatements): void
    {
        foreach ($preTestSqlStatements as $statement) {
            $this->connection->executeStatement($statement);
        }
        $actualSqlStatements = DbalSchemaDiff::determineRequiredSqlStatements($this->connection, $schema);
        self::assertSame($expectedSqlStatements, $actualSqlStatements);
    }
}
