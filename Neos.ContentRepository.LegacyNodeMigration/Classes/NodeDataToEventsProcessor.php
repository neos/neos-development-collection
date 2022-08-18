<?php
/** @noinspection DuplicatedCode */
declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration;

use Doctrine\DBAL\Platforms\PostgreSQL100Platform;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\VariantType;
use Neos\ContentRepository\EventStore\EventInterface;
use Neos\ContentRepository\EventStore\EventNormalizer;
use Neos\ContentRepository\Export\Event\ValueObject\ExportedEvent;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\ProcessorResult;
use Neos\ContentRepository\Feature\Common\PropertyValuesToWrite;
use Neos\ContentRepository\Feature\Common\SerializedNodeReferences;
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
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Node\PropertyName;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\Flow\Persistence\Doctrine\DataTypes\JsonArrayType;
use Neos\Flow\Property\PropertyMapper;
use Ramsey\Uuid\Uuid;
use Webmozart\Assert\Assert;

final class NodeDataToEventsProcessor implements ProcessorInterface
{

    private NodeTypeName $sitesNodeTypeName;
    private ContentStreamIdentifier $contentStreamIdentifier;
    private VisitedNodeAggregates $visitedNodes;

    /**
     * @var NodeReferencesWereSet[]
     */
    private array $nodeReferencesWereSetEvents = [];

    private int $numberOfExportedEvents = 0;

    /**
     * @var resource|null
     */
    private $eventFileResource;

