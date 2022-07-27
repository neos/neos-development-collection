<?php
declare(strict_types=1);
namespace Neos\ContentRepository\LegacyNodeMigration\Command;

/*
 * This file is part of the Neos.ContentRepositoryMigration package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\GraphProjector;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepository\Feature\ContentStreamRepository;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Projection\ContentStream\ContentStreamProjector;
use Neos\ContentRepository\Projection\Workspace\WorkspaceProjection;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\LegacyNodeMigration\Service\ClosureEventPublisher;
use Neos\ContentRepository\LegacyNodeMigration\Service\ContentRepositoryExportService;
use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;
use Neos\EventSourcing\EventListener\EventListenerInvoker;
use Neos\EventSourcing\EventStore\EventNormalizer;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\Storage\EventStorageInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

#[Flow\Scope('singleton')]
class ContentRepositoryMigrateCommandController extends CommandController
{
    /**
     * @Flow\InjectConfiguration(path="EventStore.stores.ContentRepository", package="Neos.EventSourcing")
     * @var array<string,mixed>
     */
    protected $eventStoreConfiguration;

    /**
     * @Flow\Inject(lazy=false)
     * @var EventNormalizer
     */
    protected $eventNormalizer;

    /**
     * @Flow\Inject(lazy=false)
     * @var GraphProjector
     */
    protected $graphProjector;

    /**
     * @Flow\Inject(lazy=false)
     * @var WorkspaceProjection
     */
    protected $workspaceProjector;

    /**
     * @Flow\Inject(lazy=false)
     * @var ContentStreamProjector
     */
    protected $contentStreamProjector;

    /**
     * @Flow\Inject(lazy=false)
     * @var ContentStreamRepository
     */
    protected $contentStreamRepository;

    /**
     * @Flow\Inject(lazy=false)
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject(lazy=false)
     * @var ContentDimensionZookeeper
     */
    protected $contentDimensionZookeeper;

    /**
     * @Flow\Inject(lazy=false)
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @Flow\Inject(lazy=false)
     * @var InterDimensionalVariationGraph
     */
    protected $interDimensionalVariationGraph;

    /**
     * @Flow\Inject(lazy=false)
     * @var RuntimeBlocker
     */
    protected $runtimeBlocker;

    /**
     * @Flow\Inject(lazy=false)
     * @var PropertyConverter
     */
    protected $propertyConverter;

    /**
     * @var Connection
     */
    private $dbal;

    public function injectEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->dbal = $entityManager->getConnection();
    }

    /**
     * Run a CR export
     */
    public function runCommand(): void
    {
        /** @var EventStorageInterface $eventStoreStorage */
        $eventStoreStorage = $this->objectManager->get(
            $this->eventStoreConfiguration['storage'],
            $this->eventStoreConfiguration['storageOptions'] ?? []
        );

        // We need to build an own $eventStore instance, because we need a custom EventPublisher.
        $eventPublisher = new ClosureEventPublisher();
        $eventStore = new EventStore($eventStoreStorage, $eventPublisher, $this->eventNormalizer);

        // We also need to build our own NodeAggregateCommandHandler (and dependencies),
        // so that the custom $eventStore is used there.
        $nodeAggregateEventPublisher = new NodeAggregateEventPublisher(
            // this is the custom EventStore we need here
            $eventStore
        );

        $nodeAggregateCommandHandler = new NodeAggregateCommandHandler(
            $this->contentStreamRepository,
            $this->nodeTypeManager,
            $this->contentDimensionZookeeper,
            $this->contentGraph,
            $this->interDimensionalVariationGraph,
            // the nodeAggregateEventPublisher contains the custom EventStore from above
            $nodeAggregateEventPublisher,
            $this->runtimeBlocker,
            $this->propertyConverter
        );
        $contentRepositoryExportService = new ContentRepositoryExportService($eventStore, $nodeAggregateCommandHandler);

        $contentStreamProjectorInvoker = new EventListenerInvoker(
            $eventStore,
            $this->contentStreamProjector,
            $this->dbal
        );
        $workspaceProjectorInvoker = new EventListenerInvoker($eventStore, $this->workspaceProjector, $this->dbal);
        $graphProjectorInvoker = new EventListenerInvoker($eventStore, $this->graphProjector, $this->dbal);

        $eventPublisher->setClosure(static function () use (
            $contentStreamProjectorInvoker,
            $workspaceProjectorInvoker,
            $graphProjectorInvoker
        ) {
            $contentStreamProjectorInvoker->catchUp();
            $workspaceProjectorInvoker->catchUp();
            $graphProjectorInvoker->catchUp();
        });

        $contentRepositoryExportService->reset();
        $this->graphProjector->assumeProjectorRunsSynchronously();
        $this->workspaceProjector->assumeProjectorRunsSynchronously();
        $this->contentStreamProjector->assumeProjectorRunsSynchronously();

        $contentRepositoryExportService->migrate();

        // TODO: re-enable asynchronous behavior; and trigger catchup of all projections. (e.g. ChangeProjector etc)
        $this->outputLine('');
        $this->outputLine('');
        $this->outputLine('!!!!! NOW, run ./flow cr:replay change');
        $this->outputLine('!!!!! NOW, run ./flow cr:replay nodeHiddenState');
        $this->outputLine('!!!!! NOW, run ./flow cr:replay documentUriPath');
        $this->outputLine('!!!!! NOW, run ./flow cr:replay assetUsage');

        // ChangeProjector catchup
    }
}
