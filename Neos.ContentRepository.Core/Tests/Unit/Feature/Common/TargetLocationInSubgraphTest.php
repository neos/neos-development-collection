<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\Feature\Common;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\Common\TargetLocationInSubgraph;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\CoverageByOrigin;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregates;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\OriginByCoverage;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Serializer;

/**
 * Test cases for {@see TargetLocationInSubgraph}
 */
class TargetLocationInSubgraphTest extends TestCase
{
    private const string SUBJECT_ID = 'nody-mc-nodeface';

    #[DataProvider('parameterProvider')]
    public function testCreate(
        ?NodeAggregateId $parentNodeAggregateId,
        ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        ?NodeAggregateId $precedingSiblingNodeAggregateId,
        ContentGraphInterface $contentGraph,
        ?\Throwable $expectedException,
    ): void {
        try {
            $actualLocation = TargetLocationInSubgraph::create(
                nodeAggregateId: NodeAggregateId::fromString(self::SUBJECT_ID),
                dimensionSpacePoint: DimensionSpacePoint::createWithoutDimensions(),
                parentNodeAggregateId: $parentNodeAggregateId,
                succeedingSiblingNodeAggregateId: $succeedingSiblingNodeAggregateId,
                precedingSiblingNodeAggregateId: $precedingSiblingNodeAggregateId,
                contentGraph: $contentGraph,
            );
            $actualException = null;
        } catch (\Throwable $actualException) {
            $actualLocation = null;
        }

        Assert::assertEquals($expectedException, $actualException);
        if (!$expectedException) {
            Assert::assertEquals($parentNodeAggregateId, $actualLocation->parentNodeAggregateId);
            Assert::assertEquals($succeedingSiblingNodeAggregateId, $actualLocation->succeedingSiblingNodeAggregateId);
            Assert::assertEquals($precedingSiblingNodeAggregateId, $actualLocation->precedingSiblingNodeAggregateId);
        }
    }

