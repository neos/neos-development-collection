<?php

/*
 * This file is part of the Neos.ContentRepository.TestSuite package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use PHPUnit\Framework\Assert;

/**
 * The feature trait to test the subgraph traversal API
 */
trait NodeTraversalTrait
{
    use CRTestSuiteRuntimeVariables;

    /**
     * @When /^I execute the findChildNodes query for parent node aggregate id "(?<parentNodeIdSerialized>[^"]*)"(?: and filter '(?<filterSerialized>[^']*)')? I expect (?:the nodes "(?<expectedNodeIdsSerialized>[^"]*)"|no nodes) to be returned( and the total count to be (?<expectedTotalCount>\d+))?$/
     */
    public function iExecuteTheFindChildNodesQueryIExpectTheFollowingNodes(string $parentNodeIdSerialized, string $filterSerialized = '', string $expectedNodeIdsSerialized = '', int $expectedTotalCount = null): void
    {
        $parentNodeAggregateId = NodeAggregateId::fromString($parentNodeIdSerialized);
        $expectedNodeIds = array_filter(explode(',', $expectedNodeIdsSerialized));
        $filterValues = !empty($filterSerialized) ? json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR) : [];
        $filter = FindChildNodesFilter::create(...$filterValues);
        $subgraph = $this->getCurrentSubgraph();

