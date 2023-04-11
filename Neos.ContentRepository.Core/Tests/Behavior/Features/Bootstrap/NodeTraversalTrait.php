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
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\AndCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\NegateCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\OrCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueContains;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueCriteriaInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueEndsWith;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueEquals;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueGreaterThan;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueGreaterThanOrEqual;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueLessThan;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueLessThanOrEqual;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueStartsWith;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use PHPUnit\Framework\Assert;

/**
 * The feature trait to test the subgraph traversal API
 */
trait NodeTraversalTrait
{
    use CurrentSubgraphTrait;

    /**
     * @When /^I execute the findChildNodes query for parent node aggregate id "(?<parentNodeIdSerialized>[^"]*)"(?: and filter '(?<filterSerialized>[^']*)')? I expect (?:the nodes "(?<expectedNodeIdsSerialized>[^"]*)"|no nodes) to be returned( and the total count to be (?<expectedTotalCount>\d+))?$/
     */
    public function iExecuteTheFindChildNodesQueryIExpectTheFollowingNodes(string $parentNodeIdSerialized, string $filterSerialized = '', string $expectedNodeIdsSerialized = '', int $expectedTotalCount = null): void
    {
        $parentNodeAggregateId = NodeAggregateId::fromString($parentNodeIdSerialized);
        $expectedNodeIds = array_filter(explode(',', $expectedNodeIdsSerialized));
        $filter = FindChildNodesFilter::create();
        if ($filterSerialized !== '') {
            $filterValues = json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR);
            $filter = $filter->with(...$filterValues);
        }

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $actualNodeIds = array_map(static fn(Node $node) => $node->nodeAggregateId->value, iterator_to_array($subgraph->findChildNodes($parentNodeAggregateId, $filter)));
            Assert::assertSame($expectedNodeIds, $actualNodeIds, 'findChildNodes returned an unexpected result');
            $actualCount = $subgraph->countChildNodes($parentNodeAggregateId, CountChildNodesFilter::fromFindChildNodesFilter($filter));
            Assert::assertSame($expectedTotalCount ?? count($expectedNodeIds), $actualCount, 'countChildNodes returned an unexpected result');
        }
    }

    /**
     * @When I execute the countChildNodes query for parent node aggregate id :parentNodeIdSerialized and filter :filterSerialized I expect the result :expectedResult
     * @When I execute the countChildNodes query for parent node aggregate id :parentNodeIdSerialized I expect the result :expectedResult
     */
    public function iExecuteTheCountChildNodesQueryIExpectTheResult(string $parentNodeIdSerialized, string $filterSerialized = null, int $expectedResult = null): void
    {
        $parentNodeAggregateId = NodeAggregateId::fromString($parentNodeIdSerialized);
        $filter = CountChildNodesFilter::create();
        if ($filterSerialized !== null) {
            $filterValues = json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR);
            $filter = $filter->with(...$filterValues);
        }

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $actualResult = $subgraph->countChildNodes($parentNodeAggregateId, $filter);
            Assert::assertSame($expectedResult, $actualResult);
        }
    }

    /**
     * @When I execute the findReferences query for node aggregate id :nodeIdSerialized and filter :filterSerialized I expect the references :referencesSerialized to be returned
     * @When I execute the findReferences query for node aggregate id :nodeIdSerialized I expect the references :referencesSerialized to be returned
     * @When I execute the findReferences query for node aggregate id :nodeIdSerialized and filter :filterSerialized I expect no references to be returned
     * @When I execute the findReferences query for node aggregate id :nodeIdSerialized I expect no references to be returned
     */
    public function iExecuteTheFindReferencesQueryIExpectTheFollowingReferences(string $nodeIdSerialized, string $filterSerialized = null, string $referencesSerialized = null): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($nodeIdSerialized);
        $expectedReferences = $referencesSerialized !== null ? json_decode($referencesSerialized, true, 512, JSON_THROW_ON_ERROR) : [];
        $filter = FindReferencesFilter::create();
        if ($filterSerialized !== null) {
            $filterValues = json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR);
            $filter = $filter->with(...$filterValues);
        }

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $actualReferences = array_map(static fn(Reference $reference) => [
                'nodeAggregateId' => $reference->node->nodeAggregateId->value,
                'name' => $reference->name->value,
                'properties' => json_decode(json_encode($reference->properties?->serialized(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR)
            ], iterator_to_array($subgraph->findReferences($nodeAggregateId, $filter)));
            Assert::assertSame($expectedReferences, $actualReferences);
        }
    }

    /**
     * @When I execute the countReferences query for node aggregate id :nodeIdSerialized and filter :filterSerialized I expect the result :expectedResult
     * @When I execute the countReferences query for node aggregate id :nodeIdSerialized I expect the result :expectedResult
     */
    public function iExecuteTheCountReferencesQueryIExpectTheResult(string $nodeIdSerialized, string $filterSerialized = null, int $expectedResult = null): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($nodeIdSerialized);
        $filter = CountReferencesFilter::create();
        if ($filterSerialized !== null) {
            $filterValues = json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR);
            $filter = $filter->with(...$filterValues);
        }

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $actualResult = $subgraph->countReferences($nodeAggregateId, $filter);
            Assert::assertSame($expectedResult, $actualResult);
        }
    }

    /**
     * @When I execute the findBackReferences query for node aggregate id :nodeIdSerialized and filter :filterSerialized I expect the references :referencesSerialized to be returned
     * @When I execute the findBackReferences query for node aggregate id :nodeIdSerialized I expect the references :referencesSerialized to be returned
     * @When I execute the findBackReferences query for node aggregate id :nodeIdSerialized and filter :filterSerialized I expect no references to be returned
     * @When I execute the findBackReferences query for node aggregate id :nodeIdSerialized I expect no references to be returned
     */
    public function iExecuteTheFindBackReferencesQueryIExpectTheFollowingReferences(string $nodeIdSerialized, string $filterSerialized = null, string $referencesSerialized = null): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($nodeIdSerialized);
        $expectedReferences = $referencesSerialized !== null ? json_decode($referencesSerialized, true, 512, JSON_THROW_ON_ERROR) : [];
        $filter = FindBackReferencesFilter::create();
        if ($filterSerialized !== null) {
            $filterValues = json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR);
            $filter = $filter->with(...$filterValues);
        }

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $actualReferences = array_map(static fn(Reference $reference) => [
                'nodeAggregateId' => $reference->node->nodeAggregateId->value,
                'name' => $reference->name->value,
                'properties' => json_decode(json_encode($reference->properties?->serialized(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR)
            ], iterator_to_array($subgraph->findBackReferences($nodeAggregateId, $filter)));
            Assert::assertSame($expectedReferences, $actualReferences);
        }
    }

    /**
     * @When I execute the countBackReferences query for node aggregate id :nodeIdSerialized and filter :filterSerialized I expect the result :expectedResult
     * @When I execute the countBackReferences query for node aggregate id :nodeIdSerialized I expect the result :expectedResult
     */
    public function iExecuteTheCountBackReferencesQueryIExpectTheResult(string $nodeIdSerialized, string $filterSerialized = null, int $expectedResult = null): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($nodeIdSerialized);
        $filter = CountBackReferencesFilter::create();
        if ($filterSerialized !== null) {
            $filterValues = json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR);
            $filter = $filter->with(...$filterValues);
        }

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $actualResult = $subgraph->countBackReferences($nodeAggregateId, $filter);
            Assert::assertSame($expectedResult, $actualResult);
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
            Assert::assertSame($actualNode?->nodeAggregateId->value, $expectedNodeAggregateId?->value);
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
            Assert::assertSame($actualParentNode?->nodeAggregateId->value, $expectedNodeAggregateId?->value);
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
            Assert::assertSame($actualNode?->nodeAggregateId->value, $expectedNodeAggregateId?->value);
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
            Assert::assertSame($actualNode?->nodeAggregateId->value, $expectedNodeAggregateId?->value);
        }
    }

    /**
     * @When I execute the findSucceedingSiblingNodes query for sibling node aggregate id :siblingNodeIdSerialized and filter :filterSerialized I expect the nodes :expectedNodeIdsSerialized to be returned
     * @When I execute the findSucceedingSiblingNodes query for sibling node aggregate id :siblingNodeIdSerialized I expect the nodes :expectedNodeIdsSerialized to be returned
     * @When I execute the findSucceedingSiblingNodes query for sibling node aggregate id :siblingNodeIdSerialized and filter :filterSerialized I expect no nodes to be returned
     * @When I execute the findSucceedingSiblingNodes query for sibling node aggregate id :siblingNodeIdSerialized I expect no nodes to be returned
     */
    public function iExecuteTheFindSucceedingSiblingNodesQueryIExpectTheFollowingNodes(string $siblingNodeIdSerialized, string $filterSerialized = null, string $expectedNodeIdsSerialized = null): void
    {
        $siblingNodeAggregateId = NodeAggregateId::fromString($siblingNodeIdSerialized);
        $expectedNodeIds = $expectedNodeIdsSerialized !== null ? array_filter(explode(',', $expectedNodeIdsSerialized)) : [];
        $filter = FindSucceedingSiblingNodesFilter::create();
        if ($filterSerialized !== null) {
            $filterValues = json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR);
            $filter = $filter->with(...$filterValues);
        }

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $actualNodeIds = array_map(static fn(Node $node) => $node->nodeAggregateId->value, iterator_to_array($subgraph->findSucceedingSiblingNodes($siblingNodeAggregateId, $filter)));
            Assert::assertSame($expectedNodeIds, $actualNodeIds);
        }
    }

    /**
     * @When I execute the findPrecedingSiblingNodes query for sibling node aggregate id :siblingNodeIdSerialized and filter :filterSerialized I expect the nodes :expectedNodeIdsSerialized to be returned
     * @When I execute the findPrecedingSiblingNodes query for sibling node aggregate id :siblingNodeIdSerialized I expect the nodes :expectedNodeIdsSerialized to be returned
     * @When I execute the findPrecedingSiblingNodes query for sibling node aggregate id :siblingNodeIdSerialized and filter :filterSerialized I expect no nodes to be returned
     * @When I execute the findPrecedingSiblingNodes query for sibling node aggregate id :siblingNodeIdSerialized I expect no nodes to be returned
     */
    public function iExecuteTheFindPrecedingSiblingNodesQueryIExpectTheFollowingNodes(string $siblingNodeIdSerialized, string $filterSerialized = null, string $expectedNodeIdsSerialized = null): void
    {
        $siblingNodeAggregateId = NodeAggregateId::fromString($siblingNodeIdSerialized);
        $expectedNodeIds = $expectedNodeIdsSerialized !== null ? array_filter(explode(',', $expectedNodeIdsSerialized)) : [];
        $filter = FindPrecedingSiblingNodesFilter::create();
        if ($filterSerialized !== null) {
            $filterValues = json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR);
            $filter = $filter->with(...$filterValues);
        }

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $actualNodeIds = array_map(static fn(Node $node) => $node->nodeAggregateId->value, iterator_to_array($subgraph->findPrecedingSiblingNodes($siblingNodeAggregateId, $filter)));
            Assert::assertSame($expectedNodeIds, $actualNodeIds);
        }
    }

    /**
     * @When I execute the retrieveNodePath query for node aggregate id :nodeIdSerialized I expect the path :expectedPathSerialized to be returned
     * @When I execute the retrieveNodePath query for node aggregate id :nodeIdSerialized I expect an exception :expectedExceptionMessage
     */
    public function iExecuteTheRetrieveNodePathQueryIExpectTheFollowingNodes(string $nodeIdSerialized, string $expectedPathSerialized = null, string $expectedExceptionMessage = null): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($nodeIdSerialized);
        $expectedNodePath = $expectedPathSerialized !== null ? NodePath::fromString($expectedPathSerialized) : null;

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            try {
                $actualNodePath = $subgraph->retrieveNodePath($nodeAggregateId);
            } catch (\InvalidArgumentException $exception) {
                if ($expectedExceptionMessage === null) {
                    throw $exception;
                }
                Assert::assertSame($expectedExceptionMessage, $exception->getMessage(), 'Exception message mismatch');
                continue;
            }
            if ($expectedExceptionMessage !== null) {
                Assert::fail('Expected an exception but none was thrown');
            }
            Assert::assertSame((string)$expectedNodePath, (string)$actualNodePath);
        }
    }

    /**
     * @When I execute the findSubtree query for entry node aggregate id :entryNodeIdsSerialized I expect the following tree:
     * @When I execute the findSubtree query for entry node aggregate id :entryNodeIdsSerialized I expect no results
     * @When I execute the findSubtree query for entry node aggregate id :entryNodeIdsSerialized and filter :filterSerialized I expect the following tree:
     * @When I execute the findSubtree query for entry node aggregate id :entryNodeIdsSerialized and filter :filterSerialized I expect no results
     */
    public function iExecuteTheFindSubtreeQueryIExpectTheFollowingTrees(string $entryNodeIdSerialized, string $filterSerialized = null, PyStringNode $expectedTree = null): void
    {
        $entryNodeAggregateId = NodeAggregateId::fromString($entryNodeIdSerialized);
        $filter = FindSubtreeFilter::create();
        if ($filterSerialized !== null) {
            $filterValues = json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR);
            $filter = $filter->with(...$filterValues);
        }

        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $result = [];
            $subtreeStack = [];
            $subtree = $subgraph->findSubtree($entryNodeAggregateId, $filter);
            if ($subtree !== null) {
                $subtreeStack[] = $subtree;
            }
            while ($subtreeStack !== []) {
                /** @var Subtree $subtree */
                $subtree = array_shift($subtreeStack);
                $result[] = str_repeat(' ', $subtree->level) . $subtree->node->nodeAggregateId->value;
                $subtreeStack = [...$subtree->children, ...$subtreeStack];
            }
            Assert::assertSame($expectedTree?->getRaw() ?? '', implode(chr(10), $result));
        }
    }

    /**
     * @When /^I execute the findDescendantNodes query for entry node aggregate id "(?<entryNodeIdSerialized>[^"]*)"(?: and filter '(?<filterSerialized>[^']*)')? I expect (?:the nodes "(?<expectedNodeIdsSerialized>[^"]*)"|no nodes) to be returned$/
     */
    public function iExecuteTheFindDescendantNodesQueryIExpectTheFollowingNodes(string $entryNodeIdSerialized, string $filterSerialized = '', string $expectedNodeIdsSerialized = ''): void
    {
        $entryNodeAggregateId = NodeAggregateId::fromString($entryNodeIdSerialized);
        $expectedNodeIds = array_filter(explode(',', $expectedNodeIdsSerialized));
        $filter = FindDescendantNodesFilter::create();
        if ($filterSerialized !== '') {
            $filterValues = json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR);
            $filter = $filter->with(...$filterValues);
        }
        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $actualNodeIds = array_map(static fn(Node $node) => $node->nodeAggregateId->value, iterator_to_array($subgraph->findDescendantNodes($entryNodeAggregateId, $filter)));
            Assert::assertSame($expectedNodeIds, $actualNodeIds, 'findDescendantNodes returned an unexpected result');
            $actualCount = $subgraph->countDescendantNodes($entryNodeAggregateId, CountDescendantNodesFilter::fromFindDescendantNodesFilter($filter));
            Assert::assertSame(count($expectedNodeIds), $actualCount, 'countDescendantNodes returned an unexpected result');
        }
    }

    /**
     * @When I execute the countNodes query I expect the result to be :expectedResult
     */
    public function iExecuteTheCountNodesQueryIExpectTheFollowingResult(int $expectedResult): void
    {
        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            Assert::assertSame($expectedResult, $subgraph->countNodes());
        }
    }

    /**
     * @Then I expect the node :nodeIdSerialized to have the following timestamps:
     */
    public function iExpectTheNodeToHaveTheFollowingTimestamps(string $nodeIdSerialized, TableNode $expectedTimestampsTable): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($nodeIdSerialized);
        $expectedTimestamps = array_map(static fn (string $timestamp) => $timestamp === '' ? null : \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $timestamp), $expectedTimestampsTable->getHash()[0]);
        /** @var ContentSubgraphInterface $subgraph */
        foreach ($this->getCurrentSubgraphs() as $subgraph) {
            $node = $subgraph->findNodeById($nodeAggregateId);
            if ($node === null) {
                Assert::fail(sprintf('Failed to find node with aggregate id "%s"', $nodeAggregateId->value));
            }
            $actualTimestamps = [
                'created' => $node->timestamps->created,
                'originalCreated' => $node->timestamps->originalCreated,
                'lastModified' => $node->timestamps->lastModified,
                'originalLastModified' => $node->timestamps->originalLastModified,
            ];
            Assert::assertEquals($expectedTimestamps, $actualTimestamps);
        }
    }
}
