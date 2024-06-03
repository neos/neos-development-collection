<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\CoverageByOrigin;
use Neos\ContentRepository\Core\Projection\ContentGraph\DimensionSpacePointsBySubtreeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\OriginByCoverage;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFound;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Implementation detail of ContentGraph and ContentSubgraph
 *
 * @internal
 */
final class NodeFactory
{
    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly PropertyConverter $propertyConverter,
        private readonly DimensionSpacePointsRepository $dimensionSpacePointRepository
    ) {
    }

    /**
     * @param array<string,string> $nodeRow Node Row from projection (<prefix>_node table)
     */
    public function mapNodeRowToNode(
        array $nodeRow,
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): Node {
        $nodeType = $this->nodeTypeManager->hasNodeType($nodeRow['nodetypename'])
            ? $this->nodeTypeManager->getNodeType($nodeRow['nodetypename'])
            : null;

        return Node::create(
            $this->contentRepositoryId,
            $workspaceName,
            $dimensionSpacePoint,
            NodeAggregateId::fromString($nodeRow['nodeaggregateid']),
            $this->dimensionSpacePointRepository->getOriginDimensionSpacePointByHash($nodeRow['origindimensionspacepointhash']),
            NodeAggregateClassification::from($nodeRow['classification']),
            NodeTypeName::fromString($nodeRow['nodetypename']),
            $this->createPropertyCollectionFromJsonString($nodeRow['properties']),
            isset($nodeRow['name']) ? NodeName::fromString($nodeRow['name']) : null,
            self::extractNodeTagsFromJson($nodeRow['subtreetags']),
            Timestamps::create(
                self::parseDateTimeString($nodeRow['created']),
                self::parseDateTimeString($nodeRow['originalcreated']),
                isset($nodeRow['lastmodified']) ? self::parseDateTimeString($nodeRow['lastmodified']) : null,
                isset($nodeRow['originallastmodified']) ? self::parseDateTimeString($nodeRow['originallastmodified']) : null,
            ),
            $visibilityConstraints,
            $nodeType,
            $contentStreamId
        );
    }

    /**
     * @param array<int, array<string, mixed>> $nodeRows
     */
    public function mapNodeRowsToNodes(
        array $nodeRows,
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): Nodes {
        return Nodes::fromArray(
            array_map(fn (array $nodeRow) => $this->mapNodeRowToNode(
                $nodeRow,
                $workspaceName,
                $contentStreamId,
                $dimensionSpacePoint,
                $visibilityConstraints
            ), $nodeRows)
        );
    }

    public function createPropertyCollectionFromJsonString(string $jsonString): PropertyCollection
    {
        return new PropertyCollection(
            SerializedPropertyValues::fromJsonString($jsonString),
            $this->propertyConverter
        );
    }

    /**
     * @param array<int,array<string,mixed>> $nodeRows
     */
    public function mapReferenceRowsToReferences(
        array $nodeRows,
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): References {
        $result = [];
        foreach ($nodeRows as $nodeRow) {
            $node = $this->mapNodeRowToNode(
                $nodeRow,
                $workspaceName,
                $contentStreamId,
                $dimensionSpacePoint,
                $visibilityConstraints
            );
            $result[] = new Reference(
                $node,
                ReferenceName::fromString($nodeRow['referencename']),
                $nodeRow['referenceproperties']
                    ? $this->createPropertyCollectionFromJsonString($nodeRow['referenceproperties'])
                    : null
            );
        }

        return References::fromArray($result);
    }

    /**
     * @param array<int,array<string,string>> $nodeRows
     * @throws NodeTypeNotFound
     */
    public function mapNodeRowsToNodeAggregate(
        array $nodeRows,
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
        VisibilityConstraints $visibilityConstraints
    ): ?NodeAggregate {
        if (empty($nodeRows)) {
            return null;
        }

        $rawNodeAggregateId = '';
        $rawNodeTypeName = '';
        $rawNodeName = '';
        $rawNodeAggregateClassification = '';
        $occupiedDimensionSpacePoints = [];
        $nodesByOccupiedDimensionSpacePoint = [];
        $coveredDimensionSpacePoints = [];
        $nodesByCoveredDimensionSpacePoints = [];
        $coverageByOccupants = [];
        $occupationByCovering = [];
        $dimensionSpacePointsBySubtreeTags = DimensionSpacePointsBySubtreeTags::create();

        foreach ($nodeRows as $nodeRow) {
            // A node can occupy exactly one DSP and cover multiple ones...
            $occupiedDimensionSpacePoint = $this->dimensionSpacePointRepository->getOriginDimensionSpacePointByHash($nodeRow['origindimensionspacepointhash']);
            if (!isset($nodesByOccupiedDimensionSpacePoint[$occupiedDimensionSpacePoint->hash])) {
                // ... so we handle occupation exactly once ...
                $nodesByOccupiedDimensionSpacePoint[$occupiedDimensionSpacePoint->hash] = $this->mapNodeRowToNode(
                    $nodeRow,
                    $workspaceName,
                    $contentStreamId,
                    $occupiedDimensionSpacePoint->toDimensionSpacePoint(),
                    $visibilityConstraints
                );
                $occupiedDimensionSpacePoints[] = $occupiedDimensionSpacePoint;
                $rawNodeAggregateId = $rawNodeAggregateId ?: $nodeRow['nodeaggregateid'];
                $rawNodeTypeName = $rawNodeTypeName ?: $nodeRow['nodetypename'];
                $rawNodeName = $rawNodeName ?: $nodeRow['name'];
                $rawNodeAggregateClassification = $rawNodeAggregateClassification ?: $nodeRow['classification'];
            }
            // ... and coverage always ...
            $coveredDimensionSpacePoint = DimensionSpacePoint::fromJsonString(
                $nodeRow['covereddimensionspacepoint']
            );
            $coveredDimensionSpacePoints[$coveredDimensionSpacePoint->hash] = $coveredDimensionSpacePoint;

            $coverageByOccupants[$occupiedDimensionSpacePoint->hash][$coveredDimensionSpacePoint->hash]
                = $coveredDimensionSpacePoint;
            $occupationByCovering[$coveredDimensionSpacePoint->hash] = $occupiedDimensionSpacePoint;
            $nodesByCoveredDimensionSpacePoints[$coveredDimensionSpacePoint->hash]
                = $nodesByOccupiedDimensionSpacePoint[$occupiedDimensionSpacePoint->hash];
            // ... as we do for explicit subtree tags
            foreach (self::extractNodeTagsFromJson($nodeRow['subtreetags'])->withoutInherited() as $explicitTag) {
                $dimensionSpacePointsBySubtreeTags = $dimensionSpacePointsBySubtreeTags->withSubtreeTagAndDimensionSpacePoint($explicitTag, $coveredDimensionSpacePoint);
            }
        }
        ksort($occupiedDimensionSpacePoints);
        ksort($coveredDimensionSpacePoints);

        // a nodeAggregate only exists if it at least contains one node
        assert($nodesByOccupiedDimensionSpacePoint !== []);

        return NodeAggregate::create(
            $this->contentRepositoryId,
            $workspaceName,
            NodeAggregateId::fromString($rawNodeAggregateId),
            NodeAggregateClassification::from($rawNodeAggregateClassification),
            NodeTypeName::fromString($rawNodeTypeName),
            $rawNodeName ? NodeName::fromString($rawNodeName) : null,
            new OriginDimensionSpacePointSet($occupiedDimensionSpacePoints),
            $nodesByOccupiedDimensionSpacePoint,
            CoverageByOrigin::fromArray($coverageByOccupants),
            new DimensionSpacePointSet($coveredDimensionSpacePoints),
            $nodesByCoveredDimensionSpacePoints,
            OriginByCoverage::fromArray($occupationByCovering),
            $dimensionSpacePointsBySubtreeTags,
            $contentStreamId,
        );
    }

    /**
     * @param iterable<int,array<string,string>> $nodeRows
     * @return iterable<int,NodeAggregate>
     * @throws NodeTypeNotFound
     */
    public function mapNodeRowsToNodeAggregates(
        iterable $nodeRows,
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
        VisibilityConstraints $visibilityConstraints
    ): iterable {
        $nodeTypeNames = [];
        $nodeNames = [];
        $occupiedDimensionSpacePointsByNodeAggregate = [];
        $nodesByOccupiedDimensionSpacePointsByNodeAggregate = [];
        $coveredDimensionSpacePointsByNodeAggregate = [];
        $nodesByCoveredDimensionSpacePointsByNodeAggregate = [];
        $classificationByNodeAggregate = [];
        $coverageByOccupantsByNodeAggregate = [];
        $occupationByCoveringByNodeAggregate = [];
        $dimensionSpacePointsBySubtreeTagsByNodeAggregate = [];

        foreach ($nodeRows as $nodeRow) {
            // A node can occupy exactly one DSP and cover multiple ones...
            $rawNodeAggregateId = $nodeRow['nodeaggregateid'];
            $occupiedDimensionSpacePoint = $this->dimensionSpacePointRepository->getOriginDimensionSpacePointByHash($nodeRow['origindimensionspacepointhash']);
            if (
                !isset($nodesByOccupiedDimensionSpacePointsByNodeAggregate
                [$rawNodeAggregateId][$occupiedDimensionSpacePoint->hash])
            ) {
                // ... so we handle occupation exactly once ...
                $nodesByOccupiedDimensionSpacePointsByNodeAggregate
                    [$rawNodeAggregateId][$occupiedDimensionSpacePoint->hash] = $this->mapNodeRowToNode(
                        $nodeRow,
                        $workspaceName,
                        $contentStreamId,
                        $occupiedDimensionSpacePoint->toDimensionSpacePoint(),
                        $visibilityConstraints
                    );
                $occupiedDimensionSpacePointsByNodeAggregate[$rawNodeAggregateId][]
                    = $occupiedDimensionSpacePoint;
                $nodeTypeNames[$rawNodeAggregateId] = $nodeTypeNames[$rawNodeAggregateId]
                    ?? NodeTypeName::fromString($nodeRow['nodetypename']);
                $nodeNames[$rawNodeAggregateId] = $nodeNames[$rawNodeAggregateId]
                    ?? ($nodeRow['name'] ? NodeName::fromString($nodeRow['name']) : null);
                $classificationByNodeAggregate[$rawNodeAggregateId]
                    = $classificationByNodeAggregate[$rawNodeAggregateId]
                    ?? NodeAggregateClassification::from($nodeRow['classification']);
            }
            // ... and coverage always ...
            $coveredDimensionSpacePoint = DimensionSpacePoint::fromJsonString(
                $nodeRow['covereddimensionspacepoint']
            );
            $coverageByOccupantsByNodeAggregate[$rawNodeAggregateId][$occupiedDimensionSpacePoint->hash]
                [$coveredDimensionSpacePoint->hash] = $coveredDimensionSpacePoint;
            $occupationByCoveringByNodeAggregate[$rawNodeAggregateId][$coveredDimensionSpacePoint->hash]
                = $occupiedDimensionSpacePoint;

            $coveredDimensionSpacePointsByNodeAggregate[$rawNodeAggregateId][$coveredDimensionSpacePoint->hash]
                = $coveredDimensionSpacePoint;
            $nodesByCoveredDimensionSpacePointsByNodeAggregate
                [$rawNodeAggregateId][$coveredDimensionSpacePoint->hash]
                = $nodesByOccupiedDimensionSpacePointsByNodeAggregate
                    [$rawNodeAggregateId][$occupiedDimensionSpacePoint->hash];

            // ... as we do for explicit subtree tags
            if (!array_key_exists($rawNodeAggregateId, $dimensionSpacePointsBySubtreeTagsByNodeAggregate)) {
                $dimensionSpacePointsBySubtreeTagsByNodeAggregate[$rawNodeAggregateId] = DimensionSpacePointsBySubtreeTags::create();
            }
            foreach (self::extractNodeTagsFromJson($nodeRow['subtreetags'])->withoutInherited() as $explicitTag) {
                $dimensionSpacePointsBySubtreeTagsByNodeAggregate[$rawNodeAggregateId] = $dimensionSpacePointsBySubtreeTagsByNodeAggregate[$rawNodeAggregateId]->withSubtreeTagAndDimensionSpacePoint($explicitTag, $coveredDimensionSpacePoint);
            }
        }

        foreach ($nodesByOccupiedDimensionSpacePointsByNodeAggregate as $rawNodeAggregateId => $nodes) {
            /** @var string $rawNodeAggregateId */
            yield NodeAggregate::create(
                $this->contentRepositoryId,
                $workspaceName,
                NodeAggregateId::fromString($rawNodeAggregateId),
                $classificationByNodeAggregate[$rawNodeAggregateId],
                $nodeTypeNames[$rawNodeAggregateId],
                $nodeNames[$rawNodeAggregateId],
                new OriginDimensionSpacePointSet(
                    $occupiedDimensionSpacePointsByNodeAggregate[$rawNodeAggregateId]
                ),
                $nodes,
                CoverageByOrigin::fromArray(
                    $coverageByOccupantsByNodeAggregate[$rawNodeAggregateId]
                ),
                new DimensionSpacePointSet(
                    $coveredDimensionSpacePointsByNodeAggregate[$rawNodeAggregateId]
                ),
                $nodesByCoveredDimensionSpacePointsByNodeAggregate
                    [$rawNodeAggregateId],
                OriginByCoverage::fromArray(
                    $occupationByCoveringByNodeAggregate[$rawNodeAggregateId]
                ),
                $dimensionSpacePointsBySubtreeTagsByNodeAggregate[$rawNodeAggregateId],
                $contentStreamId,
            );
        }
    }

    public static function extractNodeTagsFromJson(string $subtreeTagsJson): NodeTags
    {
        $explicitTags = [];
        $inheritedTags = [];
        try {
            $subtreeTagsArray = json_decode($subtreeTagsJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf('Failed to JSON-decode subtree tags from JSON string %s: %s', $subtreeTagsJson, $e->getMessage()), 1716476904, $e);
        }
        foreach ($subtreeTagsArray as $tagValue => $explicit) {
            if ($explicit) {
                $explicitTags[] = $tagValue;
            } else {
                $inheritedTags[] = $tagValue;
            }
        }
        return NodeTags::create(
            tags: SubtreeTags::fromStrings(...$explicitTags),
            inheritedTags: SubtreeTags::fromStrings(...$inheritedTags)
        );
    }

    private static function parseDateTimeString(string $string): \DateTimeImmutable
    {
        $result = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $string);
        if ($result === false) {
            throw new \RuntimeException(sprintf('Failed to parse "%s" into a valid DateTime', $string), 1678902055);
        }
        return $result;
    }
}
