<?php

declare(strict_types=1);

namespace Neos\ContentRepository\StructureAdjustment;

use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\EventStore\DecoratedEvent;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamId;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngine;
use Neos\ContentRepository\StructureAdjustment\Adjustment\DimensionAdjustment;
use Neos\ContentRepository\StructureAdjustment\Adjustment\DisallowedChildNodeAdjustment;
use Neos\ContentRepository\StructureAdjustment\Adjustment\PropertyAdjustment;
use Neos\ContentRepository\StructureAdjustment\Adjustment\StructureAdjustment;
use Neos\ContentRepository\StructureAdjustment\Adjustment\TetheredNodeAdjustments;
use Neos\ContentRepository\StructureAdjustment\Adjustment\UnknownNodeTypeAdjustment;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event\CorrelationId;
use Neos\EventStore\Model\Event\Version;
use Neos\EventStore\Model\Events as NormalizedEvents;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Psr\Clock\ClockInterface;

final class StructureAdjustmentService implements ContentRepositoryServiceInterface
{
    private readonly TetheredNodeAdjustments $tetheredNodeAdjustments;
    private readonly UnknownNodeTypeAdjustment $unknownNodeTypeAdjustment;
    private readonly DisallowedChildNodeAdjustment $disallowedChildNodeAdjustment;
    private readonly PropertyAdjustment $propertyAdjustment;
    private readonly DimensionAdjustment $dimensionAdjustment;

    /**
     * Content graph bound to the live workspace to iterate over the "real" Nodes; that is, the nodes,
     * which have an entry in the Graph Projection's "node" table.
     */
    private readonly ContentGraphInterface $liveContentGraph;

    /**
     * FIXME Currently mutable as we allow fixing of one error after another from the outside.
     * Instead we need to rethink the API and consistency guarantees:
     *  - what if there are parallel users working on live?
     *  - what if a structure adjustment has a side effect on another structure adjustment,
     *    making that obsolete or being invalid.
     *
     * A fixAllErrors should allow to commit all fixes at once and catchup the graph once afterwards.
     */
    private Version $liveContentStreamVersion;

    /**
     * @internal please use the {@see StructureAdjustmentServiceFactory} instead!
     */
    public function __construct(
        ContentGraphReadModelInterface $contentGraphReadModel,
        private readonly EventStoreInterface $eventStore,
        private readonly EventNormalizer $eventNormalizer,
        private readonly SubscriptionEngine $subscriptionEngine,
        NodeTypeManager $nodeTypeManager,
        InterDimensionalVariationGraph $interDimensionalVariationGraph,
        PropertyConverter $propertyConverter,
        ClockInterface $clock,
    ) {
        $this->liveContentGraph = $contentGraphReadModel->getContentGraph(WorkspaceName::forLive());
        $liveContentStream = $contentGraphReadModel->findContentStreamById($this->liveContentGraph->getContentStreamId());
        if ($liveContentStream === null) {
            throw new \RuntimeException(sprintf('Content stream "%s" for live workspace does not exist', $this->liveContentGraph->getContentStreamId()), 1786181008);
        }
        $this->liveContentStreamVersion = $liveContentStream->version;

        $this->tetheredNodeAdjustments = new TetheredNodeAdjustments(
            $this->liveContentGraph,
            $nodeTypeManager,
            $interDimensionalVariationGraph,
            $propertyConverter,
            $clock,
        );

        $this->unknownNodeTypeAdjustment = new UnknownNodeTypeAdjustment(
            $this->liveContentGraph,
            $nodeTypeManager
        );
        $this->disallowedChildNodeAdjustment = new DisallowedChildNodeAdjustment(
            $this->liveContentGraph,
            $nodeTypeManager
        );
        $this->propertyAdjustment = new PropertyAdjustment(
            $this->liveContentGraph,
            $interDimensionalVariationGraph,
            $nodeTypeManager
        );
        $this->dimensionAdjustment = new DimensionAdjustment(
            $this->liveContentGraph,
            $interDimensionalVariationGraph,
            $nodeTypeManager
        );
    }

    /**
     * @return \Generator<int,StructureAdjustment>
     */
    public function findAllAdjustments(): \Generator
    {
        foreach ($this->liveContentGraph->findUsedNodeTypeNames() as $nodeTypeName) {
            yield from $this->findAdjustmentsForNodeType($nodeTypeName);
        }
    }

    /**
     * @param NodeTypeName $nodeTypeName
     * @return \Generator<int,StructureAdjustment>
     */
    public function findAdjustmentsForNodeType(NodeTypeName $nodeTypeName): \Generator
    {
        yield from $this->tetheredNodeAdjustments->findAdjustmentsForNodeType($nodeTypeName);
        yield from $this->unknownNodeTypeAdjustment->findAdjustmentsForNodeType($nodeTypeName);
        yield from $this->disallowedChildNodeAdjustment->findAdjustmentsForNodeType($nodeTypeName);
        yield from $this->propertyAdjustment->findAdjustmentsForNodeType($nodeTypeName);
        yield from $this->dimensionAdjustment->findAdjustmentsForNodeType($nodeTypeName);
    }

    public function fixError(StructureAdjustment $adjustment): void
    {
        if (!$adjustment->remediation) {
            return;
        }
        $events = ($adjustment->remediation)();
        assert($events instanceof Events);

        // set correlation id and add debug metadata
        $correlationId = CorrelationId::fromString(sprintf('StructureAdjustment_%s', bin2hex(random_bytes(9))));
        $isFirstEvent = true;
        $normalizedEvents = NormalizedEvents::fromArray($events->map(function (EventInterface|DecoratedEvent $event) use (
            &$isFirstEvent,
            $correlationId,
            $adjustment
        ) {
            $metadata = $event instanceof DecoratedEvent ? $event->eventMetadata?->value ?? [] : [];
            if ($isFirstEvent) {
                $metadata['debug_reason'] = mb_strimwidth($adjustment->render(), 0, 250, '…');
                $isFirstEvent = false;
            }
            $decoratedEvent = DecoratedEvent::create(
                event: $event,
                metadata: $metadata,
                correlationId: $correlationId,
            );

            if (!$decoratedEvent->innerEvent instanceof EmbedsContentStreamId || !$decoratedEvent->innerEvent->getContentStreamId()->equals($this->liveContentGraph->getContentStreamId())) {
                throw new \RuntimeException(sprintf('StructureAdjustments must only emit events to be published on the live content stream. Got %s with %s', $decoratedEvent::class, json_encode($decoratedEvent)), 1786179987);
            }

            return $this->eventNormalizer->normalize($decoratedEvent);
        }));

        $liveContentStreamEventStreamName = ContentStreamEventStreamName::fromContentStreamId($this->liveContentGraph->getContentStreamId());
        $this->eventStore->commit($liveContentStreamEventStreamName->getEventStreamName(), $normalizedEvents, ExpectedVersion::fromVersion($this->liveContentStreamVersion));
        $this->liveContentStreamVersion = $this->liveContentStreamVersion->add(Version::fromInteger($normalizedEvents->count()));
        $this->subscriptionEngine->catchUpActive();
    }
}
