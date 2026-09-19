<?php

declare(strict_types=1);

namespace Neos\ContentRepository\StructureAdjustment\Adjustment;

use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\Feature\Common\DimensionSpaceInternals;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;

/**
 * @internal the publication of events is not API, use commands instead.
 */
final class PropertyAdjustment
{
    use DimensionSpaceInternals;

    public function __construct(
        private readonly ContentGraphInterface $contentGraph,
        private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
        private readonly NodeTypeManager $nodeTypeManager
    ) {
    }

    /**
     * @return \Generator<int,StructureAdjustment>
     */
    public function findAdjustmentsForNodeType(NodeTypeName $nodeTypeName): \Generator
    {
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);
        if (!$nodeType) {
            // In case we cannot find the expected tethered nodes, this fix cannot do anything.
            return;
        }

        $expectedPropertiesFromNodeType = array_filter($nodeType->getProperties(), fn ($value) => $value !== null);

        foreach ($this->contentGraph->findNodeAggregatesByType($nodeTypeName) as $nodeAggregate) {
            $orderedOccupiedDimensionSpacePoints = $this->requireOrderedOccupiedDimensionSpacePoints($nodeAggregate);
            foreach ($orderedOccupiedDimensionSpacePoints as $originDimensionSpacePoint) {
                $node = $nodeAggregate->getNodeByOccupiedDimensionSpacePoint($originDimensionSpacePoint);
                $propertyKeysInNode = [];

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
                            fn () => $this->removeProperty($nodeAggregate, $node, $propertyKey)
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
                            fn () => $this->removeProperty($nodeAggregate, $node, $propertyKey)
                        );
                    }
                }

                // detect missing default values
                foreach ($nodeType->getDefaultValuesForProperties() as $propertyKey => $defaultValue) {
                    if (!array_key_exists($propertyKey, $propertyKeysInNode)) {
                        yield StructureAdjustment::createForNode(
                            $node,
                            StructureAdjustment::MISSING_DEFAULT_VALUE,
                            'The property "' . $propertyKey . '" is is missing in the node. Suggesting to add it.',
                            fn () => $this->addProperty($nodeAggregate, $node, $propertyKey, $defaultValue)
                        );
                    }
                }
            }
        }
    }

    private function removeProperty(NodeAggregate $nodeAggregate, Node $node, string $propertyKey): Events
    {
        return $this->publishNodePropertiesWereSet($nodeAggregate, $node, SerializedPropertyValues::createEmpty(), PropertyNames::fromArray([$propertyKey]));
    }

    private function addProperty(NodeAggregate $nodeAggregate, Node $node, string $propertyKey, mixed $defaultValue): Events
    {
        $propertyType = $this->nodeTypeManager->getNodeType($node->nodeTypeName)?->getPropertyType($propertyKey) ?? 'string';
        $serializedPropertyValues = SerializedPropertyValues::fromArray([
            $propertyKey => SerializedPropertyValue::create($defaultValue, $propertyType)
        ]);

        return $this->publishNodePropertiesWereSet($nodeAggregate, $node, $serializedPropertyValues, PropertyNames::createEmpty());
    }

    private function publishNodePropertiesWereSet(
        NodeAggregate $nodeAggregate,
        Node $node,
        SerializedPropertyValues $serializedPropertyValues,
        PropertyNames $propertyNames
    ): Events {
        $events = Events::with(
            new NodePropertiesWereSet(
                $this->contentGraph->getWorkspaceName(),
                $this->contentGraph->getContentStreamId(),
                $node->aggregateId,
                $node->originDimensionSpacePoint,
                $nodeAggregate->getCoverageByOccupant($node->originDimensionSpacePoint),
                $serializedPropertyValues,
                $propertyNames
            )
        );

        return $events;
    }
}
