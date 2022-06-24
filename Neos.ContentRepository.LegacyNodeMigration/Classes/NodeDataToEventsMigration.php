<?php
/** @noinspection DuplicatedCode */
declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration;

use Doctrine\DBAL\Platforms\PostgreSQL100Platform;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\VariantType;
use Neos\ContentRepository\Feature\Common\PropertyValuesToWrite;
use Neos\ContentRepository\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\ContentRepository\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Feature\NodeMove\Event\NodeMoveMapping;
use Neos\ContentRepository\Feature\NodeMove\Event\NodeMoveMappings;
use Neos\ContentRepository\Feature\NodeMove\Event\NodeVariantAssignment;
use Neos\ContentRepository\Feature\NodeMove\Event\NodeVariantAssignments;
use Neos\ContentRepository\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\LegacyNodeMigration\Exception\MigrationException;
use Neos\ContentRepository\LegacyNodeMigration\Helpers\SerializedPropertyValuesAndReferences;
use Neos\ContentRepository\LegacyNodeMigration\Helpers\VisitedNodeAggregate;
use Neos\ContentRepository\LegacyNodeMigration\Helpers\VisitedNodeAggregates;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifiers;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\Node\NodePath;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\PropertyName;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Persistence\Doctrine\DataTypes\JsonArrayType;
use Neos\Flow\Property\PropertyMapper;

final class NodeDataToEventsMigration
{

    private NodeTypeName $sitesNodeTypeName;
    private ContentStreamIdentifier $contentStreamIdentifier;

    private VisitedNodeAggregates $visitedNodes;

    /**
     * @var NodeReferencesWereSet[]
     */
    private array $nodeReferencesWereSetEvents = [];

    public function __construct(
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly PropertyMapper $propertyMapper,
        private readonly PropertyConverter $propertyConverter,
        private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
    ) {
        $this->sitesNodeTypeName = NodeTypeName::fromString('Neos.Neos:Sites');
        $this->contentStreamIdentifier = ContentStreamIdentifier::create();
        $this->visitedNodes = new VisitedNodeAggregates();
    }

