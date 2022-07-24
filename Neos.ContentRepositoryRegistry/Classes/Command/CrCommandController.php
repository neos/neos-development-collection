<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Command;

use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HypergraphProjector;
use Neos\ContentRepository\Projection\Changes\ChangeProjection;
use Neos\ContentRepository\Projection\ContentStream\ContentStreamProjector;
use Neos\ContentRepository\Projection\NodeHiddenState\NodeHiddenStateProjector;
use Neos\ContentRepository\Projection\Workspace\WorkspaceProjection;
use Neos\ESCR\AssetUsage\Projector\AssetUsageProjector;
use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\EntityManagerInterface;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\GraphProjector;
use Neos\EventSourcing\EventListener\EventListenerInvoker;
use Neos\EventSourcing\EventStore\EventStoreFactory;
use Neos\EventSourcing\Projection\ProjectorInterface;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Neos\FrontendRouting\Projection\DocumentUriPathProjector;

class CrCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var EventStoreFactory
     */
    protected $eventStoreFactory;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;


    public function replayCommand(string $projectionName, string $contentRepositoryName = 'ContentRepository', int $maximumSequenceNumber = null, $quiet = false)
    {
        if ($projectionName === 'graph') {
            $projector = $this->objectManager->get(GraphProjector::class);
        } elseif ($projectionName === 'nodeHiddenState') {
            $projector = $this->objectManager->get(NodeHiddenStateProjector::class);
        } elseif ($projectionName === 'documentUriPath') {
            $projector = $this->objectManager->get(DocumentUriPathProjector::class);
        } elseif ($projectionName === 'change') {
            $projector = $this->objectManager->get(ChangeProjection::class);
        } elseif ($projectionName === 'workspace') {
            $projector = $this->objectManager->get(WorkspaceProjection::class);
        } elseif ($projectionName === 'assetUsage') {
            $projector = $this->objectManager->get(AssetUsageProjector::class);
        } elseif ($projectionName === 'contentStream') {
            $projector = $this->objectManager->get(ContentStreamProjector::class);
        //} elseif ($projectionName === 'hypergraph') {
        //    $projector = $this->objectManager->get(HypergraphProjector::class);
        } else {
            throw new \RuntimeException('Wrong $projectionName given. Supported are: graph, nodeHiddenState, documentUriPath, change, workspace, assetUsage, contentStream');
        }

        if (!$quiet) {
            $this->outputLine('Replaying events for projection "%s"%s ...', [$projectionName, ($maximumSequenceNumber ? ' until sequence number ' . $maximumSequenceNumber : '')]);
            $this->output->progressStart();
        }
        // TODO: right now we re-use the contentRepositoryName as eventStoreIdentifier - needs to be refactored after ContentRepository instance creation
        $eventListenerInvoker = $this->createEventListenerInvokerForProjection($projector, $contentRepositoryName);
        $eventsCount = 0;
        $eventListenerInvoker->onProgress(function () use (&$eventsCount, $quiet) {
            $eventsCount++;
            if (!$quiet) {
                $this->output->progressAdvance();
            }
        });
        if ($maximumSequenceNumber !== null) {
            $eventListenerInvoker = $eventListenerInvoker->withMaximumSequenceNumber($maximumSequenceNumber);
        }

        $projector->reset();
        $eventListenerInvoker->replay();

        if (!$quiet) {
            $this->output->progressFinish();
            $this->outputLine('Replayed %s events.', [$eventsCount]);
        }
    }

    /**
     * @param string $projectorClassName
     * @param string $eventStoreIdentifier
     * @return EventListenerInvoker
     */
    protected function createEventListenerInvokerForProjection(ProjectorInterface $projector, string $eventStoreIdentifier): EventListenerInvoker
    {
        $eventStore = $this->eventStoreFactory->create($eventStoreIdentifier);

        $connection = $this->objectManager->get(EntityManagerInterface::class)->getConnection();
        return new EventListenerInvoker($eventStore, $projector, $connection);
    }

}
