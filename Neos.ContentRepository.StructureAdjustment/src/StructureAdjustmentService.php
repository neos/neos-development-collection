<?php

declare(strict_types=1);

namespace Neos\ContentRepository\StructureAdjustment;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\EventStore\DecoratedEvent;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\SharedModel\Id\UuidFactory;
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
use Neos\EventStore\Model\Events;

class StructureAdjustmentService implements ContentRepositoryServiceInterface
{
    protected TetheredNodeAdjustments $tetheredNodeAdjustments;
    protected UnknownNodeTypeAdjustment $unknownNodeTypeAdjustment;
    protected DisallowedChildNodeAdjustment $disallowedChildNodeAdjustment;
    protected PropertyAdjustment $propertyAdjustment;
    protected DimensionAdjustment $dimensionAdjustment;

    /**
     * Content graph bound to the live workspace to iterate over the "real" Nodes; that is, the nodes,
     * which have an entry in the Graph Projection's "node" table.
     *
     * @var ContentGraphInterface
     */
    private readonly ContentGraphInterface $liveContentGraph;

    public function __construct(
        ContentRepository $contentRepository,
        private readonly EventStoreInterface $eventStore,
        private readonly EventNormalizer $eventNormalizer,
        private readonly SubscriptionEngine $subscriptionEngine,
        NodeTypeManager $nodeTypeManager,
        InterDimensionalVariationGraph $interDimensionalVariationGraph,
        PropertyConverter $propertyConverter,
    ) {

        $this->liveContentGraph = $contentRepository->getContentGraph(WorkspaceName::forLive());

        $this->tetheredNodeAdjustments = new TetheredNodeAdjustments(
            $this->liveContentGraph,
            $nodeTypeManager,
            $interDimensionalVariationGraph,
            $propertyConverter,
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
            $nodeTypeManager
        );
        $this->dimensionAdjustment = new DimensionAdjustment(
            $this->liveContentGraph,
            $interDimensionalVariationGraph,
            $nodeTypeManager
        );
    }

    /**
     * @return \Generator|StructureAdjustment[]
     */
    public function findAllAdjustments(): \Generator
    {
        foreach ($this->liveContentGraph->findUsedNodeTypeNames() as $nodeTypeName) {
            yield from $this->findAdjustmentsForNodeType($nodeTypeName);
        }
    }

    /**
     * @param NodeTypeName $nodeTypeName
     * @return \Generator|StructureAdjustment[]
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
        $remediation = $adjustment->remediation;
        $eventsToPublish = $remediation();
        assert($eventsToPublish instanceof EventsToPublish);

        // set correlation id and add debug metadata
        $correlationId = CorrelationId::fromString(sprintf('StructureAdjustment_%s', bin2hex(random_bytes(9))));
        $isFirstEvent = true;
        $normalizedEvents = Events::fromArray($eventsToPublish->events->map(function (EventInterface|DecoratedEvent $event) use (
            &$isFirstEvent, $correlationId, $adjustment
        ) {
            $metadata = $event instanceof DecoratedEvent ? $event->eventMetadata?->value ?? [] : [];
            if ($isFirstEvent) {
                $metadata['debug_reason'] = mb_strimwidth($adjustment->render() , 0, 250, '…');
                $isFirstEvent = false;
            }
            $decoratedEvent = DecoratedEvent::create(
                event: $event,
                metadata: $metadata,
                correlationId: $correlationId,
            );

            return $this->eventNormalizer->normalize($decoratedEvent);
        }));

        $this->eventStore->commit($eventsToPublish->streamName, $normalizedEvents, $eventsToPublish->expectedVersion);
        $this->subscriptionEngine->catchUpActive();
    }
}
