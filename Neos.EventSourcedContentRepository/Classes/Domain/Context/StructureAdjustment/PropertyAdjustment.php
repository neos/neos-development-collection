<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\StructureAdjustment;

use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodePropertiesWereSet;
use Neos\EventSourcedContentRepository\Domain\Context\StructureAdjustment\Traits\LoadNodeTypeTrait;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\EventSourcedContentRepository\Domain\Context\StructureAdjustment\Dto\StructureAdjustment;
use Neos\EventSourcing\EventStore\EventStore;
use Ramsey\Uuid\Uuid;

/**
 * @Flow\Scope("singleton")
 */
class PropertyAdjustment
{
    use LoadNodeTypeTrait;

    protected EventStore $eventStore;
    protected ProjectedNodeIterator $projectedNodeIterator;
    protected NodeTypeManager $nodeTypeManager;
    protected ReadSideMemoryCacheManager $readSideMemoryCacheManager;

    public function __construct(EventStore $eventStore, ProjectedNodeIterator $projectedNodeIterator, NodeTypeManager $nodeTypeManager, ReadSideMemoryCacheManager $readSideMemoryCacheManager)
    {
        $this->eventStore = $eventStore;
        $this->projectedNodeIterator = $projectedNodeIterator;
        $this->nodeTypeManager = $nodeTypeManager;
        $this->readSideMemoryCacheManager = $readSideMemoryCacheManager;
    }

    public function findAdjustmentsForNodeType(NodeTypeName $nodeTypeName): \Generator
    {
        $nodeType = $this->loadNodeType($nodeTypeName);
        if ($nodeType === null) {
            // In case we cannot find the expected tethered nodes, this fix cannot do anything.
            return;
        }
        $expectedPropertiesFromNodeType = array_filter($nodeType->getProperties(), fn($value) => $value !== null);

        foreach ($this->projectedNodeIterator->nodeAggregatesOfType($nodeTypeName) as $nodeAggregate) {
            foreach ($nodeAggregate->getNodes() as $node) {
                foreach ($node->getProperties() as $propertyKey => $property) {
                    if (!array_key_exists($propertyKey, $expectedPropertiesFromNodeType)) {
                        yield StructureAdjustment::createForNode($node, StructureAdjustment::OBSOLETE_PROPERTY, 'The property "' . $propertyKey . '" is not defined anymore in the current NodeType schema. Suggesting to remove it.', function () use ($node, $propertyKey) {
                            $this->readSideMemoryCacheManager->disableCache();
                            return $this->removeProperty($node, $propertyKey);
                        });
                    }
                }
            }
        }
    }

    protected function removeProperty(NodeInterface $node, string $propertyKey): CommandResult
    {
        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new NodePropertiesWereSet(
                    $node->getContentStreamIdentifier(),
                    $node->getNodeAggregateIdentifier(),
                    $node->getOriginDimensionSpacePoint(),
                    SerializedPropertyValues::fromArray([$propertyKey => null])
                ),
                Uuid::uuid4()->toString()
            )
        );

        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($node->getContentStreamIdentifier());
        $this->eventStore->commit($streamName->getEventStreamName(), $events);
        return CommandResult::fromPublishedEvents($events);
    }

    protected function getNodeTypeManager(): NodeTypeManager
    {
        return $this->nodeTypeManager;
    }
}