        $actualNodeIds = array_map(static fn(Node $node) => $node->aggregateId->value, iterator_to_array($subgraph->findChildNodes($parentNodeAggregateId, $filter)));
        Assert::assertSame($expectedNodeIds, $actualNodeIds, 'findChildNodes returned an unexpected result');
        $actualCount = $subgraph->countChildNodes($parentNodeAggregateId, CountChildNodesFilter::fromFindChildNodesFilter($filter));
        Assert::assertSame($expectedTotalCount ?? count($expectedNodeIds), $actualCount, 'countChildNodes returned an unexpected result');
    }

    /**
     * @When /^I execute the findReferences query for node aggregate id "(?<nodeIdSerialized>[^"]*)"(?: and filter '(?<filterSerialized>[^']*)')? I expect (?:the references '(?<referencesSerialized>[^']*)'|no references) to be returned( and the total count to be (?<expectedTotalCount>\d+))?$/
     */
    public function iExecuteTheFindReferencesQueryIExpectTheFollowingReferences(string $nodeIdSerialized, string $filterSerialized = null, string $referencesSerialized = null, int $expectedTotalCount = null): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($nodeIdSerialized);
        $expectedReferences = $referencesSerialized !== null ? json_decode($referencesSerialized, true, 512, JSON_THROW_ON_ERROR) : [];
        $filterValues = !empty($filterSerialized) ? json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR) : [];
        $filter = FindReferencesFilter::create(...$filterValues);
        $subgraph = $this->getCurrentSubgraph();

        $actualReferences = array_map(static fn(Reference $reference) => [
            'nodeAggregateId' => $reference->node->aggregateId->value,
            'name' => $reference->name->value,
            'properties' => json_decode(json_encode($reference->properties?->serialized(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR)
        ], iterator_to_array($subgraph->findReferences($nodeAggregateId, $filter)));
        Assert::assertSame($expectedReferences, $actualReferences);
        Assert::assertSame($expectedTotalCount ?? count($expectedReferences), $subgraph->countReferences($nodeAggregateId, CountReferencesFilter::fromFindReferencesFilter($filter)));
    }

    /**
     * @When /^I execute the findBackReferences query for node aggregate id "(?<nodeIdSerialized>[^"]*)"(?: and filter '(?<filterSerialized>[^']*)')? I expect (?:the references '(?<referencesSerialized>[^']*)'|no references) to be returned( and the total count to be (?<expectedTotalCount>\d+))?$/
     */
    public function iExecuteTheFindBackReferencesQueryIExpectTheFollowingReferences(string $nodeIdSerialized, string $filterSerialized = null, string $referencesSerialized = null, int $expectedTotalCount = null): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($nodeIdSerialized);
        $expectedReferences = $referencesSerialized !== null ? json_decode($referencesSerialized, true, 512, JSON_THROW_ON_ERROR) : [];
        $filterValues = !empty($filterSerialized) ? json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR) : [];
        $filter = FindBackReferencesFilter::create(...$filterValues);
        $subgraph = $this->getCurrentSubgraph();
        $actualReferences = array_map(static fn(Reference $reference) => [
            'nodeAggregateId' => $reference->node->aggregateId->value,
            'name' => $reference->name->value,
            'properties' => json_decode(json_encode($reference->properties?->serialized(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR)
        ], iterator_to_array($subgraph->findBackReferences($nodeAggregateId, $filter)));
        Assert::assertSame($expectedReferences, $actualReferences);
        Assert::assertSame($expectedTotalCount ?? count($expectedReferences), $subgraph->countBackReferences($nodeAggregateId, CountBackReferencesFilter::fromFindBackReferencesFilter($filter)));
    }

    /**
     * @When I execute the findNodeById query for node aggregate id :nodeIdSerialized I expect no node to be returned
     * @When I execute the findNodeById query for node aggregate id :nodeIdSerialized I expect the node :expectedNodeIdSerialized to be returned
     */
    public function iExecuteTheFindNodeByIdQueryIExpectTheFollowingNodes(string $nodeIdSerialized, string $expectedNodeIdSerialized = null): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($nodeIdSerialized);
        $expectedNodeAggregateId = $expectedNodeIdSerialized !== null ? NodeAggregateId::fromString($expectedNodeIdSerialized) : null;

        $actualNode = $this->getCurrentSubgraph()->findNodeById($nodeAggregateId);
        Assert::assertSame($actualNode?->aggregateId->value, $expectedNodeAggregateId?->value);
    }

    /**
     * @When I execute the findParentNode query for node aggregate id :nodeIdSerialized I expect no node to be returned
     * @When I execute the findParentNode query for node aggregate id :nodeIdSerialized I expect the node :expectedNodeIdSerialized to be returned
     */
    public function iExecuteTheFindParentNodeQueryIExpectTheFollowingNodes(string $nodeIdSerialized, string $expectedNodeIdSerialized = null): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($nodeIdSerialized);
        $expectedNodeAggregateId = $expectedNodeIdSerialized !== null ? NodeAggregateId::fromString($expectedNodeIdSerialized) : null;

        $actualParentNode = $this->getCurrentSubgraph()->findParentNode($nodeAggregateId);
        Assert::assertSame($actualParentNode?->aggregateId->value, $expectedNodeAggregateId?->value);
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

        $actualNode = $this->getCurrentSubgraph()->findNodeByPath($path, $startingNodeAggregateId);
        Assert::assertSame($actualNode?->aggregateId->value, $expectedNodeAggregateId?->value);
    }

    /**
     * @When I execute the findNodeByAbsolutePath query for path :pathSerialized I expect no node to be returned
     * @When I execute the findNodeByAbsolutePath query for path :pathSerialized I expect the node :expectedNodeIdSerialized to be returned
     */
    public function iExecuteTheFindNodeByAbsolutePathQueryIExpectTheFollowingNodes(string $pathSerialized, string $expectedNodeIdSerialized = null): void
    {
        $path = AbsoluteNodePath::fromString($pathSerialized);
        $expectedNodeAggregateId = $expectedNodeIdSerialized !== null ? NodeAggregateId::fromString($expectedNodeIdSerialized) : null;

        $actualNode = $this->getCurrentSubgraph()->findNodeByAbsolutePath($path);
        Assert::assertSame($actualNode?->aggregateId->value, $expectedNodeAggregateId?->value);
    }

    /**
     * @When I execute the findNodeByPath query for parent node aggregate id :parentNodeIdSerialized and node name :edgeNameSerialized as path I expect no node to be returned
     * @When I execute the findNodeByPath query for parent node aggregate id :parentNodeIdSerialized and node name :edgeNameSerialized as path I expect the node :expectedNodeIdSerialized to be returned
     */
    public function iExecuteTheFindChildNodeByNodeNameQueryIExpectTheFollowingNodes(string $parentNodeIdSerialized, string $edgeNameSerialized, string $expectedNodeIdSerialized = null): void
    {
        $parentNodeAggregateId = NodeAggregateId::fromString($parentNodeIdSerialized);
        $edgeName = NodeName::fromString($edgeNameSerialized);
        $expectedNodeAggregateId = $expectedNodeIdSerialized !== null ? NodeAggregateId::fromString($expectedNodeIdSerialized) : null;

        $actualNode = $this->getCurrentSubgraph()->findNodeByPath($edgeName, $parentNodeAggregateId);
        Assert::assertSame($actualNode?->aggregateId->value, $expectedNodeAggregateId?->value);
    }

    /**
     * @When /^I execute the findSucceedingSiblingNodes query for sibling node aggregate id "(?<siblingNodeIdSerialized>[^"]*)"(?: and filter '(?<filterSerialized>[^']*)')? I expect (?:the nodes "(?<expectedNodeIdsSerialized>[^"]*)"|no nodes) to be returned$/
     */
    public function iExecuteTheFindSucceedingSiblingNodesQueryIExpectTheFollowingNodes(string $siblingNodeIdSerialized, string $filterSerialized = null, string $expectedNodeIdsSerialized = null): void
    {
        $siblingNodeAggregateId = NodeAggregateId::fromString($siblingNodeIdSerialized);
        $expectedNodeIds = $expectedNodeIdsSerialized !== null ? array_filter(explode(',', $expectedNodeIdsSerialized)) : [];
        $filterValues = !empty($filterSerialized) ? json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR) : [];
        $filter = FindSucceedingSiblingNodesFilter::create(...$filterValues);

        $actualNodeIds = array_map(
            static fn(Node $node) => $node->aggregateId->value,
            iterator_to_array($this->getCurrentSubgraph()->findSucceedingSiblingNodes($siblingNodeAggregateId, $filter))
        );
        Assert::assertSame($expectedNodeIds, $actualNodeIds);
    }

    /**
     * @When /^I execute the findPrecedingSiblingNodes query for sibling node aggregate id "(?<siblingNodeIdSerialized>[^"]*)"(?: and filter '(?<filterSerialized>[^']*)')? I expect (?:the nodes "(?<expectedNodeIdsSerialized>[^"]*)"|no nodes) to be returned$/
     */
    public function iExecuteTheFindPrecedingSiblingNodesQueryIExpectTheFollowingNodes(string $siblingNodeIdSerialized, string $filterSerialized = null, string $expectedNodeIdsSerialized = null): void
    {
        $siblingNodeAggregateId = NodeAggregateId::fromString($siblingNodeIdSerialized);
        $expectedNodeIds = $expectedNodeIdsSerialized !== null ? array_filter(explode(',', $expectedNodeIdsSerialized)) : [];
        $filterValues = !empty($filterSerialized) ? json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR) : [];
        $filter = FindPrecedingSiblingNodesFilter::create(...$filterValues);

        $actualNodeIds = array_map(
            static fn(Node $node) => $node->aggregateId->value,
            iterator_to_array($this->getCurrentSubgraph()->findPrecedingSiblingNodes($siblingNodeAggregateId, $filter))
        );
        Assert::assertSame($expectedNodeIds, $actualNodeIds);
    }

    /**
     * @When I execute the retrieveNodePath query for node aggregate id :nodeIdSerialized I expect the path :expectedPathSerialized to be returned
     * @When I execute the retrieveNodePath query for node aggregate id :nodeIdSerialized I expect an exception :expectedExceptionMessage
     */
    public function iExecuteTheRetrieveNodePathQueryIExpectTheFollowingNodes(string $nodeIdSerialized, string $expectedPathSerialized = null, string $expectedExceptionMessage = null): void
    {
        try {
            $actualNodePath = $this->getCurrentSubgraph()->retrieveNodePath(NodeAggregateId::fromString($nodeIdSerialized));
            if ($expectedExceptionMessage !== null) {
                Assert::fail('Expected an exception but none was thrown');
            }
            Assert::assertSame($expectedPathSerialized, $actualNodePath->serializeToString());
        } catch (\InvalidArgumentException $exception) {
            if ($expectedExceptionMessage === null) {
                throw $exception;
            }
            Assert::assertSame($expectedExceptionMessage, $exception->getMessage(), 'Exception message mismatch');
        }
    }

    /**
     * @When I execute the findSubtree query for entry node aggregate id :entryNodeIdSerialized I expect the following tree:
     * @When I execute the findSubtree query for entry node aggregate id :entryNodeIdSerialized I expect no results
     * @When I execute the findSubtree query for entry node aggregate id :entryNodeIdSerialized and filter :filterSerialized I expect the following tree:
     * @When I execute the findSubtree query for entry node aggregate id :entryNodeIdSerialized and filter :filterSerialized I expect no results
     * @When /^I execute the findSubtree query for entry node aggregate id "(?<entryNodeIdSerialized>[^"]*)" I expect the following tree (?<withTags>with tags):$/
     */
    public function iExecuteTheFindSubtreeQueryIExpectTheFollowingTrees(string $entryNodeIdSerialized, string $filterSerialized = null, PyStringNode $expectedTree = null, string $withTags = null): void
    {
        $entryNodeAggregateId = NodeAggregateId::fromString($entryNodeIdSerialized);
        $filterValues = !empty($filterSerialized) ? json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR) : [];
        $filter = FindSubtreeFilter::create(...$filterValues);

        $result = [];
        $subtreeStack = [];
        $subtree = $this->getCurrentSubgraph()->findSubtree($entryNodeAggregateId, $filter);
        if ($subtree !== null) {
            $subtreeStack[] = $subtree;
        }
        while ($subtreeStack !== []) {
            /** @var Subtree $subtree */
            $subtree = array_shift($subtreeStack);
            $tags = [];
            if ($withTags !== null) {
                $explicitTags = $subtree->node->tags->withoutInherited()->toStringArray();
                sort($explicitTags);
                $inheritedTags = $subtree->node->tags->onlyInherited()->toStringArray();
                sort($inheritedTags);
                $tags = [...array_map(static fn(string $tag) => $tag . '*', $explicitTags), ...$inheritedTags];
            }
            $result[] = str_repeat(' ', $subtree->level) . $subtree->node->aggregateId->value . ($tags !== [] ? ' (' . implode(',', $tags) . ')' : '');
            $subtreeStack = [...$subtree->children, ...$subtreeStack];
        }
        Assert::assertSame($expectedTree?->getRaw() ?? '', implode(chr(10), $result));
    }

    /**
     * @When /^I execute the findDescendantNodes query for entry node aggregate id "(?<entryNodeIdSerialized>[^"]*)"(?: and filter '(?<filterSerialized>[^']*)')? I expect (?:the nodes "(?<expectedNodeIdsSerialized>[^"]*)"|no nodes) to be returned( and the total count to be (?<expectedTotalCount>\d+))?$/
     */
    public function iExecuteTheFindDescendantNodesQueryIExpectTheFollowingNodes(string $entryNodeIdSerialized, string $filterSerialized = '', string $expectedNodeIdsSerialized = '', int $expectedTotalCount = null): void
    {
        $entryNodeAggregateId = NodeAggregateId::fromString($entryNodeIdSerialized);
        $expectedNodeIds = array_filter(explode(',', $expectedNodeIdsSerialized));
        $filterValues = !empty($filterSerialized) ? json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR) : [];
        $filter = FindDescendantNodesFilter::create(...$filterValues);
        $subgraph = $this->getCurrentSubgraph();

        $actualNodeIds = array_map(static fn(Node $node) => $node->aggregateId->value, iterator_to_array($subgraph->findDescendantNodes($entryNodeAggregateId, $filter)));
        Assert::assertSame($expectedNodeIds, $actualNodeIds, 'findDescendantNodes returned an unexpected result');
        $actualCount = $subgraph->countDescendantNodes($entryNodeAggregateId, CountDescendantNodesFilter::fromFindDescendantNodesFilter($filter));
        Assert::assertSame($expectedTotalCount ?? count($expectedNodeIds), $actualCount, 'countDescendantNodes returned an unexpected result');
    }

    /**
     * @When /^I execute the findAncestorNodes query for entry node aggregate id "(?<entryNodeIdSerialized>[^"]*)"(?: and filter '(?<filterSerialized>[^']*)')? I expect (?:the nodes "(?<expectedNodeIdsSerialized>[^"]*)"|no nodes) to be returned( and the total count to be (?<expectedTotalCount>\d+))?$/
     */
    public function iExecuteTheFindAncestorNodesQueryIExpectTheFollowingNodes(string $entryNodeIdSerialized, string $filterSerialized = '', string $expectedNodeIdsSerialized = '', int $expectedTotalCount = null): void
    {
        $entryNodeAggregateId = NodeAggregateId::fromString($entryNodeIdSerialized);
        $expectedNodeIds = array_filter(explode(',', $expectedNodeIdsSerialized));
        $filterValues = !empty($filterSerialized) ? json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR) : [];
        $filter = FindAncestorNodesFilter::create(...$filterValues);
        $subgraph = $this->getCurrentSubgraph();
        $actualNodeIds = array_map(static fn(Node $node) => $node->aggregateId->value, iterator_to_array($subgraph->findAncestorNodes($entryNodeAggregateId, $filter)));
        Assert::assertSame($expectedNodeIds, $actualNodeIds, 'findAncestorNodes returned an unexpected result');
        $actualCount = $subgraph->countAncestorNodes($entryNodeAggregateId, CountAncestorNodesFilter::fromFindAncestorNodesFilter($filter));
        Assert::assertSame($expectedTotalCount ?? count($expectedNodeIds), $actualCount, 'countAncestorNodes returned an unexpected result');
    }

    /**
     * @When /^I execute the findClosestNode query for entry node aggregate id "(?<entryNodeIdSerialized>[^"]*)"(?: and filter '(?<filterSerialized>[^']*)')? I expect (?:the node "(?<expectedNodeId>[^"]*)"|no node) to be returned?$/
     */
    public function iExecuteTheFindClosestNodeQueryIExpectTheFollowingNodes(string $entryNodeIdSerialized, string $filterSerialized = '', string $expectedNodeId = null): void
    {
        $entryNodeAggregateId = NodeAggregateId::fromString($entryNodeIdSerialized);
        $filterValues = !empty($filterSerialized) ? json_decode($filterSerialized, true, 512, JSON_THROW_ON_ERROR) : [];
        $filter = FindClosestNodeFilter::create(...$filterValues);
        $subgraph = $this->getCurrentSubgraph();
        $actualNodeId = $subgraph->findClosestNode($entryNodeAggregateId, $filter)?->aggregateId->value;
        Assert::assertSame($expectedNodeId, $actualNodeId, 'findClosestNode returned an unexpected result');
    }

    /**
     * @When I execute the countNodes query I expect the result to be :expectedResult
     */
    public function iExecuteTheCountNodesQueryIExpectTheFollowingResult(int $expectedResult): void
    {
        Assert::assertSame($expectedResult, $this->getCurrentSubgraph()->countNodes());
    }

    /**
     * @Then I expect the node :nodeIdSerialized to have the following timestamps:
     */
    public function iExpectTheNodeToHaveTheFollowingTimestamps(string $nodeIdSerialized, TableNode $expectedTimestampsTable): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($nodeIdSerialized);
        $expectedTimestamps = array_map(static fn (string $timestamp) => $timestamp === '' ? null : \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $timestamp), $expectedTimestampsTable->getHash()[0]);

        $node = $this->getCurrentSubgraph()->findNodeById($nodeAggregateId);
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


    /**
     * @When I execute the findRootNodeByType query for node type :serializedNodeTypeName I expect no node to be returned
     * @When I execute the findRootNodeByType query for node type :serializedNodeTypeName I expect the node :serializedExpectedNodeId to be returned
     */
    public function iExecuteTheFindRootNodeByTypeQueryIExpectTheFollowingNodes(string $serializedNodeTypeName, string $serializedExpectedNodeId = null): void
    {
        $expectedNodeAggregateId = $serializedExpectedNodeId !== null
            ? NodeAggregateId::fromString($serializedExpectedNodeId)
            : null;

        $actualNode = $this->getCurrentSubgraph()->findRootNodeByType(NodeTypeName::fromString($serializedNodeTypeName));
        Assert::assertSame($actualNode?->aggregateId->value, $expectedNodeAggregateId?->value);
    }
}
