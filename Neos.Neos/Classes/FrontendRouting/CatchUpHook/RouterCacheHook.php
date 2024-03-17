<?php

namespace Neos\Neos\FrontendRouting\CatchUpHook;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\CoverageNodeMoveMapping;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\Projection\CatchUpHookInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\EventStore\Model\EventEnvelope;
use Neos\Flow\Mvc\Routing\RouterCachingService;
use Neos\Neos\FrontendRouting\Exception\NodeNotFoundException;
use Neos\Neos\FrontendRouting\Projection\DocumentNodeInfo;
use Neos\Neos\FrontendRouting\Projection\DocumentUriPathFinder;

final class RouterCacheHook implements CatchUpHookInterface
{
    /**
     * Runtime cache to collect tags until they can get flushed.
     * @var string[]
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
            NodeAggregateWasRemoved::class => $this->onBeforeNodeAggregateWasRemoved($eventInstance),
            NodePropertiesWereSet::class => $this->onBeforeNodePropertiesWereSet($eventInstance),
            NodeAggregateWasMoved::class => $this->onBeforeNodeAggregateWasMoved($eventInstance),
            SubtreeWasTagged::class => $this->onBeforeSubtreeWasTagged($eventInstance),
            default => null
        };
    }

    public function onAfterEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        match ($eventInstance::class) {
            NodeAggregateWasRemoved::class => $this->flushAllCollectedTags(),
            NodePropertiesWereSet::class => $this->flushAllCollectedTags(),
            NodeAggregateWasMoved::class => $this->flushAllCollectedTags(),
            SubtreeWasTagged::class => $this->flushAllCollectedTags(),
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

    private function onBeforeSubtreeWasTagged(SubtreeWasTagged $event): void
    {
        if (!$this->getState()->isLiveContentStream($event->contentStreamId)) {
            return;
        }

        foreach ($event->affectedDimensionSpacePoints as $dimensionSpacePoint) {
            $node = $this->findDocumentNodeInfoByIdAndDimensionSpacePoint($event->nodeAggregateId, $dimensionSpacePoint);
            if ($node === null) {
                // Probably not a document node
                continue;
            }

            $this->collectTagsToFlush($node);

            $descendantsOfNode = $this->getState()->getDescendantsOfNode($node);
            array_map($this->collectTagsToFlush(...), iterator_to_array($descendantsOfNode));
        }
    }

    private function onBeforeNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        if (!$this->getState()->isLiveContentStream($event->contentStreamId)) {
            return;
        }

        foreach ($event->affectedCoveredDimensionSpacePoints as $dimensionSpacePoint) {
            $node = $this->findDocumentNodeInfoByIdAndDimensionSpacePoint($event->nodeAggregateId, $dimensionSpacePoint);
            if ($node === null) {
                // Probably not a document node
                continue;
            }

            $this->collectTagsToFlush($node);

            $descendantsOfNode = $this->getState()->getDescendantsOfNode($node);
            array_map($this->collectTagsToFlush(...), iterator_to_array($descendantsOfNode));
        }
    }

    private function onBeforeNodePropertiesWereSet(NodePropertiesWereSet $event): void
    {
        if (!$this->getState()->isLiveContentStream($event->contentStreamId)) {
            return;
        }

        $newPropertyValues = $event->propertyValues->getPlainValues();
        if (!isset($newPropertyValues['uriPathSegment'])) {
            return;
        }

        foreach ($event->affectedDimensionSpacePoints as $affectedDimensionSpacePoint) {
            $node = $this->findDocumentNodeInfoByIdAndDimensionSpacePoint($event->nodeAggregateId, $affectedDimensionSpacePoint);
            if ($node === null) {
                // probably not a document node
                continue;
            }

            $this->collectTagsToFlush($node);

            $descendantsOfNode = $this->getState()->getDescendantsOfNode($node);
            array_map($this->collectTagsToFlush(...), iterator_to_array($descendantsOfNode));
        }
    }

    private function onBeforeNodeAggregateWasMoved(NodeAggregateWasMoved $event): void
    {
        if (!$this->getState()->isLiveContentStream($event->contentStreamId)) {
            return;
        }

        foreach ($event->nodeMoveMappings as $moveMapping) {
            /* @var \Neos\ContentRepository\Core\Feature\NodeMove\Dto\OriginNodeMoveMapping $moveMapping */
            foreach ($moveMapping->newLocations as $newLocation) {
                /* @var $newLocation CoverageNodeMoveMapping */
                $node = $this->findDocumentNodeInfoByIdAndDimensionSpacePoint($event->nodeAggregateId, $newLocation->coveredDimensionSpacePoint);
                if (!$node) {
                    // node probably no document node, skip
                    continue;
                }

                $this->collectTagsToFlush($node);

                $descendantsOfNode = $this->getState()->getDescendantsOfNode($node);
                array_map($this->collectTagsToFlush(...), iterator_to_array($descendantsOfNode));
            }
        }
    }

    private function collectTagsToFlush(DocumentNodeInfo $node): void
    {
        array_push($this->tagsToFlush, ...$node->getRouteTags()->getTags());
    }

    private function flushAllCollectedTags(): void
    {
        if ($this->tagsToFlush === []) {
            return;
        }

        $this->routerCachingService->flushCachesByTags($this->tagsToFlush);
        $this->tagsToFlush = [];
    }

    private function getState(): DocumentUriPathFinder
    {
        return $this->contentRepository->projectionState(DocumentUriPathFinder::class);
    }

    private function findDocumentNodeInfoByIdAndDimensionSpacePoint(NodeAggregateId $nodeAggregateId, DimensionSpacePoint $dimensionSpacePoint): ?DocumentNodeInfo
    {
        try {
            return $this->getState()->getByIdAndDimensionSpacePointHash(
                $nodeAggregateId,
                $dimensionSpacePoint->hash
            );
        } catch (NodeNotFoundException $_) {
            /** @noinspection BadExceptionsProcessingInspection */
            return null;
        }
    }
}