    /**
     * @return iterable<string,array{
     *      parentNodeAggregateId: ?NodeAggregateId,
     *      succeedingSiblingNodeAggregateId: ?NodeAggregateId,
     *      precedingSiblingNodeAggregateId: ?NodeAggregateId,
     *      contentGraph: ContentGraphInterface,
     *      expectedException: ?\Throwable,
     * }>
     */
    public static function parameterProvider(): iterable
    {
        yield 'emptyParameters' => [
            'parentNodeAggregateId' => null,
            'succeedingSiblingNodeAggregateId' => null,
            'precedingSiblingNodeAggregateId' => null,
            'contentGraph' => self::createContentGraph(
                nodes: [],
                parents: [],
                succeedingSiblings: [],
                precedingSiblings: [],
            ),
            'expectedException' => null,
        ];

        $parentNodeAggregateId = NodeAggregateId::fromString('sir-david-nodenborough');
        yield 'missingParent' => [
            'parentNodeAggregateId' => $parentNodeAggregateId,
            'succeedingSiblingNodeAggregateId' => null,
            'precedingSiblingNodeAggregateId' => null,
            'contentGraph' => self::createContentGraph(
                nodes: [],
                parents: [],
                succeedingSiblings: [],
                precedingSiblings: [],
            ),
            'expectedException' => NodeAggregateCurrentlyDoesNotExist::butWasExpectedTo($parentNodeAggregateId),
        ];

        $parentNode = self::createNode($parentNodeAggregateId);
        yield 'onlyParent' => [
            'parentNodeAggregateId' => $parentNodeAggregateId,
            'succeedingSiblingNodeAggregateId' => null,
            'precedingSiblingNodeAggregateId' => null,
            'contentGraph' => self::createContentGraph(
                nodes: [
                    $parentNodeAggregateId->value => $parentNode,
                ],
                parents: [
                    self::SUBJECT_ID => $parentNode,
                ],
                succeedingSiblings: [],
                precedingSiblings: [],
            ),
            'expectedException' => null,
        ];

        $succeedingSiblingId = NodeAggregateId::fromString('succeeding-mc-nodeface');
        yield 'missingSucceedingSiblingId' => [
            'parentNodeAggregateId' => null,
            'succeedingSiblingNodeAggregateId' => $succeedingSiblingId,
            'precedingSiblingNodeAggregateId' => null,
            'contentGraph' => self::createContentGraph(
                nodes: [],
                parents: [],
                succeedingSiblings: [],
                precedingSiblings: [],
            ),
            'expectedException' => NodeAggregateCurrentlyDoesNotExist::butWasExpectedTo($succeedingSiblingId),
        ];

        $succeedingSiblingNode = self::createNode($succeedingSiblingId);
        yield 'onlySucceedingSibling' => [
            'parentNodeAggregateId' => null,
            'succeedingSiblingNodeAggregateId' => $succeedingSiblingId,
            'precedingSiblingNodeAggregateId' => null,
            'contentGraph' => self::createContentGraph(
                nodes: [
                    $succeedingSiblingId->value => $succeedingSiblingNode,
                ],
                parents: [],
                succeedingSiblings: [
                    self::SUBJECT_ID => Nodes::fromArray([$succeedingSiblingNode]),
                ],
                precedingSiblings: [],
            ),
            'expectedException' => null,
        ];

        $precedingSiblingId = NodeAggregateId::fromString('preceding-mc-nodeface');
        yield 'missingPrecedingSiblingId' => [
            'parentNodeAggregateId' => null,
            'succeedingSiblingNodeAggregateId' => null,
            'precedingSiblingNodeAggregateId' => $precedingSiblingId,
            'contentGraph' => self::createContentGraph(
                nodes: [],
                parents: [],
                succeedingSiblings: [],
                precedingSiblings: [],
            ),
            'expectedException' => NodeAggregateCurrentlyDoesNotExist::butWasExpectedTo($precedingSiblingId),
        ];

        $precedingSiblingNode = self::createNode($precedingSiblingId);
        yield 'onlyPrecedingSibling' => [
            'parentNodeAggregateId' => null,
            'succeedingSiblingNodeAggregateId' => null,
            'precedingSiblingNodeAggregateId' => $precedingSiblingId,
            'contentGraph' => self::createContentGraph(
                nodes: [
                    $precedingSiblingId->value => $precedingSiblingNode,
                ],
                parents: [],
                succeedingSiblings: [],
                precedingSiblings: [
                    self::SUBJECT_ID => Nodes::fromArray([$precedingSiblingNode]),
                ],
            ),
            'expectedException' => null,
        ];

        yield 'everything' => [
            'parentNodeAggregateId' => $parentNodeAggregateId,
            'succeedingSiblingNodeAggregateId' => $succeedingSiblingId,
            'precedingSiblingNodeAggregateId' => $precedingSiblingId,
            'contentGraph' => self::createContentGraph(
                nodes: [
                    $parentNodeAggregateId->value => $parentNode,
                    $succeedingSiblingId->value => $succeedingSiblingNode,
                    $precedingSiblingId->value => $precedingSiblingNode,
                ],
                parents: [
                    self::SUBJECT_ID => $parentNode,
                ],
                succeedingSiblings: [
                    self::SUBJECT_ID => Nodes::fromArray([$succeedingSiblingNode]),
                ],
                precedingSiblings: [
                    self::SUBJECT_ID => Nodes::fromArray([$precedingSiblingNode]),
                ],
            ),
            'expectedException' => null,
        ];
    }

