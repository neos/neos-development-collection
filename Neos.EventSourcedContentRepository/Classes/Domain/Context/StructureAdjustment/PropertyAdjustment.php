<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\StructureAdjustment;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentGraph;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodePropertiesWereSet;
use Neos\EventSourcedContentRepository\Domain\CommandResult;
use Neos\EventSourcedContentRepository\Domain\Context\StructureAdjustment\Traits\LoadNodeTypeTrait;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValue;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\EventSourcedContentRepository\Domain\Context\StructureAdjustment\Dto\StructureAdjustment;
use Neos\EventSourcing\EventStore\EventStore;
use Ramsey\Uuid\Uuid;

#[Flow\Scope("singleton")]
class PropertyAdjustment
{
    use LoadNodeTypeTrait;

    protected EventStore $eventStore;
    protected ProjectedNodeIterator $projectedNodeIterator;
    protected NodeTypeManager $nodeTypeManager;
    protected ReadSideMemoryCacheManager $readSideMemoryCacheManager;
    protected RuntimeBlocker $runtimeBlocker;
    protected ContentGraph $contentGraph;

    public function __construct(
        EventStore $eventStore,
        ProjectedNodeIterator $projectedNodeIterator,
        NodeTypeManager $nodeTypeManager,
        ReadSideMemoryCacheManager $readSideMemoryCacheManager,
        RuntimeBlocker $runtimeBlocker,
        ContentGraph $contentGraph
    ) {
        $this->eventStore = $eventStore;
        $this->projectedNodeIterator = $projectedNodeIterator;
        $this->nodeTypeManager = $nodeTypeManager;
        $this->readSideMemoryCacheManager = $readSideMemoryCacheManager;
        $this->runtimeBlocker = $runtimeBlocker;
        $this->contentGraph = $contentGraph;
    }

    public function findAdjustmentsForNodeType(NodeTypeName $nodeTypeName): \Generator
    {
        $nodeType = $this->loadNodeType($nodeTypeName);
        if ($nodeType === null) {
            // In case we cannot find the expected tethered nodes, this fix cannot do anything.
            return;
        }
        $expectedPropertiesFromNodeType = array_filter($nodeType->getProperties(), fn ($value) => $value !== null);

        foreach ($this->projectedNodeIterator->nodeAggregatesOfType($nodeTypeName) as $nodeAggregate) {
            foreach ($nodeAggregate->getNodes() as $node) {
                $propertyKeysInNode = [];

                foreach ($node->getProperties()->serialized() as $propertyKey => $property) {
                    $propertyKeysInNode[$propertyKey] = $propertyKey;

                    // detect obsolete properties
                    if (!array_key_exists($propertyKey, $expectedPropertiesFromNodeType)) {
                        yield StructureAdjustment::createForNode(
                            $node,
                            StructureAdjustment::OBSOLETE_PROPERTY,
                            'The property "' . $propertyKey
                                . '" is not defined anymore in the current NodeType schema. Suggesting to remove it.',
                            function () use ($node, $propertyKey) {
                                $this->readSideMemoryCacheManager->disableCache();
                                return $this->removeProperty($node, $propertyKey);
                            }
                        );
                    }

                    // detect non-deserializable properties
                    try {
                        $node->getProperty($propertyKey);
                    } catch (\Exception $e) {
                        $message = sprintf(
                            'The property "%s" was not deserializable. Error was: %s %s. Remove the property?',
                            $propertyKey,
                            get_class($e),
                            $e->getMessage()
                        );
                        yield StructureAdjustment::createForNode(
                            $node,
                            StructureAdjustment::NON_DESERIALIZABLE_PROPERTY,
                            $message,
                            function () use ($node, $propertyKey) {
                                $this->readSideMemoryCacheManager->disableCache();
                                return $this->removeProperty($node, $propertyKey);
                            }
                        );
                    }
                }

                // detect missing default values
                foreach ($nodeType->getDefaultValuesForProperties() as $propertyKey => $defaultValue) {
                    if ($defaultValue instanceof \DateTimeInterface) {
                        $propertyValue = json_encode($propertyValue);
                    }
                    if (!array_key_exists($propertyKey, $propertyKeysInNode)) {
                        yield StructureAdjustment::createForNode(
                            $node,
                            StructureAdjustment::MISSING_DEFAULT_VALUE,
                            'The property "' . $propertyKey . '" is is missing in the node. Suggesting to add it.',
                            function () use ($node, $propertyKey, $defaultValue) {
                                $this->readSideMemoryCacheManager->disableCache();
                                return $this->addProperty($node, $propertyKey, $defaultValue);
                            }
                        );
                    }
                }
            }
        }
    }

    protected function removeProperty(NodeInterface $node, string $propertyKey): CommandResult
    {
        $serializedPropertyValues = SerializedPropertyValues::fromArray([$propertyKey => null]);
        return $this->publishNodePropertiesWereSet($node, $serializedPropertyValues);
    }

    protected function addProperty(NodeInterface $node, string $propertyKey, $defaultValue): CommandResult
    {
        // WORKAROUND: $nodeType->getPropertyType() is missing the "initialize" call,
        // so we need to trigger another method beforehand.
        $node->getNodeType()->getFullConfiguration();
        $propertyType = $node->getNodeType()->getPropertyType($propertyKey);
        $serializedPropertyValues = SerializedPropertyValues::fromArray([
            $propertyKey => new SerializedPropertyValue($defaultValue, $propertyType)
        ]);

        return $this->publishNodePropertiesWereSet($node, $serializedPropertyValues);
    }

    protected function publishNodePropertiesWereSet(
        NodeInterface $node,
        SerializedPropertyValues $serializedPropertyValues
    ): CommandResult {
        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new NodePropertiesWereSet(
                    $node->getContentStreamIdentifier(),
                    $node->getNodeAggregateIdentifier(),
                    $node->getOriginDimensionSpacePoint(),
                    $serializedPropertyValues,
                    UserIdentifier::forSystemUser()
                ),
                Uuid::uuid4()->toString()
            )
        );

        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($node->getContentStreamIdentifier());
        $this->eventStore->commit($streamName->getEventStreamName(), $events);

        return CommandResult::fromPublishedEvents($events, $this->runtimeBlocker);
    }

    protected function getNodeTypeManager(): NodeTypeManager
    {
        return $this->nodeTypeManager;
    }
}