    public function __construct(
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly PropertyMapper $propertyMapper,
        private readonly PropertyConverter $propertyConverter,
        private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
        private readonly EventNormalizer $eventNormalizer,
        private readonly Filesystem $files,
        private readonly iterable $nodeDataRows,
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

    public function onMessage(\Closure $callback): void
    {
        $this->callbacks[] = $callback;
    }

    public function run(): ProcessorResult
    {
        $this->resetRuntimeState();

        foreach ($this->nodeDataRows as $nodeDataRow) {
            if ($nodeDataRow['path'] === '/sites') {
                $sitesNodeAggregateIdentifier = NodeAggregateIdentifier::fromString($nodeDataRow['identifier']);
                $this->visitedNodes->addRootNode($sitesNodeAggregateIdentifier, $this->sitesNodeTypeName, NodePath::fromString('/sites'), $this->interDimensionalVariationGraph->getDimensionSpacePoints());
                $this->exportEvent(new RootNodeAggregateWithNodeWasCreated($this->contentStreamIdentifier, $sitesNodeAggregateIdentifier, $this->sitesNodeTypeName, $this->interDimensionalVariationGraph->getDimensionSpacePoints(), NodeAggregateClassification::CLASSIFICATION_ROOT, UserIdentifier::forSystemUser()));
                continue;
            }
            try {
                $this->processNodeData($nodeDataRow);
            } catch (MigrationException $exception) {
                return ProcessorResult::error($exception->getMessage());
            }
        }
        // Set References, now when the full import is done.
        foreach ($this->nodeReferencesWereSetEvents as $nodeReferencesWereSetEvent) {
            $this->exportEvent($nodeReferencesWereSetEvent);
        }

        try {
            $this->files->writeStream('events.jsonl', $this->eventFileResource);
        } catch (FilesystemException $exception) {
            return ProcessorResult::error(sprintf('Failed to write events.jsonl: %s', $exception->getMessage()));
        }
        return ProcessorResult::success(sprintf('Exported %d event%s', $this->numberOfExportedEvents, $this->numberOfExportedEvents === 1 ? '' : 's'));
    }

    /** ----------------------------- */

    private function resetRuntimeState(): void
    {
        $this->visitedNodes = new VisitedNodeAggregates();
        $this->nodeReferencesWereSetEvents = [];
        $this->numberOfExportedEvents = 0;
        $this->eventFileResource = fopen('php://temp/maxmemory:5242880', 'rb+');
        Assert::resource($this->eventFileResource, null, 'Failed to create temporary event file resource');
    }

    private function exportEvent(EventInterface $event): void
    {
        $exportedEvent = new ExportedEvent(
            Uuid::uuid4()->toString(),
            $this->eventNormalizer->getEventType($event)->value,
            json_decode($this->eventNormalizer->getEventData($event)->value, true),
            []
        );
        fwrite($this->eventFileResource, $exportedEvent->toJson() . chr(10));
        $this->numberOfExportedEvents ++;
    }

    /**
     * @param array $nodeDataRow
     */
    private function processNodeData(array $nodeDataRow): void
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
            throw new MigrationException(sprintf('Failed to find parent node for node with id "%s" and dimensions: %s. Did you properly configure your dimensions setup to be in sync with the old setup?', $nodeAggregateIdentifier, $originDimensionSpacePoint), 1655980069);
        }
        $pathParts = $nodePath->getParts();
        $nodeName = end($pathParts);
        $nodeTypeName = NodeTypeName::fromString($nodeDataRow['nodetype']);
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName->getValue());
        $serializedPropertyValuesAndReferences = $this->extractPropertyValuesAndReferences($nodeDataRow, $nodeType);

        if ($this->isAutoCreatedChildNode($parentNodeAggregate->nodeTypeName, $nodeName) && !$this->visitedNodes->containsNodeAggregate($nodeAggregateIdentifier)) {
            // Create tethered node if the node was not found before.
            // If the node was already visited, we want to create a node variant (and keep the tethering status)
            $specializations = $this->interDimensionalVariationGraph->getSpecializationSet($originDimensionSpacePoint->toDimensionSpacePoint(), true, $this->visitedNodes->alreadyVisitedOriginDimensionSpacePoints($nodeAggregateIdentifier)->toDimensionSpacePointSet());
            $this->exportEvent(new NodeAggregateWithNodeWasCreated($this->contentStreamIdentifier, $nodeAggregateIdentifier, $nodeTypeName, $originDimensionSpacePoint, $specializations, $parentNodeAggregate->nodeAggregateIdentifier, $nodeName, $serializedPropertyValuesAndReferences->serializedPropertyValues, NodeAggregateClassification::CLASSIFICATION_TETHERED, UserIdentifier::forSystemUser(), null));
        } elseif ($this->visitedNodes->containsNodeAggregate($nodeAggregateIdentifier)) {
            // Create node variant, BOTH for tethered and regular nodes
            $this->createNodeVariant($nodeAggregateIdentifier, $originDimensionSpacePoint, $serializedPropertyValuesAndReferences, $parentNodeAggregate);
        } else {
            // create node aggregate
            $this->exportEvent( new NodeAggregateWithNodeWasCreated($this->contentStreamIdentifier, $nodeAggregateIdentifier, $nodeTypeName, $originDimensionSpacePoint, $this->interDimensionalVariationGraph->getSpecializationSet($originDimensionSpacePoint->toDimensionSpacePoint()), $parentNodeAggregate->nodeAggregateIdentifier, $nodeName, $serializedPropertyValuesAndReferences->serializedPropertyValues, NodeAggregateClassification::CLASSIFICATION_REGULAR, UserIdentifier::forSystemUser(), null));
        }
        // nodes are hidden via NodeAggregateWasDisabled event
        if ($nodeDataRow['hidden']) {
            $this->exportEvent( new NodeAggregateWasDisabled($this->contentStreamIdentifier, $nodeAggregateIdentifier, $this->interDimensionalVariationGraph->getSpecializationSet($originDimensionSpacePoint->toDimensionSpacePoint(), true, $this->visitedNodes->alreadyVisitedOriginDimensionSpacePoints($nodeAggregateIdentifier)->toDimensionSpacePointSet()), UserIdentifier::forSystemUser()));
        }
        foreach ($serializedPropertyValuesAndReferences->references as $referencePropertyName => $destinationNodeAggregateIdentifiers) {
            $this->nodeReferencesWereSetEvents[] = new NodeReferencesWereSet($this->contentStreamIdentifier, $nodeAggregateIdentifier, new OriginDimensionSpacePointSet([$originDimensionSpacePoint]), PropertyName::fromString($referencePropertyName), SerializedNodeReferences::fromNodeAggregateIdentifiers($destinationNodeAggregateIdentifiers), UserIdentifier::forSystemUser());
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

            // In the old `Node`, we call the property mapper to convert the returned properties from NodeData;
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
    private function createNodeVariant(NodeAggregateIdentifier $nodeAggregateIdentifier, OriginDimensionSpacePoint $originDimensionSpacePoint, SerializedPropertyValuesAndReferences $serializedPropertyValuesAndReferences, VisitedNodeAggregate $parentNodeAggregate): void
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
        $this->exportEvent($variantCreatedEvent);
        if ($serializedPropertyValuesAndReferences->serializedPropertyValues->count() > 0) {
            $this->exportEvent(new NodePropertiesWereSet($this->contentStreamIdentifier, $nodeAggregateIdentifier, $originDimensionSpacePoint, $serializedPropertyValuesAndReferences->serializedPropertyValues, UserIdentifier::forSystemUser()));
        }
        // When we specialize/generalize, we create a node variant at exactly the same tree location as the source node
        // If the parent node aggregate id differs, we need to move the just created variant to the new location
        $nodeAggregate = $this->visitedNodes->getByNodeAggregateIdentifier($nodeAggregateIdentifier);
        if (!$parentNodeAggregate->nodeAggregateIdentifier->equals($nodeAggregate->getVariant($variantSourceOriginDimensionSpacePoint)->parentNodeAggregateIdentifier)) {
            $this->exportEvent(new NodeAggregateWasMoved(
                $this->contentStreamIdentifier,
                $nodeAggregateIdentifier,
                NodeMoveMappings::fromArray([
                    new NodeMoveMapping(
                        $originDimensionSpacePoint,
                        NodeVariantAssignments::create()->add(new NodeVariantAssignment($parentNodeAggregate->nodeAggregateIdentifier, $variantSourceOriginDimensionSpacePoint), $originDimensionSpacePoint->toDimensionSpacePoint()),
                        NodeVariantAssignments::create()
                    )
                ]),
                new DimensionSpacePointSet([]),
                UserIdentifier::forSystemUser()
            ));
        }
    }

    private function isAutoCreatedChildNode(NodeTypeName $parentNodeTypeName, NodeName $nodeName): bool
    {
        if (!$this->nodeTypeManager->hasNodeType($parentNodeTypeName->getValue())) {
            return false;
        }
        $nodeTypeOfParent = $this->nodeTypeManager->getNodeType($parentNodeTypeName->getValue());
        return $nodeTypeOfParent->hasAutoCreatedChildNode($nodeName);
    }
}
