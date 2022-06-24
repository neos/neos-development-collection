<?php
declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration\Command;

/*
 * This file is part of the Neos.ContentRepository.LegacyNodeMigration package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\LegacyNodeMigration\Middleware\NeosLegacyEventMiddleware;
use Neos\ContentRepository\LegacyNodeMigration\NodeDataToEventsMigration;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ESCR\Export\Handler;
use Neos\ESCR\Export\Middleware\Context;
use Neos\ESCR\Export\Middleware\Event\NeosEventMiddleware;
use Neos\ESCR\Export\Middleware\MiddlewareInterface;
use Neos\ESCR\Export\ValueObject\Parameters;
use Neos\EventSourcing\EventStore\EventNormalizer;
use Neos\EventSourcing\EventStore\EventStoreFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Core\Booting\Scripts;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Utility\Environment;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class ContentRepositoryMigrateCommandController extends CommandController
{

    /**
     * @var array
     */
    #[Flow\InjectConfiguration(package: 'Neos.Flow')]
    protected array $flowSettings;

    public function __construct(
        private readonly Connection $connection,
        private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
        private readonly ContentDimensionZookeeper $contentDimensionZookeeper,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly PropertyMapper $propertyMapper,
        private readonly EventNormalizer $eventNormalizer,
        private readonly PropertyConverter $propertyConverter,
        private readonly Environment $environment,
        private readonly EventStoreFactory $eventStoreFactory,
        private readonly WorkspaceFinder $workspaceFinder,
    ) {
        parent::__construct();
    }

    /**
     * Run a CR export
     *
     * @param bool $quiet If set, only errors will be rendered
     * @param bool $assumeYes If set, prompts will be skipped
     */
    public function runCommand(bool $quiet = false, bool $assumeYes = false): void
    {
        $connection = $assumeYes ? $this->connection : $this->determineConnection();

        $filesystem = new Filesystem(new LocalFilesystemAdapter($this->environment->getPathToTemporaryDirectory()));
        $context = new Context($filesystem, Parameters::fromArray([]));

        // TODO: export/import Assets

        $nodeDataToEventsMigration = new NodeDataToEventsMigration($this->nodeTypeManager, $this->propertyMapper, $this->propertyConverter, $this->interDimensionalVariationGraph);
        $exporter = Handler::fromContextAndMiddlewares($context, new NeosLegacyEventMiddleware($connection, $this->eventNormalizer, $nodeDataToEventsMigration));
        if (!$quiet) {
            $this->outputLine('Exporting node data table rows');
            $this->registerProgressCallbacks($exporter);
        }
        $exporter->processExport();

        $importer = Handler::fromContextAndMiddlewares($context, new NeosEventMiddleware(true, true, $this->eventStoreFactory, $this->workspaceFinder, $this->eventNormalizer));
        if (!$quiet) {
            $this->outputLine('Importing events');
            $this->registerProgressCallbacks($importer);
        }

        // TODO: Fails if events table is not empty
        // TODO: create site

        $importer->processImport();

        $projections = ['graph', 'nodeHiddenState', 'documentUriPath', 'change', 'workspace', 'assetUsage', 'contentStream'];
        if (!$quiet) {
            $this->outputLine('Replaying projections');
            $this->output->progressStart(count($projections));
        }
        foreach ($projections as $projection) {
            Scripts::executeCommand('neos.contentrepositoryregistry:cr:replay', $this->flowSettings, false, ['projectionName' => $projection]);
            if (!$quiet) {
                $this->output->progressAdvance();
            }
        }
        if (!$quiet) {
            $this->output->progressFinish();
        }

        $this->outputLine('<success>Done</success>');
    }

    private function determineConnection(): Connection
    {
        $connectionParams = $this->connection->getParams();
        $useDefault = $this->output->askConfirmation(sprintf('Do you want to migrate nodes from the current database "%s@%s" (y/n)? ', $connectionParams['dbname'] ?? '?', $connectionParams['host'] ?? '?'));
        if ($useDefault) {
            return $this->connection;
        }
        $connectionParams['driver'] = $this->output->select(sprintf('Driver? [%s] ', $connectionParams['driver'] ?? ''), ['pdo_mysql', 'pdo_sqlite', 'pdo_pgsql'], $connectionParams['driver'] ?? null);
        $connectionParams['host'] = $this->output->ask(sprintf('Host? [%s] ',$connectionParams['host'] ?? ''), $connectionParams['host'] ?? null);
        $connectionParams['dbname'] = $this->output->ask(sprintf('DB name? [%s] ',$connectionParams['dbname'] ?? ''), $connectionParams['dbname'] ?? null);
        $connectionParams['user'] = $this->output->ask(sprintf('DB user? [%s] ',$connectionParams['user'] ?? ''), $connectionParams['user'] ?? null);
        $connectionParams['password'] = $this->output->askHiddenResponse(sprintf('DB password? [%s]', str_repeat('*', strlen($connectionParams['password'] ?? '')))) ?? $connectionParams['password'];
        return DriverManager::getConnection($connectionParams, new Configuration());
    }

    private function registerProgressCallbacks(Handler $handler): void
    {
        $output = $this->output->getOutput();
        $mainSection = $output instanceof ConsoleOutput ? $output->section() : $output;
        $progressBar = new ProgressBar($mainSection);
        $progressBar->setBarCharacter('<success>●</success>');
        $progressBar->setEmptyBarCharacter('<error>◌</error>');
        $progressBar->setProgressCharacter('<success>●</success>');
        $progressBar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% - %message%');
        $progressBar->setMessage('...');

        $handler->onStart(fn(int $numberOfSteps) => $progressBar->start($numberOfSteps));
        $handler->onStep(function(MiddlewareInterface $middleware) use ($progressBar) {
            $progressBar->advance();
            $progressBar->setMessage($middleware->getLabel());
        });
        if ($output instanceof ConsoleOutput) {
            $logSection = $output->section();
            $handler->onMessage(fn(string $message) => $logSection->writeln($message));
        }
    }
}
