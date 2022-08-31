<?php

declare(strict_types=1);

namespace Neos\ContentRepository\StructureAdjustment\Adjustment;

use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollectionInterface;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\SharedModel\User\UserIdentifier;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;

class PropertyAdjustment
{
    use LoadNodeTypeTrait;

    public function __construct(
        private readonly ProjectedNodeIterator $projectedNodeIterator,
        private readonly NodeTypeManager $nodeTypeManager
    ) {
    }

    /**
     * @return \Generator<int,StructureAdjustment>
     */
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

                /** @var PropertyCollectionInterface $properties */
                $properties = $node->properties;
                foreach ($properties->serialized() as $propertyKey => $property) {
                    $propertyKeysInNode[$propertyKey] = $propertyKey;

                    // detect obsolete properties
                    if (!array_key_exists($propertyKey, $expectedPropertiesFromNodeType)) {
                        yield StructureAdjustment::createForNode(
                            $node,
                            StructureAdjustment::OBSOLETE_PROPERTY,
                            'The property "' . $propertyKey
                                . '" is not defined anymore in the current NodeType schema. Suggesting to remove it.',
                            function () use ($node, $propertyKey) {
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
                                return $this->removeProperty($node, $propertyKey);
                            }
                        );
                    }
                }

                // detect missing default values
                foreach ($nodeType->getDefaultValuesForProperties() as $propertyKey => $defaultValue) {
                    if ($defaultValue instanceof \DateTimeInterface) {
                        $defaultValue = json_encode($defaultValue);
                    }
                    if (!array_key_exists($propertyKey, $propertyKeysInNode)) {
                        yield StructureAdjustment::createForNode(
                            $node,
                            StructureAdjustment::MISSING_DEFAULT_VALUE,
                            'The property "' . $propertyKey . '" is is missing in the node. Suggesting to add it.',
                            function () use ($node, $propertyKey, $defaultValue) {
                                return $this->addProperty($node, $propertyKey, $defaultValue);
                            }
                        );
                    }
                }
            }
        }
    }

    private function removeProperty(Node $node, string $propertyKey): EventsToPublish
    {
        $serializedPropertyValues = SerializedPropertyValues::fromArray([$propertyKey => null]);
        return $this->publishNodePropertiesWereSet($node, $serializedPropertyValues);
    }

    private function addProperty(Node $node, string $propertyKey, mixed $defaultValue): EventsToPublish
    {
        $propertyType = $node->nodeType->getPropertyType($propertyKey);
        $serializedPropertyValues = SerializedPropertyValues::fromArray([
            $propertyKey => new SerializedPropertyValue($defaultValue, $propertyType)
        ]);

        return $this->publishNodePropertiesWereSet($node, $serializedPropertyValues);
    }

    private function publishNodePropertiesWereSet(
        Node $node,
        SerializedPropertyValues $serializedPropertyValues
    ): EventsToPublish {
        $events = Events::with(
            new NodePropertiesWereSet(
                $node->subgraphIdentity->contentStreamIdentifier,
                $node->nodeAggregateIdentifier,
                $node->originDimensionSpacePoint,
                $serializedPropertyValues,
                UserIdentifier::forSystemUser()
            )
        );

        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($node->subgraphIdentity->contentStreamIdentifier);
        return new EventsToPublish(
            $streamName->getEventStreamName(),
            $events,
            ExpectedVersion::ANY()
        );
    }

    protected function getNodeTypeManager(): NodeTypeManager
    {
        return $this->nodeTypeManager;
    }
}
