<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\Command\CrCommandController;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Cli\Response;
use Neos\Flow\Core\Bootstrap;
use Neos\Utility\ObjectAccess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Verifies that `cr:setup` followed by `cr:status` reports all subscriptions as OK and ACTIVE.
 */
final class CrSetupAndStatusCommandTest extends TestCase
{
    private CrCommandController $crController;

    private Response $response;

    private BufferedOutput $bufferedOutput;

    /** @before */
    public function injectController(): void
    {
        $this->crController = $this->getObject(CrCommandController::class);
        $this->response = new Response();
        $this->bufferedOutput = new BufferedOutput();

        ObjectAccess::setProperty($this->crController, 'response', $this->response, true);
        ObjectAccess::getProperty($this->crController, 'output', true)->setOutput($this->bufferedOutput);

        $contentRepositoryId = ContentRepositoryId::fromString('default');

        $connection = $this->getObject(Connection::class);
        $this->dropContentRepositoryTables($connection, $contentRepositoryId);
        $this->getObject(ContentRepositoryRegistry::class)->resetFactoryInstance($contentRepositoryId);
    }

    /** @test */
    public function setupThenStatusReportsAllSubscriptionsOk(): void
    {
        $this->crController->setupCommand(contentRepository: 'default');
        $this->bufferedOutput->fetch();

        $this->crController->statusCommand(contentRepository: 'default');

        self::assertSame(0, $this->response->getExitCode(), 'cr:status exited with a non-zero exit code');
        // strip_tags removes Flow's <success>, <b> etc. formatter tags that BufferedOutput does not resolve
        self::assertSame(
            <<<'OUTPUT'
            Event Store:
              Setup: OK
              Position: 0

            Subscriptions:
              contentGraph:
                Setup: OK
                Projection: ACTIVE at position 0
              Neos.Neos:DocumentUriPathProjection:
                Setup: OK
                Projection: ACTIVE at position 0
              Neos.Neos:PendingChangesProjection:
                Setup: OK
                Projection: ACTIVE at position 0
              Neos.Workspace.Ui:TrashBinProjection:
                Setup: OK
                Projection: ACTIVE at position 0

            OUTPUT,
            strip_tags($this->bufferedOutput->fetch())
        );
    }

    private function dropContentRepositoryTables(Connection $connection, ContentRepositoryId $contentRepositoryId): void
    {
        if ($connection->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            $connection->prepare('SET FOREIGN_KEY_CHECKS = 0;')->executeStatement();
        }

        $cascade = $connection->getDatabasePlatform() instanceof PostgreSQLPlatform ? ' CASCADE' : '';
        $prefix = sprintf('cr_%s_', $contentRepositoryId->value);

        foreach ($connection->createSchemaManager()->listTableNames() as $tableName) {
            if (!str_starts_with($tableName, $prefix)) {
                continue;
            }
            $connection->prepare('DROP TABLE ' . $connection->quoteIdentifier($tableName) . $cascade)->executeStatement();
        }

        if ($connection->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            $connection->prepare('SET FOREIGN_KEY_CHECKS = 1;')->executeStatement();
        }
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T
     */
    private function getObject(string $className): object
    {
        return Bootstrap::$staticObjectManager->get($className);
    }
}
