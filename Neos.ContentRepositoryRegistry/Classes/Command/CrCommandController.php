<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Command;

use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HypergraphProjection;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjection;
use Neos\ContentRepository\Core\Projection\ContentStream\ContentStreamProjection;
use Neos\ContentRepository\Core\Projection\NodeHiddenState\NodeHiddenStateProjection;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceProjection;
use Neos\ContentRepository\Core\Service\ContentStreamPrunerFactory;
use Neos\ContentRepository\Core\Service\WorkspaceMaintenanceServiceFactory;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ESCR\AssetUsage\Projector\AssetUsageProjection;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Neos\FrontendRouting\Projection\DocumentUriPathProjection;
use Neos\Neos\PendingChangesProjection\ChangeProjection;

class CrCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;


    public function setupCommand(string $contentRepositoryIdentifier = 'default'): void
    {
        $contentRepositoryIdentifier = ContentRepositoryId::fromString($contentRepositoryIdentifier);

        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryIdentifier);
        $contentRepository->setUp();
        $this->outputLine('Content Repository tables "' . $contentRepositoryIdentifier . '" set up.');
    }

    public function replayAllCommand(string $contentRepositoryIdentifier = 'default', int $maximumSequenceNumber = null, bool $quiet = false): void
    {
        foreach (['graph', 'nodeHiddenState', 'documentUriPath', 'change', 'workspace', /* 'assetUsage',*/ 'contentStream', /* 'hypergraph' */] as $projectionName) {
            $this->replayCommand($projectionName, $contentRepositoryIdentifier, $maximumSequenceNumber, $quiet);
        }
    }

    public function replayCommand(string $projectionName, string $contentRepositoryIdentifier = 'default', int $maximumSequenceNumber = null, bool $quiet = false): void
    {
        $contentRepositoryIdentifier = ContentRepositoryId::fromString($contentRepositoryIdentifier);

        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryIdentifier);

        if ($projectionName === 'graph') {
            $projectionName = ContentGraphProjection::class;
        } elseif ($projectionName === 'nodeHiddenState') {
            $projectionName = NodeHiddenStateProjection::class;  // TODO
        } elseif ($projectionName === 'documentUriPath') {
            $projectionName = DocumentUriPathProjection::class;
        } elseif ($projectionName === 'change') {
            $projectionName = ChangeProjection::class;
        } elseif ($projectionName === 'workspace') {
            $projectionName = WorkspaceProjection::class;
        } elseif ($projectionName === 'assetUsage') {
            $projectionName = AssetUsageProjection::class;
        } elseif ($projectionName === 'contentStream') {
            $projectionName = ContentStreamProjection::class;
        } elseif ($projectionName === 'hypergraph') {
            $projectionName = HypergraphProjection::class; // TODO
        } else {
            throw new \RuntimeException('Wrong $projectionName given. Supported are: graph, nodeHiddenState, documentUriPath, change, workspace, assetUsage, contentStream');
        }

        if (!$quiet) {
            $this->outputLine('Replaying events for projection "%s"%s ...', [$projectionName, ($maximumSequenceNumber ? ' until sequence number ' . $maximumSequenceNumber : '')]);
            $this->output->progressStart();
        }

        // TODO: right now we re-use the contentRepositoryName as eventStoreIdentifier - needs to be refactored after ContentRepository instance creation
        //$eventListenerInvoker = $this->createEventListenerInvokerForProjection($projector, $contentRepositoryName);
        // TODO: ONPROGRESS HOOK??$eventListenerInvoker->onProgress(function () use (&$eventsCount, $quiet) {
        //    $eventsCount++;
        //    if (!$quiet) {
        //        $this->output->progressAdvance();
        //    }
        //});
        // TODO: MAX SEQ NUMBER
        //if ($maximumSequenceNumber !== null) {
        //    $eventListenerInvoker = $eventListenerInvoker->withMaximumSequenceNumber($maximumSequenceNumber);
        //}

        $contentRepository->resetProjectionState($projectionName);
        $contentRepository->catchUpProjection($projectionName);

        if (!$quiet) {
            $this->output->progressFinish();
            $this->outputLine('Replayed events.');
        }
    }

    public function pruneCommand(string $contentRepositoryIdentifier = 'default'): void
    {
        if (!$this->output->askConfirmation(sprintf("This will prune your content repository \"%s\". Are you sure to proceed? (y/n) ", $contentRepositoryIdentifier), false)) {
            return;
        }

        $contentRepositoryIdentifier = ContentRepositoryId::fromString($contentRepositoryIdentifier);

        $contentStreamPruner = $this->contentRepositoryRegistry->getService(
            $contentRepositoryIdentifier,
            new ContentStreamPrunerFactory()
        );
        $contentStreamPruner->pruneAll();

        $workspaceMaintenanceService = $this->contentRepositoryRegistry->getService(
            $contentRepositoryIdentifier,
            new WorkspaceMaintenanceServiceFactory()
        );
        $workspaceMaintenanceService->pruneAll();

        $this->replayAllCommand();
    }
}
