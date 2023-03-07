<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentSubgraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\ContentSubhypergraph;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantsFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingsFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencedNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingsFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use PHPUnit\Framework\Assert;

/**
 * The feature trait to test the subgraph traversal API
 */
trait NodeTraversalTrait
{
    use CurrentSubgraphTrait;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    /**
     * @When I execute the findChildNodes query for parent node aggregate id :parentNodeIdSerialized and filter :filterSerialized I expect the nodes :expectedNodeIdsSerialized to be returned
     * @When I execute the findChildNodes query for parent node aggregate id :parentNodeIdSerialized I expect the nodes :expectedNodeIdsSerialized to be returned
     * @When I execute the findChildNodes query for parent node aggregate id :parentNodeIdSerialized and filter :filterSerialized I expect no nodes to be returned
     * @When I execute the findChildNodes query for parent node aggregate id :parentNodeIdSerialized I expect no nodes to be returned
     */
    public function iExecuteTheFindChildNodesQueryIExpectTheFollowingNodes(string $parentNodeIdSerialized, string $filterSerialized = null, string $expectedNodeIdsSerialized = null): void
    {
        $parentNodeAggregateId = NodeAggregateId::fromString($parentNodeIdSerialized);
        $expectedNodeIds = $expectedNodeIdsSerialized !== null ? array_filter(explode(',', $expectedNodeIdsSerialized)) : [];
        $filter = FindChildNodesFilter::all();
        if ($filterSerialized !== null) {
            $filterValues = json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR);
            $filter = $filter->with(...$filterValues);
        }

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $actualNodeIds = array_map(static fn (Node $node) => $node->nodeAggregateId->getValue(), iterator_to_array($subgraph->findChildNodes($parentNodeAggregateId, $filter)));
            Assert::assertSame($expectedNodeIds, $actualNodeIds);
        }
    }

    /**
     * @When I execute the findReferencedNodes query for node aggregate id :nodeIdSerialized and filter :filterSerialized I expect the references :referencesSerialized to be returned
     * @When I execute the findReferencedNodes query for node aggregate id :nodeIdSerialized I expect the references :referencesSerialized to be returned
     * @When I execute the findReferencedNodes query for node aggregate id :nodeIdSerialized and filter :filterSerialized I expect no references to be returned
     * @When I execute the findReferencedNodes query for node aggregate id :nodeIdSerialized I expect no references to be returned
     */
    public function iExecuteTheFindReferencedNodesQueryIExpectTheFollowingReferences(string $nodeIdSerialized, string $filterSerialized = null, string $referencesSerialized = null): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($nodeIdSerialized);
        $expectedReferences = $referencesSerialized !== null ? json_decode($referencesSerialized, true, 512, JSON_THROW_ON_ERROR) : [];
        $filter = FindReferencedNodesFilter::all();
        if ($filterSerialized !== null) {
            $filterValues = json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR);
            $filter = $filter->with(...$filterValues);
        }

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $actualReferences = array_map(static fn (Reference $reference) => ['nodeAggregateId' => $reference->node->nodeAggregateId->getValue(), 'name' => $reference->name->value, 'properties' => json_decode(json_encode($reference->properties?->serialized(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR)], iterator_to_array($subgraph->findReferencedNodes($nodeAggregateId, $filter)));
            Assert::assertSame($expectedReferences, $actualReferences);
        }
    }

    /**
     * @When I execute the findReferencingNodes query for node aggregate id :nodeIdSerialized and filter :filterSerialized I expect the references :referencesSerialized to be returned
     * @When I execute the findReferencingNodes query for node aggregate id :nodeIdSerialized I expect the references :referencesSerialized to be returned
     * @When I execute the findReferencingNodes query for node aggregate id :nodeIdSerialized and filter :filterSerialized I expect no references to be returned
     * @When I execute the findReferencingNodes query for node aggregate id :nodeIdSerialized I expect no references to be returned
     */
    public function iExecuteTheFindReferencingNodesQueryIExpectTheFollowingReferences(string $nodeIdSerialized, string $filterSerialized = null, string $referencesSerialized = null): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($nodeIdSerialized);
        $expectedReferences = $referencesSerialized !== null ? json_decode($referencesSerialized, true, 512, JSON_THROW_ON_ERROR) : [];
        $filter = FindReferencingNodesFilter::all();
        if ($filterSerialized !== null) {
            $filterValues = json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR);
            $filter = $filter->with(...$filterValues);
        }

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $actualReferences = array_map(static fn (Reference $reference) => ['nodeAggregateId' => $reference->node->nodeAggregateId->getValue(), 'name' => $reference->name->value, 'properties' => json_decode(json_encode($reference->properties?->serialized(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR)], iterator_to_array($subgraph->findReferencingNodes($nodeAggregateId, $filter)));
            Assert::assertSame($expectedReferences, $actualReferences);
        }
    }

    /**
     * @When I execute the findNodeById query for node aggregate id :nodeIdSerialized I expect no node to be returned
     * @When I execute the findNodeById query for node aggregate id :nodeIdSerialized I expect the node :expectedNodeIdSerialized to be returned
     */
    public function iExecuteTheFindNodeByIdQueryIExpectTheFollowingNodes(string $nodeIdSerialized, string $expectedNodeIdSerialized = null): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($nodeIdSerialized);
        $expectedNodeAggregateId = $expectedNodeIdSerialized !== null ? NodeAggregateId::fromString($expectedNodeIdSerialized) : null;

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $actualNode = $subgraph->findNodeById($nodeAggregateId);
            Assert::assertSame($actualNode?->nodeAggregateId->getValue(), $expectedNodeAggregateId?->getValue());
        }
    }

    /**
     * @When I execute the findParentNode query for node aggregate id :nodeIdSerialized I expect no node to be returned
     * @When I execute the findParentNode query for node aggregate id :nodeIdSerialized I expect the node :expectedNodeIdSerialized to be returned
     */
    public function iExecuteTheFindParentNodeQueryIExpectTheFollowingNodes(string $nodeIdSerialized, string $expectedNodeIdSerialized = null): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($nodeIdSerialized);
        $expectedNodeAggregateId = $expectedNodeIdSerialized !== null ? NodeAggregateId::fromString($expectedNodeIdSerialized) : null;

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $actualParentNode = $subgraph->findParentNode($nodeAggregateId);
            Assert::assertSame($actualParentNode?->nodeAggregateId->getValue(), $expectedNodeAggregateId?->getValue());
        }
    }

    /**
     * @When I execute the findNodeByPath query for path :pathSerialized and starting node aggregate id :startingNodeIdSerialized I expect no node to be returned
     * @When I execute the findNodeByPath query for path :pathSerialized and starting node aggregate id :startingNodeIdSerialized I expect the node :expectedNodeIdSerialized to be returned
     */
    public function iExecuteTheFindNodeByPathQueryIExpectTheFollowingNodes(string $pathSerialized, string $startingNodeIdSerialized, string $expectedNodeIdSerialized = null): void
    {
        $path = NodePath::fromString($pathSerialized);
        $startingNodeAggregateId = NodeAggregateId::fromString($startingNodeIdSerialized);
        $expectedNodeAggregateId = $expectedNodeIdSerialized !== null ? NodeAggregateId::fromString($expectedNodeIdSerialized) : null;

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $actualNode = $subgraph->findNodeByPath($path, $startingNodeAggregateId);
            Assert::assertSame($actualNode?->nodeAggregateId->getValue(), $expectedNodeAggregateId?->getValue());
        }
    }

    /**
     * @When I execute the findChildNodeConnectedThroughEdgeName query for parent node aggregate id :parentNodeIdSerialized and edge name :edgeNameSerialized I expect no node to be returned
     * @When I execute the findChildNodeConnectedThroughEdgeName query for parent node aggregate id :parentNodeIdSerialized and edge name :edgeNameSerialized I expect the node :expectedNodeIdSerialized to be returned
     */
    public function iExecuteTheFindChildNodeConnectedThroughEdgeNameQueryIExpectTheFollowingNodes(string $parentNodeIdSerialized, string $edgeNameSerialized, string $expectedNodeIdSerialized = null): void
    {
        $parentNodeAggregateId = NodeAggregateId::fromString($parentNodeIdSerialized);
        $edgeName = NodeName::fromString($edgeNameSerialized);
        $expectedNodeAggregateId = $expectedNodeIdSerialized !== null ? NodeAggregateId::fromString($expectedNodeIdSerialized) : null;

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $actualNode = $subgraph->findChildNodeConnectedThroughEdgeName($parentNodeAggregateId, $edgeName);
            Assert::assertSame($actualNode?->nodeAggregateId->getValue(), $expectedNodeAggregateId?->getValue());
        }
    }

    /**
     * @When I execute the findSucceedingSiblings query for sibling node aggregate id :siblingNodeIdSerialized and filter :filterSerialized I expect the nodes :expectedNodeIdsSerialized to be returned
     * @When I execute the findSucceedingSiblings query for sibling node aggregate id :siblingNodeIdSerialized I expect the nodes :expectedNodeIdsSerialized to be returned
     * @When I execute the findSucceedingSiblings query for sibling node aggregate id :siblingNodeIdSerialized and filter :filterSerialized I expect no nodes to be returned
     * @When I execute the findSucceedingSiblings query for sibling node aggregate id :siblingNodeIdSerialized I expect no nodes to be returned
     */
    public function iExecuteTheFindSucceedingSiblingsQueryIExpectTheFollowingNodes(string $siblingNodeIdSerialized, string $filterSerialized = null, string $expectedNodeIdsSerialized = null): void
    {
        $siblingNodeAggregateId = NodeAggregateId::fromString($siblingNodeIdSerialized);
        $expectedNodeIds = $expectedNodeIdsSerialized !== null ? array_filter(explode(',', $expectedNodeIdsSerialized)) : [];
        $filter = FindSucceedingSiblingsFilter::all();
        if ($filterSerialized !== null) {
            $filterValues = json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR);
            $filter = $filter->with(...$filterValues);
        }

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $actualNodeIds = array_map(static fn (Node $node) => $node->nodeAggregateId->getValue(), iterator_to_array($subgraph->findSucceedingSiblings($siblingNodeAggregateId, $filter)));
            Assert::assertSame($expectedNodeIds, $actualNodeIds);
        }
    }

    /**
     * @When I execute the findPrecedingSiblings query for sibling node aggregate id :siblingNodeIdSerialized and filter :filterSerialized I expect the nodes :expectedNodeIdsSerialized to be returned
     * @When I execute the findPrecedingSiblings query for sibling node aggregate id :siblingNodeIdSerialized I expect the nodes :expectedNodeIdsSerialized to be returned
     * @When I execute the findPrecedingSiblings query for sibling node aggregate id :siblingNodeIdSerialized and filter :filterSerialized I expect no nodes to be returned
     * @When I execute the findPrecedingSiblings query for sibling node aggregate id :siblingNodeIdSerialized I expect no nodes to be returned
     */
    public function iExecuteTheFindPrecedingSiblingsQueryIExpectTheFollowingNodes(string $siblingNodeIdSerialized, string $filterSerialized = null, string $expectedNodeIdsSerialized = null): void
    {
        $siblingNodeAggregateId = NodeAggregateId::fromString($siblingNodeIdSerialized);
        $expectedNodeIds = $expectedNodeIdsSerialized !== null ? array_filter(explode(',', $expectedNodeIdsSerialized)) : [];
        $filter = FindPrecedingSiblingsFilter::all();
        if ($filterSerialized !== null) {
            $filterValues = json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR);
            $filter = $filter->with(...$filterValues);
        }

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $actualNodeIds = array_map(static fn (Node $node) => $node->nodeAggregateId->getValue(), iterator_to_array($subgraph->findPrecedingSiblings($siblingNodeAggregateId, $filter)));
            Assert::assertSame($expectedNodeIds, $actualNodeIds);
        }
    }

    /**
     * @When I execute the findNodePath query for node aggregate id :nodeIdSerialized I expect the path :expectedPathSerialized to be returned
     * @When I execute the findNodePath query for node aggregate id :nodeIdSerialized I expect no path to be returned
     */
    public function iExecuteTheFindNodePathQueryIExpectTheFollowingNodes(string $nodeIdSerialized, string $expectedPathSerialized = null): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($nodeIdSerialized);
        $expectedNodePath = $expectedPathSerialized !== null ? NodePath::fromString($expectedPathSerialized) : null;

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $actualNodePath = $subgraph->findNodePath($nodeAggregateId);
            if ($expectedNodePath === null) {
                Assert::assertNull($actualNodePath);
            } else {
                Assert::assertSame((string)$expectedNodePath, (string)$actualNodePath);
            }
        }
    }

    /**
     * @When I execute the findSubtrees query for entry node aggregate id :entryNodeIdsSerialized I expect the following tree:
     * @When I execute the findSubtrees query for entry node aggregate id :entryNodeIdsSerialized I expect no results
     * @When I execute the findSubtrees query for entry node aggregate id :entryNodeIdsSerialized and filter :filterSerialized I expect the following tree:
     * @When I execute the findSubtrees query for entry node aggregate id :entryNodeIdsSerialized and filter :filterSerialized I expect no results
     */
    public function iExecuteTheFindSubtreesQueryIExpectTheFollowingTrees(string $entryNodeIdSerialized, string $filterSerialized = null, PyStringNode $expectedTree = null): void
    {
        $entryNodeAggregateIds = NodeAggregateIds::create(NodeAggregateId::fromString($entryNodeIdSerialized));
        $filter = FindSubtreesFilter::all();
        if ($filterSerialized !== null) {
            $filterValues = json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR);
            $filter = $filter->with(...$filterValues);
        }

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $result = [];
            $subtreeStack = iterator_to_array($subgraph->findSubtrees($entryNodeAggregateIds, $filter));
            while ($subtreeStack !== []) {
                /** @var Subtree $subtree */
                $subtree = array_shift($subtreeStack);
                $result[] = str_repeat(' ', $subtree->level) . $subtree->node->nodeAggregateId->getValue();
                $subtreeStack = [...$subtree->children, ...$subtreeStack];
            }
            Assert::assertSame($expectedTree?->getRaw() ?? '', implode(chr(10), $result));
        }
    }

    /**
     * @When I execute the findDescendants query for entry node aggregate id :entryNodeIdSerialized and filter :filterSerialized I expect the nodes :expectedNodeIdsSerialized to be returned
     * @When I execute the findDescendants query for entry node aggregate id :entryNodeIdSerialized I expect the nodes :expectedNodeIdsSerialized to be returned
     * @When I execute the findDescendants query for entry node aggregate id :entryNodeIdSerialized and filter :filterSerialized I expect no nodes to be returned
     * @When I execute the findDescendants query for entry node aggregate id :entryNodeIdSerialized I expect no nodes to be returned
     */
    public function iExecuteTheFindDescendantsQueryIExpectTheFollowingNodes(string $entryNodeIdSerialized, string $filterSerialized = null, string $expectedNodeIdsSerialized = null): void
    {
        $entryNodeAggregateIds = NodeAggregateIds::create(NodeAggregateId::fromString($entryNodeIdSerialized));
        $expectedNodeIds = $expectedNodeIdsSerialized !== null ? array_filter(explode(',', $expectedNodeIdsSerialized)) : [];
        $filter = FindDescendantsFilter::all();
        if ($filterSerialized !== null) {
            $filterValues = json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR);
            $filter = $filter->with(...$filterValues);
        }

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $actualNodeIds = array_map(static fn (Node $node) => $node->nodeAggregateId->getValue(), iterator_to_array($subgraph->findDescendants($entryNodeAggregateIds, $filter)));
            Assert::assertSame($expectedNodeIds, $actualNodeIds);
        }
    }

}
