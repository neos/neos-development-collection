<?php

namespace Neos\Neos\FrontendRouting\CatchUpHook;

use Neos\ContentRepository\Core\Projection\CatchUpHookInterface;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\EventStore\Model\EventEnvelope;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\RedirectHandler\NeosAdapter\Service\NodeRedirectService;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Neos\FrontendRouting\Projection\DocumentUriPathFinder;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\CoverageNodeMoveMapping;
use Neos\Neos\FrontendRouting\Projection\DocumentNodeInfo;
use Neos\Neos\FrontendRouting\Exception\NodeNotFoundException;
use Neos\Neos\FrontendRouting\NodeAddress;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\Neos\Routing\Cache\RouteCacheFlusher;
use Neos\Flow\Mvc\Routing\RouterCachingService;

final class DocumentUriPathProjectionHookForRouteCacheFlushing implements CatchUpHookInterface
{
    /**
     * Runtime cache to collect tags until they can get flushed.
     * @var array<string, string>
     */
    private array $tagsToFlush = [];

    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly RouterCachingService $routerCachingService,
    ) {
    }

    public function onBeforeCatchUp(): void
    {
        // Nothing to do here
    }

    public function onBeforeEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        match ($eventInstance::class) {
//            NodeAggregateWasRemoved::class => $this->onBeforeNodeAggregateWasRemoved($eventInstance),
//            NodePropertiesWereSet::class => $this->onBeforeNodePropertiesWereSet($eventInstance),
//            NodeAggregateWasMoved::class => $this->onBeforeNodeAggregateWasMoved($eventInstance),
            NodeAggregateWasDisabled::class => $this->onBeforeNodeAggregateWasDisabled($eventInstance),
            default => null
        };

    }

    public function onAfterEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        match ($eventInstance::class) {
//            NodeAggregateWasRemoved::class => $this->onAfterNodeAggregateWasRemoved($eventInstance),
//            NodePropertiesWereSet::class => $this->onAfterNodePropertiesWereSet($eventInstance),
//            NodeAggregateWasMoved::class => $this->onAfterNodeAggregateWasMoved($eventInstance),
            NodeAggregateWasDisabled::class => $this->flushAllCollectedTags(),
            default => null
        };
    }

    public function onBeforeBatchCompleted(): void
    {
        // Nothing to do here
    }

    public function onAfterCatchUp(): void
    {
        // Nothing to do here
    }

    private function onBeforeNodeAggregateWasDisabled(NodeAggregateWasDisabled $event): void
    {
        if (!$this->isLiveContentStream($event->contentStreamId)) {
            return;
        }

        foreach ($event->affectedDimensionSpacePoints as $dimensionSpacePoint) {
            $node = $this->tryGetNode(fn() => $this->getState()->getByIdAndDimensionSpacePointHash(
                $event->nodeAggregateId,
                $dimensionSpacePoint->hash
            ));
            if ($node === null) {
                // Probably not a document node
                continue;
            }

            $this->collectTagsToFlush($node);
        }
    }

    private function collectTagsToFlush(DocumentNodeInfo $node): void
    {
        array_push($this->tagsToFlush, ...$node->getRouteTags()->getTags());
    }

    private function flushAllCollectedTags(): void
    {
        $this->routerCachingService->flushCachesByTags($this->tagsToFlush);
        $this->tagsToFlush = [];
    }

    private function getState(): DocumentUriPathFinder
    {
        return $this->contentRepository->projectionState(DocumentUriPathFinder::class);
    }

    private function isLiveContentStream(ContentStreamId $contentStreamId): bool
    {
        return $contentStreamId->equals($this->getState()->getLiveContentStreamId());
    }

    private function tryGetNode(\Closure $closure): ?DocumentNodeInfo
    {
        try {
            return $closure();
        } catch (NodeNotFoundException $_) {
            /** @noinspection BadExceptionsProcessingInspection */
            return null;
        }
    }
}