    /**
     * @param array<string,Node> $nodes
     * @param array<string,Node> $parents
     * @param array<string,Nodes> $succeedingSiblings
     * @param array<string,Nodes> $precedingSiblings
     */
    private static function createContentGraph(
        array $nodes,
        array $parents,
        array $succeedingSiblings,
        array $precedingSiblings,
    ): ContentGraphInterface {
        $subgraph = self::createSubgraph($nodes, $parents, $succeedingSiblings, $precedingSiblings);
        $nodeAggregates = array_map(
            fn (Node $node): NodeAggregate => self::createNodeAggregate($node),
            $nodes,
        );
        return new readonly class ($nodeAggregates, $subgraph) implements ContentGraphInterface
        {
            public function __construct(
                private array $nodeAggregates,
                private ContentSubgraphInterface $subgraph,
            ) {
            }

            public function getContentRepositoryId(): ContentRepositoryId
            {
                throw new \BadMethodCallException();
            }

            public function getWorkspaceName(): WorkspaceName
            {
                throw new \BadMethodCallException();
            }

            public function getSubgraph(
                DimensionSpacePoint $dimensionSpacePoint,
                VisibilityConstraints $visibilityConstraints
            ): ContentSubgraphInterface {
                return $this->subgraph;
            }

            public function findRootNodeAggregateByType(NodeTypeName $nodeTypeName): ?NodeAggregate
            {
                throw new \BadMethodCallException();
            }

            public function findRootNodeAggregates(Filter\FindRootNodeAggregatesFilter $filter): NodeAggregates
            {
                throw new \BadMethodCallException();
            }

            public function findNodeAggregatesByType(NodeTypeName $nodeTypeName): NodeAggregates
            {
                throw new \BadMethodCallException();
            }

            public function findNodeAggregateById(NodeAggregateId $nodeAggregateId): ?NodeAggregate
            {
                return $this->nodeAggregates[$nodeAggregateId->value] ?? null;
            }

            public function findNodeAggregatesByIds(NodeAggregateIds $nodeAggregateIds): NodeAggregates
            {
                throw new \BadMethodCallException();
            }

            public function findUsedNodeTypeNames(): NodeTypeNames
            {
                throw new \BadMethodCallException();
            }

            public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
                NodeAggregateId $childNodeAggregateId,
                OriginDimensionSpacePoint $childOriginDimensionSpacePoint
            ): ?NodeAggregate {
                throw new \BadMethodCallException();
            }

            public function findParentNodeAggregates(NodeAggregateId $childNodeAggregateId): NodeAggregates
            {
                throw new \BadMethodCallException();
            }

            public function findAncestorNodeAggregateIds(NodeAggregateId $entryNodeAggregateId): NodeAggregateIds
            {
                throw new \BadMethodCallException();
            }

            public function findChildNodeAggregates(NodeAggregateId $parentNodeAggregateId): NodeAggregates
            {
                throw new \BadMethodCallException();
            }

            public function findChildNodeAggregateByName(
                NodeAggregateId $parentNodeAggregateId,
                NodeName $name
            ): ?NodeAggregate {
                throw new \BadMethodCallException();
            }

            public function findTetheredChildNodeAggregates(NodeAggregateId $parentNodeAggregateId): NodeAggregates
            {
                throw new \BadMethodCallException();
            }

            public function getDimensionSpacePointsOccupiedByChildNodeName(
                NodeName $nodeName,
                NodeAggregateId $parentNodeAggregateId,
                OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint,
                DimensionSpacePointSet $dimensionSpacePointsToCheck
            ): DimensionSpacePointSet {
                throw new \BadMethodCallException();
            }

            public function findNodeAggregatesTaggedBy(SubtreeTag $subtreeTag): NodeAggregates
            {
                throw new \BadMethodCallException();
            }

            public function getContentStreamId(): ContentStreamId
            {
                throw new \BadMethodCallException();
            }
        };
    }

    /**
     * @param array<string,Node> $nodes
     * @param array<string,Node> $parents
     * @param array<string,Nodes> $succeedingSiblings
     * @param array<string,Nodes> $precedingSiblings
     */
    private static function createSubgraph(
        array $nodes,
        array $parents,
        array $succeedingSiblings,
        array $precedingSiblings,
    ): ContentSubgraphInterface {
        return new class($nodes, $parents, $succeedingSiblings, $precedingSiblings) implements ContentSubgraphInterface
        {
            /**
             * @param array<string,Node> $nodes
             * @param array<string,Node> $parents
             * @param array<string,Nodes> $succeedingSiblings
             * @param array<string,Nodes> $precedingSiblings
             */
            public function __construct(
                public array $nodes,
                public array $parents,
                public array $succeedingSiblings,
                public array $precedingSiblings,
            ) {
            }

            public function getContentRepositoryId(): ContentRepositoryId
            {
                throw new \BadMethodCallException();
            }

            public function getWorkspaceName(): WorkspaceName
            {
                throw new \BadMethodCallException();
            }

            public function getDimensionSpacePoint(): DimensionSpacePoint
            {
                return DimensionSpacePoint::createWithoutDimensions();
            }

            public function getVisibilityConstraints(): VisibilityConstraints
            {
                throw new \BadMethodCallException();
            }

            public function findNodeById(NodeAggregateId $nodeAggregateId): ?Node
            {
                return $this->nodes[$nodeAggregateId->value] ?? null;
            }

            public function findNodesByIds(NodeAggregateIds $nodeAggregateIds): Nodes
            {
                throw new \BadMethodCallException();
            }

            public function findRootNodeByType(NodeTypeName $nodeTypeName): ?Node
            {
                throw new \BadMethodCallException();
            }

            public function findChildNodes(
                NodeAggregateId $parentNodeAggregateId,
                Filter\FindChildNodesFilter $filter
            ): Nodes {
                throw new \BadMethodCallException();
            }

            public function countChildNodes(
                NodeAggregateId $parentNodeAggregateId,
                Filter\CountChildNodesFilter $filter
            ): int {
                throw new \BadMethodCallException();
            }

            public function findParentNode(NodeAggregateId $childNodeAggregateId): ?Node
            {
                return $this->nodes[$childNodeAggregateId->value] ?? null;
            }

            public function findSucceedingSiblingNodes(
                NodeAggregateId $siblingNodeAggregateId,
                Filter\FindSucceedingSiblingNodesFilter $filter
            ): Nodes {
                return $this->succeedingSiblings[$siblingNodeAggregateId->value] ?? Nodes::createEmpty();
            }

            public function findPrecedingSiblingNodes(
                NodeAggregateId $siblingNodeAggregateId,
                Filter\FindPrecedingSiblingNodesFilter $filter
            ): Nodes {
                return $this->precedingSiblings[$siblingNodeAggregateId->value] ?? Nodes::createEmpty();
            }

            public function findAncestorNodes(
                NodeAggregateId $entryNodeAggregateId,
                Filter\FindAncestorNodesFilter $filter
            ): Nodes {
                throw new \BadMethodCallException();
            }

            public function countAncestorNodes(
                NodeAggregateId $entryNodeAggregateId,
                Filter\CountAncestorNodesFilter $filter
            ): int {
                throw new \BadMethodCallException();
            }

            public function findClosestNode(
                NodeAggregateId $entryNodeAggregateId,
                Filter\FindClosestNodeFilter $filter
            ): ?Node {
                throw new \BadMethodCallException();
            }

            public function findDescendantNodes(
                NodeAggregateId $entryNodeAggregateId,
                Filter\FindDescendantNodesFilter $filter
            ): Nodes {
                throw new \BadMethodCallException();
            }

            public function countDescendantNodes(
                NodeAggregateId $entryNodeAggregateId,
                Filter\CountDescendantNodesFilter $filter
            ): int {
                throw new \BadMethodCallException();
            }

            public function findSubtree(
                NodeAggregateId $entryNodeAggregateId,
                Filter\FindSubtreeFilter $filter
            ): ?Subtree {
                throw new \BadMethodCallException();
            }

            public function findReferences(
                NodeAggregateId $nodeAggregateId,
                Filter\FindReferencesFilter $filter
            ): References {
                throw new \BadMethodCallException();
            }

            public function countReferences(
                NodeAggregateId $nodeAggregateId,
                Filter\CountReferencesFilter $filter
            ): int {
                throw new \BadMethodCallException();
            }

            public function findBackReferences(
                NodeAggregateId $nodeAggregateId,
                Filter\FindBackReferencesFilter $filter
            ): References {
                throw new \BadMethodCallException();
            }

            public function countBackReferences(
                NodeAggregateId $nodeAggregateId,
                Filter\CountBackReferencesFilter $filter
            ): int {
                throw new \BadMethodCallException();
            }

            public function findNodeByPath(NodeName|NodePath $path, NodeAggregateId $startingNodeAggregateId): ?Node
            {
                throw new \BadMethodCallException();
            }

            public function findNodeByAbsolutePath(AbsoluteNodePath $path): ?Node
            {
                throw new \BadMethodCallException();
            }

            public function retrieveNodePath(NodeAggregateId $nodeAggregateId): AbsoluteNodePath
            {
                throw new \BadMethodCallException();
            }

            public function countNodes(): int
            {
                throw new \BadMethodCallException();
            }
        };
    }

    private static function createNode(NodeAggregateId $nodeAggregateId): Node
    {
        return Node::create(
            contentRepositoryId: ContentRepositoryId::fromString('default'),
            workspaceName: WorkspaceName::forLive(),
            dimensionSpacePoint: DimensionSpacePoint::createWithoutDimensions(),
            aggregateId: $nodeAggregateId,
            originDimensionSpacePoint: OriginDimensionSpacePoint::createWithoutDimensions(),
            classification: NodeAggregateClassification::CLASSIFICATION_REGULAR,
            nodeTypeName: NodeTypeName::fromString('Neos.ContentRepository:Test'),
            properties: new PropertyCollection(
                serializedPropertyValues: SerializedPropertyValues::createEmpty(),
                propertyConverter: new PropertyConverter(new Serializer()),
            ),
            name: null,
            tags: NodeTags::createEmpty(),
            timestamps: Timestamps::create(
                created: new \DateTimeImmutable(timezone: new \DateTimeZone('UTC')),
                originalCreated: new \DateTimeImmutable(timezone: new \DateTimeZone('UTC')),
                lastModified: null,
                originalLastModified: null,
            ),
            visibilityConstraints: VisibilityConstraints::createEmpty(),
        );
    }

    private static function createNodeAggregate(Node $node): NodeAggregate
    {
        return NodeAggregate::create(
            contentRepositoryId: $node->contentRepositoryId,
            workspaceName: $node->workspaceName,
            nodeAggregateId: $node->aggregateId,
            classification: $node->classification,
            nodeTypeName: $node->nodeTypeName,
            nodeName: $node->name,
            occupiedDimensionSpacePoints: new OriginDimensionSpacePointSet([$node->originDimensionSpacePoint]),
            nodesByOccupiedDimensionSpacePoint: [
                $node->originDimensionSpacePoint->hash => $node,
            ],
            coverageByOccupant: CoverageByOrigin::fromArray([
                $node->originDimensionSpacePoint->hash => [
                    $node->originDimensionSpacePoint->hash => $node->dimensionSpacePoint,
                ]
            ]),
            coveredDimensionSpacePoints: DimensionSpacePointSet::fromArray([$node->dimensionSpacePoint]),
            occupationByCovered: OriginByCoverage::fromArray([
                $node->dimensionSpacePoint->hash => $node->originDimensionSpacePoint,
            ]),
            nodeTagsByCoveredDimensionSpacePoint: [
                $node->dimensionSpacePoint->hash => NodeTags::createEmpty(),
            ]
        );
    }
}