    public function setContentStreamIdentifier(ContentStreamIdentifier $contentStreamIdentifier): void
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
    }

    public function setSitesNodeType(NodeTypeName $nodeTypeName): void
    {
        $this->sitesNodeTypeName = $nodeTypeName;
    }

    /**
     * @param iterable<array> $nodeDataRows
     * @return iterable<DomainEventInterface>
     */
    public function run(iterable $nodeDataRows): iterable
    {
        $this->resetRuntimeState();

        foreach ($nodeDataRows as $nodeDataRow) {
            if ($nodeDataRow['path'] === '/sites') {
                $sitesNodeAggregateIdentifier = NodeAggregateIdentifier::fromString($nodeDataRow['identifier']);
                $this->visitedNodes->addRootNode($sitesNodeAggregateIdentifier, $this->sitesNodeTypeName, NodePath::fromString('/sites'), $this->interDimensionalVariationGraph->getDimensionSpacePoints());
                yield new RootNodeAggregateWithNodeWasCreated($this->contentStreamIdentifier, $sitesNodeAggregateIdentifier, $this->sitesNodeTypeName, $this->interDimensionalVariationGraph->getDimensionSpacePoints(), NodeAggregateClassification::CLASSIFICATION_ROOT, UserIdentifier::forSystemUser());
                continue;
            }
            yield from $this->processNodeData($nodeDataRow);
        }
        // Set References, now when the full import is done.
        yield from $this->nodeReferencesWereSetEvents;
    }

    /** ----------------------------- */

    private function resetRuntimeState(): void
    {
        $this->visitedNodes = new VisitedNodeAggregates();
        $this->nodeReferencesWereSetEvents = [];
        $this->nodeDatasToExportAtNextIteration = [];
    }

    /**
     * @param array $nodeDataRow
     * @return \Traversable<DomainEventInterface>
     */
    private function processNodeData(array $nodeDataRow): \Traversable
    {
        $nodeAggregateIdentifier = NodeAggregateIdentifier::fromString($nodeDataRow['identifier']);
        $nodePath = NodePath::fromString(strtolower($nodeDataRow['path']));
        try {
            $dimensionArray = json_decode($nodeDataRow['dimensionvalues'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new MigrationException(sprintf('Failed to parse dimensionvalues "%s": %s', $nodeDataRow['dimensionvalues'], $exception->getMessage()), 1652967873, $exception);
        }
        /** @noinspection PhpDeprecationInspection */
        $originDimensionSpacePoint = OriginDimensionSpacePoint::fromLegacyDimensionArray($dimensionArray);
        $parentNodeAggregate = $this->visitedNodes->findMostSpecificParentNodeInDimensionGraph($nodePath, $originDimensionSpacePoint, $this->interDimensionalVariationGraph);
        if ($parentNodeAggregate === null) {
            throw new MigrationException(sprintf('Failed to find parent node for node with id "%s" and dimensions: %s', $nodeAggregateIdentifier, $originDimensionSpacePoint), 1655980069);
        }
        $pathParts = $nodePath->getParts();
        $nodeName = end($pathParts);
        $nodeTypeName = NodeTypeName::fromString($nodeDataRow['nodetype']);
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName->getValue());
        // HACK: $nodeType->getPropertyType() is missing the "initialize" call, so we need to trigger another method beforehand.
        $nodeType->getFullConfiguration();
        $serializedPropertyValuesAndReferences = $this->extractPropertyValuesAndReferences($nodeDataRow, $nodeType);

        if ($this->isAutoCreatedChildNode($parentNodeAggregate->nodeTypeName, $nodeName)) {
            // Create tethered node
            $specializations = $this->interDimensionalVariationGraph->getSpecializationSet($originDimensionSpacePoint->toDimensionSpacePoint(), true, $this->visitedNodes->alreadyVisitedOriginDimensionSpacePoints($nodeAggregateIdentifier)->toDimensionSpacePointSet());
            yield new NodeAggregateWithNodeWasCreated($this->contentStreamIdentifier, $nodeAggregateIdentifier, $nodeTypeName, $originDimensionSpacePoint, $specializations, $parentNodeAggregate->nodeAggregateIdentifier, $nodeName, $serializedPropertyValuesAndReferences->serializedPropertyValues, NodeAggregateClassification::CLASSIFICATION_TETHERED, UserIdentifier::forSystemUser(), null);
        } elseif ($this->visitedNodes->containsNodeAggregate($nodeAggregateIdentifier)) {
            // Create node variant
            yield from $this->createNodeVariant($nodeAggregateIdentifier, $originDimensionSpacePoint, $serializedPropertyValuesAndReferences, $parentNodeAggregate);
        } else {
            // create node aggregate
            yield new NodeAggregateWithNodeWasCreated($this->contentStreamIdentifier, $nodeAggregateIdentifier, $nodeTypeName, $originDimensionSpacePoint, $this->interDimensionalVariationGraph->getSpecializationSet($originDimensionSpacePoint->toDimensionSpacePoint()), $parentNodeAggregate->nodeAggregateIdentifier, $nodeName, $serializedPropertyValuesAndReferences->serializedPropertyValues, NodeAggregateClassification::CLASSIFICATION_REGULAR, UserIdentifier::forSystemUser(), null);
        }
        // nodes are hidden via NodeAggregateWasDisabled event
        if ($nodeDataRow['hidden']) {
            yield new NodeAggregateWasDisabled($this->contentStreamIdentifier, $nodeAggregateIdentifier, $this->interDimensionalVariationGraph->getSpecializationSet($originDimensionSpacePoint->toDimensionSpacePoint(), true, $this->visitedNodes->alreadyVisitedOriginDimensionSpacePoints($nodeAggregateIdentifier)->toDimensionSpacePointSet()), UserIdentifier::forSystemUser());
        }
        foreach ($serializedPropertyValuesAndReferences->references as $referencePropertyName => $destinationNodeAggregateIdentifiers) {
            $this->nodeReferencesWereSetEvents[] = new NodeReferencesWereSet($this->contentStreamIdentifier, $nodeAggregateIdentifier, $originDimensionSpacePoint, $destinationNodeAggregateIdentifiers, PropertyName::fromString($referencePropertyName), UserIdentifier::forSystemUser());
        }

        $this->visitedNodes->add($nodeAggregateIdentifier, new DimensionSpacePointSet([$originDimensionSpacePoint->toDimensionSpacePoint()]), $nodeTypeName, $nodePath, $parentNodeAggregate->nodeAggregateIdentifier);
    }

    public function extractPropertyValuesAndReferences(array $nodeDataRow, NodeType $nodeType): SerializedPropertyValuesAndReferences
    {
        $properties = [];
        $references = [];

        // Note: We use a PostgreSQL platform because the implementation is forward-compatible, @see JsonArrayType::convertToPHPValue()
        $decodedProperties = (new JsonArrayType())->convertToPHPValue($nodeDataRow['properties'], new PostgreSQL100Platform());
        if (!is_array($decodedProperties)) {
            throw new MigrationException(sprintf('Failed to decode properties %s of node "%s" (type: "%s")', json_encode($nodeDataRow['properties']), $nodeDataRow['identifier'], $nodeType), 1656057035);
        }

        foreach ($decodedProperties as $propertyName => $propertyValue) {
            $type = $nodeType->getPropertyType($propertyName);

            if ($type === 'reference' || $type === 'references') {
                if (!empty($propertyValue)) {
                    if (!is_array($propertyValue)) {
                        $propertyValue = [$propertyValue];
                    }
                    $references[$propertyName] = NodeAggregateIdentifiers::fromArray(array_map(static fn (string $identifier) => NodeAggregateIdentifier::fromString($identifier), $propertyValue));
                }
                continue;
            }

            // In the old `NodeInterface`, we call the property mapper to convert the returned properties from NodeData;
            // so we need to do the same here.
            try {
                $properties[$propertyName] = $this->propertyMapper->convert($propertyValue, $type);
            } catch (\Exception $e) {
                throw new MigrationException(sprintf('Failed to convert property "%s" of type "%s" (Node: %s): %s', $propertyName, $type, $nodeDataRow['identifier'], $e->getMessage()), 1655912878, $e);
            }
        }

        // hiddenInIndex is stored as separate column in the nodedata table, but we need it as (internal) property
        if ($nodeDataRow['hiddeninindex']) {
            $properties['_hiddenInIndex'] = true;
        }

        return new SerializedPropertyValuesAndReferences($this->propertyConverter->serializePropertyValues(PropertyValuesToWrite::fromArray($properties), $nodeType), $references);
    }

    /**
     * Produces a node variant creation event (NodeSpecializationVariantWasCreated, NodeGeneralizationVariantWasCreated or NodePeerVariantWasCreated) and the corresponding NodePropertiesWereSet event
     * if another variant of the specified node has been processed already.
     *
     * NOTE: We prioritize specializations/generalizations over peer variants ("ch" creates a specialization variant of "de" rather than a peer of "en" if both has been seen before).
     * For that reason we loop over all previously visited dimension space points until we encounter a specialization/generalization. Otherwise, the last NodePeerVariantWasCreated will be used
     */
    private function createNodeVariant(NodeAggregateIdentifier $nodeAggregateIdentifier, OriginDimensionSpacePoint $originDimensionSpacePoint, SerializedPropertyValuesAndReferences $serializedPropertyValuesAndReferences, VisitedNodeAggregate $parentNodeAggregate): \Traversable
    {
        $alreadyVisitedOriginDimensionSpacePoints = $this->visitedNodes->alreadyVisitedOriginDimensionSpacePoints($nodeAggregateIdentifier);
        $coveredDimensionSpacePoints = $this->interDimensionalVariationGraph->getSpecializationSet($originDimensionSpacePoint->toDimensionSpacePoint(), true, $alreadyVisitedOriginDimensionSpacePoints->toDimensionSpacePointSet());
        $variantCreatedEvent = null;
        $variantSourceOriginDimensionSpacePoint = null;
        foreach ($alreadyVisitedOriginDimensionSpacePoints as $alreadyVisitedOriginDimensionSpacePoint) {
            $variantType = $this->interDimensionalVariationGraph->getVariantType($originDimensionSpacePoint->toDimensionSpacePoint(), $alreadyVisitedOriginDimensionSpacePoint->toDimensionSpacePoint());
            $variantCreatedEvent = match ($variantType) {
                VariantType::TYPE_SPECIALIZATION => new NodeSpecializationVariantWasCreated($this->contentStreamIdentifier, $nodeAggregateIdentifier, $alreadyVisitedOriginDimensionSpacePoint, $originDimensionSpacePoint, $coveredDimensionSpacePoints, UserIdentifier::forSystemUser()),
                VariantType::TYPE_GENERALIZATION => new NodeGeneralizationVariantWasCreated($this->contentStreamIdentifier, $nodeAggregateIdentifier, $alreadyVisitedOriginDimensionSpacePoint, $originDimensionSpacePoint, $coveredDimensionSpacePoints, UserIdentifier::forSystemUser()),
                VariantType::TYPE_PEER => new NodePeerVariantWasCreated($this->contentStreamIdentifier, $nodeAggregateIdentifier, $alreadyVisitedOriginDimensionSpacePoint, $originDimensionSpacePoint, $coveredDimensionSpacePoints, UserIdentifier::forSystemUser()),
                VariantType::TYPE_SAME => null,
            };
            $variantSourceOriginDimensionSpacePoint = $alreadyVisitedOriginDimensionSpacePoint;
            if ($variantCreatedEvent instanceof NodeSpecializationVariantWasCreated || $variantCreatedEvent instanceof NodeGeneralizationVariantWasCreated) {
                break;
            }
        }
        if ($variantCreatedEvent === null) {
            throw new MigrationException(sprintf('Node "%s" for dimension %s was already created previously', $nodeAggregateIdentifier, $originDimensionSpacePoint), 1656057201);
        }
        yield $variantCreatedEvent;
        if ($serializedPropertyValuesAndReferences->serializedPropertyValues->count() > 0) {
            yield new NodePropertiesWereSet($this->contentStreamIdentifier, $nodeAggregateIdentifier, $originDimensionSpacePoint, $serializedPropertyValuesAndReferences->serializedPropertyValues, UserIdentifier::forSystemUser());
        }
        // When we specialize/generalize, we create a node variant at exactly the same tree location as the source node
        // If the parent node aggregate id differs, we need to move the just created variant to the new location
        $nodeAggregate = $this->visitedNodes->getByNodeAggregateIdentifier($nodeAggregateIdentifier);
        if (!$parentNodeAggregate->nodeAggregateIdentifier->equals($nodeAggregate->getVariant($variantSourceOriginDimensionSpacePoint)->parentNodeAggregateIdentifier)) {
            yield new NodeAggregateWasMoved(
                $this->contentStreamIdentifier,
                $nodeAggregateIdentifier,
                NodeMoveMappings::fromArray([
                    new NodeMoveMapping(
                        $originDimensionSpacePoint,
                        NodeVariantAssignments::createFromArray([
                            $variantSourceOriginDimensionSpacePoint->hash => new NodeVariantAssignment(
                                $parentNodeAggregate->nodeAggregateIdentifier,
                                $variantSourceOriginDimensionSpacePoint,
                            )
                        ]),
                        NodeVariantAssignments::create()
                    )
                ]),
                new DimensionSpacePointSet([]),
                UserIdentifier::forSystemUser()
            );
        }
    }

    private function isAutoCreatedChildNode(NodeTypeName $parentNodeTypeName, NodeName $nodeName): bool
    {
        if (!$this->nodeTypeManager->hasNodeType($parentNodeTypeName->getValue())) {
            return false;
        }
        $nodeTypeOfParent = $this->nodeTypeManager->getNodeType($parentNodeTypeName->getValue());
        // HACK: $nodeType->getPropertyType() is missing the "initialize" call, so we need to trigger another method beforehand.
        $nodeTypeOfParent->getFullConfiguration();
        return $nodeTypeOfParent->hasAutoCreatedChildNode($nodeName);
    }


}
