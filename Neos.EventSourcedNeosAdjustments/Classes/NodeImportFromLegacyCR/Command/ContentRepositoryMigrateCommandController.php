<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\NodeImportFromLegacyCR\Command;

/*
 * This file is part of the Neos.ContentRepositoryMigration package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\GraphProjector;
use Neos\EventSourcedContentRepository\Domain\Projection\ContentStream\ContentStreamProjector;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceProjector;
use Neos\EventSourcedNeosAdjustments\NodeImportFromLegacyCR\Service\ClosureEventPublisher;
use Neos\EventSourcedNeosAdjustments\NodeImportFromLegacyCR\Service\ContentRepositoryExportService;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventListener\EventListenerInvoker;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\Storage\EventStorageInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

/**
 * @Flow\Scope("singleton")
 */
class ContentRepositoryMigrateCommandController extends CommandController
{

    /**
     * @Flow\InjectConfiguration(path="EventStore.stores.ContentRepository", package="Neos.EventSourcing")
     * @var array
     */
    protected $eventStoreConfiguration;

    /**
     * @Flow\Inject(lazy=false)
     * @var GraphProjector
     */
    protected $graphProjector;

    /**
     * @Flow\Inject(lazy=false)
     * @var WorkspaceProjector
     */
    protected $workspaceProjector;

    /**
     * @Flow\Inject(lazy=false)
     * @var ContentStreamProjector
     */
    protected $contentStreamProjector;

    /**
     * Run a CR export
     */
    public function runCommand()
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        /** @var EventStorageInterface $eventStoreStorage */
        $eventStoreStorage = $this->objectManager->get($this->eventStoreConfiguration['storage'], $this->eventStoreConfiguration['storageOptions'] ?? []);

        $eventPublisher = new ClosureEventPublisher();
        $eventStore = new EventStore($eventStoreStorage, $eventPublisher);
        $contentRepositoryExportService = new ContentRepositoryExportService($eventStore);

        $eventListenerInvoker = new EventListenerInvoker($eventStore);

        $eventPublisher->setClosure(function (DomainEvents $e) use ($eventListenerInvoker) {
            $eventListenerInvoker->catchUp($this->contentStreamProjector);
            $eventListenerInvoker->catchUp($this->workspaceProjector);
            $eventListenerInvoker->catchUp($this->graphProjector);
        });

        $contentRepositoryExportService->reset();
        $this->graphProjector->assumeProjectorRunsSynchronously();
        $this->workspaceProjector->assumeProjectorRunsSynchronously();
        $this->contentStreamProjector->assumeProjectorRunsSynchronously();

        $contentRepositoryExportService->migrate();

        // TODO: re-enable asynchronous behavior; and trigger catchup of all projections. (e.g. ChangeProjector etc)
        $this->outputLine('');
        $this->outputLine('');
        $this->outputLine('!!!!! NOW, run ./flow projection:replay change');
        $this->outputLine('!!!!! NOW, run ./flow projection:replay nodehiddenstate');

        // ChangeProjector catchup
    }
}
