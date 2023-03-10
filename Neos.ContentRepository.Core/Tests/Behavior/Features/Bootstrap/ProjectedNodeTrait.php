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

use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Helper\ContentGraphs;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Helper\ContentSubgraphs;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Helper\NodeDiscriminator;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Helper\NodeDiscriminators;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Helper\NodesByAdapter;
use Neos\ContentRepository\Core\Tests\Behavior\Fixtures\DayOfWeek;
use Neos\ContentRepository\Core\Tests\Behavior\Fixtures\PostalAddress;
use PHPUnit\Framework\Assert;

/**
 * The feature trait to test projected nodes
 */
trait ProjectedNodeTrait
{
    use CurrentSubgraphTrait;

    protected ?NodesByAdapter $currentNodes = null;

    abstract protected function getAvailableContentGraphs(): ContentGraphs;

    abstract protected function getCurrentSubgraphs(): ContentSubgraphs;

    abstract protected function getRootNodeAggregateId(): ?NodeAggregateId;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    protected function getCurrentNodes(): ?NodesByAdapter
    {
        return $this->currentNodes;
    }

    /**
     * @When /^I go to the parent node of node aggregate "([^"]*)"$/
     * @param string $serializedNodeAggregateId
     */
    public function iGoToTheParentNodeOfNode(string $serializedNodeAggregateId): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($serializedNodeAggregateId);
        $this->initializeCurrentNodesFromContentSubgraphs(function (ContentSubgraphInterface $subgraph) use ($nodeAggregateId) {
            return $subgraph->findParentNode($nodeAggregateId);
        });
    }

    /**
     * @Then /^I get the node at path "([^"]*)"$/
     * @param string $serializedNodePath
     * @throws \Exception
     */
    public function iGetTheNodeAtPath(string $serializedNodePath): void
    {
        $nodePath = NodePath::fromString($serializedNodePath);
        $this->initializeCurrentNodesFromContentSubgraphs(function (ContentSubgraphInterface $subgraph) use ($nodePath) {
            return $subgraph->findNodeByPath($nodePath, $this->getRootNodeAggregateId());
        });
    }

    /**
     * @Then /^I expect a node identified by (.*) to exist in the content graph$/
     * @param string $serializedNodeDiscriminator
     * @throws \Exception
     */
    public function iExpectANodeIdentifiedByXToExistInTheContentGraph(string $serializedNodeDiscriminator): void
    {
        $nodeDiscriminator = NodeDiscriminator::fromShorthand($serializedNodeDiscriminator);
        $this->initializeCurrentNodesFromContentGraphs(function (ContentGraphInterface $contentGraph, string $adapterName) use ($nodeDiscriminator) {
            $currentNode = $contentGraph->findNodeByIdAndOriginDimensionSpacePoint(
                $nodeDiscriminator->getContentStreamId(),
                $nodeDiscriminator->getNodeAggregateId(),
                $nodeDiscriminator->getOriginDimensionSpacePoint()
            );
            Assert::assertNotNull(
                $currentNode,
                'Node with aggregate id "' . $nodeDiscriminator->getNodeAggregateId()
                . '" and originating in dimension space point "' . $nodeDiscriminator->getOriginDimensionSpacePoint()
                . '" was not found in content stream "' . $nodeDiscriminator->getContentStreamId() . '"'
                . '" in adapter "' . $adapterName . '"'
            );

            return $currentNode;
        });
    }

    /**
     * @Then /^I expect node aggregate identifier "([^"]*)" to lead to node (.*)$/
     * @param string $serializedNodeAggregateId
     * @param string $serializedNodeDiscriminator
     */
    public function iExpectNodeAggregateIdToLeadToNode(
        string $serializedNodeAggregateId,
        string $serializedNodeDiscriminator
    ): void {
        $nodeAggregateId = NodeAggregateId::fromString($serializedNodeAggregateId);
        $expectedDiscriminator = NodeDiscriminator::fromShorthand($serializedNodeDiscriminator);
        $this->initializeCurrentNodesFromContentSubgraphs(function (ContentSubgraphInterface $subgraph, string $adapterName) use ($nodeAggregateId, $expectedDiscriminator) {
            $currentNode = $subgraph->findNodeById($nodeAggregateId);
            Assert::assertNotNull($currentNode, 'No node could be found by node aggregate id "' . $nodeAggregateId . '" in content subgraph "' . $this->dimensionSpacePoint . '@' . $this->contentStreamId . '" and adapter "' . $adapterName . '"');
            $actualDiscriminator = NodeDiscriminator::fromNode($currentNode);
            Assert::assertTrue($expectedDiscriminator->equals($actualDiscriminator), 'Node discriminators do not match. Expected was ' . json_encode($expectedDiscriminator) . ' , given was ' . json_encode($actualDiscriminator) . ' in adapter "' . $adapterName . '"');
            return $currentNode;
        });
    }

    /**
     * @Then /^I expect node aggregate identifier "([^"]*)" to lead to no node$/
     * @param string $serializedNodeAggregateId
     */
    public function iExpectNodeAggregateIdToLeadToNoNode(string $serializedNodeAggregateId): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($serializedNodeAggregateId);
        foreach ($this->getCurrentSubgraphs() as $adapterName => $subgraph) {
            assert($subgraph instanceof ContentSubgraphInterface);
            $nodeByAggregateId = $subgraph->findNodeById($nodeAggregateId);
            if (!is_null($nodeByAggregateId)) {
                Assert::fail(
                    'A node was found by node aggregate id "' . $nodeAggregateId
                    . '" in content subgraph {' . $this->dimensionSpacePoint . ',' . $this->contentStreamId
                    . '} and adapter "' . $adapterName . '"'
                );
            }

        }
    }

    /**
     * @Then /^I expect path "([^"]*)" to lead to node (.*)$/
     * @param string $serializedNodePath
     * @param string $serializedNodeDiscriminator
     * @throws \Exception
     */
    public function iExpectPathToLeadToNode(string $serializedNodePath, string $serializedNodeDiscriminator): void
    {
        if (!$this->getRootNodeAggregateId()) {
            throw new \Exception('ERROR: rootNodeAggregateId needed for running this step. You need to use "the event RootNodeAggregateWithNodeWasCreated was published with payload" to create a root node..');
        }
        $nodePath = NodePath::fromString($serializedNodePath);
        $expectedDiscriminator = NodeDiscriminator::fromShorthand($serializedNodeDiscriminator);
        $this->initializeCurrentNodesFromContentSubgraphs(function (ContentSubgraphInterface $subgraph, string $adapterName) use ($nodePath, $expectedDiscriminator) {
            $currentNode = $subgraph->findNodeByPath($nodePath, $this->getRootNodeAggregateId());
            Assert::assertNotNull($currentNode, 'No node could be found by node path "' . $nodePath . '" in content subgraph "' . $this->dimensionSpacePoint . '@' . $this->contentStreamId . '" and adapter "' . $adapterName . '"');
            $actualDiscriminator = NodeDiscriminator::fromNode($currentNode);
            Assert::assertTrue($expectedDiscriminator->equals($actualDiscriminator), 'Node discriminators do not match. Expected was ' . json_encode($expectedDiscriminator) . ' , given was ' . json_encode($actualDiscriminator) . ' in adapter "' . $adapterName . '"');
            return $currentNode;
        });
    }

    /**
     * @Then /^I expect path "([^"]*)" to lead to no node$/
     * @param string $serializedNodePath
     * @throws \Exception
     */
    public function iExpectPathToLeadToNoNode(string $serializedNodePath): void
    {
        if (!$this->getRootNodeAggregateId()) {
            throw new \Exception('ERROR: rootNodeAggregateId needed for running this step. You need to use "the event RootNodeAggregateWithNodeWasCreated was published with payload" to create a root node..');
        }
        $nodePath = NodePath::fromString($serializedNodePath);
        foreach ($this->getCurrentSubgraphs() as $adapterName => $subgraph) {
            $nodeByPath = $subgraph->findNodeByPath($nodePath, $this->getRootNodeAggregateId());
            Assert::assertNull($nodeByPath, 'A node was found by node path "' . $nodePath . '" in content subgraph "' . $this->dimensionSpacePoint . '@' . $this->contentStreamId . '" and adapter "' . $adapterName . '"');
        }
    }

    /**
     * @Then /^I expect node aggregate identifier "([^"]*)" and node path "([^"]*)" to lead to node (.*)$/
     * @param string $serializedNodeAggregateId
     * @param string $serializedNodePath
     * @param string $serializedNodeDiscriminator
     * @throws \Exception
     */
    public function iExpectNodeAggregateIdAndNodePathToLeadToNode(string $serializedNodeAggregateId, string $serializedNodePath, string $serializedNodeDiscriminator): void
    {
        $this->iExpectNodeAggregateIdToLeadToNode($serializedNodeAggregateId, $serializedNodeDiscriminator);
        $this->iExpectPathToLeadToNode($serializedNodePath, $serializedNodeDiscriminator);
    }

    /**
     * @Then /^I expect node aggregate identifier "([^"]*)" and node path "([^"]*)" to lead to no node$/
     * @param string $serializedNodeAggregateId
     * @param string $serializedNodePath
     * @throws \Exception
     */
    public function iExpectNodeAggregateIdAndNodePathToLeadToNoNode(string $serializedNodeAggregateId, string $serializedNodePath): void
    {
        $this->iExpectNodeAggregateIdToLeadToNoNode($serializedNodeAggregateId);
        $this->iExpectPathToLeadToNoNode($serializedNodePath);
    }

    protected function initializeCurrentNodesFromContentGraphs(callable $query): void
    {
        $currentNodes = [];
        foreach ($this->getActiveContentGraphs() as $adapterName => $graph) {
            $currentNodes[$adapterName] = $query($graph, $adapterName);
        }

        $this->currentNodes = new NodesByAdapter($currentNodes);
    }

    protected function initializeCurrentNodesFromContentSubgraphs(callable $query): void
    {
        $currentNodes = [];
        foreach ($this->getCurrentSubgraphs() as $adapterName => $subgraph) {
            $currentNodes[$adapterName] = $query($subgraph, $adapterName);
        }

        $this->currentNodes = new NodesByAdapter($currentNodes);
    }

    /**
     * @Then /^I expect this node to be classified as "([^"]*)"$/
     */
    public function iExpectThisNodeToBeClassifiedAs(string $serializedExpectedClassification): void
    {
        $expectedClassification = NodeAggregateClassification::from($serializedExpectedClassification);
        $this->assertOnCurrentNodes(function (Node $currentNode, string $adapterName) use ($expectedClassification) {
            Assert::assertTrue(
                $expectedClassification->equals($currentNode->classification),
                'Node was expected to be classified as "' . $expectedClassification->value . '" but is as "' . $currentNode->classification->value . '" in adapter "' . $adapterName . '"'
            );
        });
    }

    /**
     * @Then /^I expect this node to be of type "([^"]*)"$/
     * @param string $serializedExpectedNodeTypeName
     */
    public function iExpectThisNodeToBeOfType(string $serializedExpectedNodeTypeName): void
    {
        $expectedNodeTypeName = NodeTypeName::fromString($serializedExpectedNodeTypeName);
        $this->assertOnCurrentNodes(function (Node $currentNode, string $adapterName) use ($expectedNodeTypeName) {
            $actualNodeTypeName = $currentNode->nodeTypeName;
            Assert::assertSame($expectedNodeTypeName, $actualNodeTypeName, 'Actual node type name "' . $actualNodeTypeName .'" does not match expected "' . $expectedNodeTypeName . '" in adapter "' . $adapterName . '"');
        });
    }

    /**
     * @Then /^I expect this node to be named "([^"]*)"$/
     * @param string $serializedExpectedNodeName
     */
    public function iExpectThisNodeToBeNamed(string $serializedExpectedNodeName): void
    {
        $expectedNodeName = NodeName::fromString($serializedExpectedNodeName);
        $this->assertOnCurrentNodes(function (Node $currentNode, string $adapterName) use ($expectedNodeName) {
            $actualNodeName = $currentNode->nodeName;
            Assert::assertSame((string)$expectedNodeName, (string)$actualNodeName, 'Actual node name "' . $actualNodeName .'" does not match expected "' . $expectedNodeName . '" in adapter "' . $adapterName . '"');
        });
    }

    /**
     * @Then /^I expect this node to be unnamed$/
     */
    public function iExpectThisNodeToBeUnnamed(): void
    {
        $this->assertOnCurrentNodes(function (Node $currentNode, string $adapterName) {
            Assert::assertNull($currentNode->nodeName, 'Node was not expected to be named in adapter "' . $adapterName . '"');
        });
    }

    /**
     * @Then /^I expect this node to have the following properties:$/
     * @param TableNode $expectedProperties
     */
    public function iExpectThisNodeToHaveTheFollowingProperties(TableNode $expectedProperties): void
    {
        $this->assertOnCurrentNodes(function (Node $currentNode, string $adapterName) use ($expectedProperties) {
            $properties = $currentNode->properties;
            foreach ($expectedProperties->getHash() as $row) {
                Assert::assertTrue($properties->offsetExists($row['Key']), 'Property "' . $row['Key'] . '" not found');
                $expectedPropertyValue = $this->resolvePropertyValue($row['Value']);
                $actualPropertyValue = $properties->offsetGet($row['Key']);
                if ($row['Value'] === 'Date:now') {
                    // we accept 10s offset for the projector to be fine
                    Assert::assertLessThan($actualPropertyValue, $expectedPropertyValue->sub(new \DateInterval('PT10S')), 'Node property ' . $row['Key'] . ' does not match. Expected: ' . json_encode($expectedPropertyValue) . '; Actual: ' . json_encode($actualPropertyValue));
                } else {
                    Assert::assertEquals($expectedPropertyValue, $actualPropertyValue, 'Node property ' . $row['Key'] . ' does not match. Expected: ' . json_encode($expectedPropertyValue) . '; Actual: ' . json_encode($actualPropertyValue) . ' in adapter "' . $adapterName . '"');
                }
            }
        });
    }

    private function resolvePropertyValue(string $serializedPropertyValue)
    {
        switch ($serializedPropertyValue) {
            case 'PostalAddress:dummy':
                return PostalAddress::dummy();
            case 'PostalAddress:anotherDummy':
                return PostalAddress::anotherDummy();
            case 'Date:now':
                return new \DateTimeImmutable();
            default:
                if (\str_starts_with($serializedPropertyValue, 'Date:')) {
                    return \DateTimeImmutable::createFromFormat(\DateTimeInterface::W3C, \mb_substr($serializedPropertyValue, 5));
                } elseif (\str_starts_with($serializedPropertyValue, 'URI:')) {
                    return new Uri(\mb_substr($serializedPropertyValue, 4));
                } elseif (\str_starts_with($serializedPropertyValue, 'DayOfWeek:')) {
                    return DayOfWeek::from(\mb_substr($serializedPropertyValue, 10));
                }
        }

        try {
            return \json_decode($serializedPropertyValue, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            // no JSON string, just return the value
            return $serializedPropertyValue;
        }
    }

    /**
     * @Then /^I expect this node to have no properties$/
     */
    public function iExpectThisNodeToHaveNoProperties(): void
    {
        $this->assertOnCurrentNodes(function (Node $currentNode, string $adapterName) {
            $properties = $currentNode->properties;
            $properties = iterator_to_array($properties);
            Assert::assertCount(0, $properties, 'No properties were expected in adapter "' . $adapterName . '"');
        });
    }

    /**
     * @Then /^I expect this node to have the following references:$/
     * @param TableNode $expectedReferences
     * @throws \Exception
     */
    public function iExpectThisNodeToHaveTheFollowingReferences(TableNode $expectedReferences): void
    {
        $this->assertOnCurrentNodes(function (Node $currentNode, string $adapterName) use ($expectedReferences) {
            $actualReferences = $this->getCurrentSubgraphs()[$adapterName]
                ->findReferences($currentNode->nodeAggregateId, FindReferencesFilter::create());

            $this->assertReferencesMatch($expectedReferences, $actualReferences, $adapterName);
        });
    }

    /**
     * @Then /^I expect this node to have no references$/
     * @throws \Exception
     */
    public function iExpectThisNodeToHaveNoReferences(): void
    {
        $this->assertOnCurrentNodes(function (Node $currentNode, string $adapterName) {
            $references = $this->getCurrentSubgraphs()[$adapterName]
                ->findReferences($currentNode->nodeAggregateId, FindReferencesFilter::create());

            Assert::assertCount(0, $references, 'No references were expected in adapter "' . $adapterName . '".');
        });
    }

    /**
     * @Then /^I expect this node to be referenced by:$/
     * @param TableNode $expectedReferences
     * @throws \Exception
     */
    public function iExpectThisNodeToBeReferencedBy(TableNode $expectedReferences): void
    {
        $this->assertOnCurrentNodes(function (Node $currentNode, string $adapterName) use ($expectedReferences) {
            $actualReferences = $this->getCurrentSubgraphs()[$adapterName]
                ->findBackReferences($currentNode->nodeAggregateId, FindBackReferencesFilter::create());

            $this->assertReferencesMatch($expectedReferences, $actualReferences, $adapterName);
        });
    }

    private function assertReferencesMatch(TableNode $expectedReferencesTable, References $actualReferences, string $adapterName): void
    {
        $expectedReferences = $expectedReferencesTable->getHash();
        Assert::assertSame(
            $actualReferences->count(),
            count($expectedReferences),
            'Node reference count does not match. Expected: ' . count($expectedReferences)
                . ', actual: ' . $actualReferences->count() . ' in adapter ' . $adapterName
        );

        foreach ($expectedReferences as $index => $row) {
            Assert::assertSame(
                PropertyName::fromString($row['Name'])->value,
                $actualReferences[$index]->name->value
            );
            $expectedReferenceDiscriminator = NodeDiscriminator::fromShorthand($row['Node']);
            $actualReferenceDiscriminator = NodeDiscriminator::fromNode($actualReferences[$index]->node);
            Assert::assertTrue(
                $expectedReferenceDiscriminator->equals($actualReferenceDiscriminator),
                'Reference discriminator does not match in adapter "' . $adapterName . '".'
                    . ' Expected was ' . json_encode($expectedReferenceDiscriminator)
                    . ', given was ' . json_encode($actualReferenceDiscriminator)
            );

            if (isset($row['Properties'])) {
                $actualProperties = $actualReferences[$index]->properties;
                $rawExpectedProperties = \json_decode($row['Properties'], true, 512, JSON_THROW_ON_ERROR);
                if (is_null($rawExpectedProperties)) {
                    Assert::assertNull(
                        $actualProperties,
                        'Reference properties for reference ' . $index
                            . ' are not null as expected in adapter ' . $adapterName
                    );
                } else {
                    foreach ($rawExpectedProperties as $propertyName => $rawExpectedPropertyValue) {
                        Assert::assertTrue(
                            $actualProperties->offsetExists($propertyName),
                            'Reference property "' . $propertyName . '" not found in adapter "' . $adapterName . '"'
                        );
                        $expectedPropertyValue = $this->resolvePropertyValue($rawExpectedPropertyValue);
                        $actualPropertyValue = $actualProperties->offsetGet($propertyName);
                        if ($rawExpectedPropertyValue === 'Date:now') {
                            // we accept 10s offset for the projector to be fine
                            Assert::assertLessThan(
                                $actualPropertyValue,
                                $expectedPropertyValue->sub(new \DateInterval('PT10S')),
                                'Reference property ' . $propertyName . ' does not match. Expected: '
                                . json_encode($expectedPropertyValue) . '; Actual: '
                                . json_encode($actualPropertyValue) . ' in adapter "' . $adapterName . '"'
                            );
                        } else {
                            Assert::assertEquals(
                                $expectedPropertyValue,
                                $actualPropertyValue,
                                'Reference property ' . $propertyName . ' does not match. Expected: '
                                . json_encode($expectedPropertyValue) . '; Actual: '
                                . json_encode($actualPropertyValue) . ' in adapter "' . $adapterName . '"'
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * @Then /^I expect this node to not be referenced$/
     * @throws \Exception
     */
    public function iExpectThisNodeToNotBeReferenced(): void
    {
        $this->assertOnCurrentNodes(function (Node $currentNode, string $adapterName) {
            $originNodes = $this->getCurrentSubgraphs()[$adapterName]
                ->findBackReferences($currentNode->nodeAggregateId, FindBackReferencesFilter::create());
            Assert::assertCount(0, $originNodes, 'No referencing nodes were expected in adapter "' . $adapterName . '".');
        });
    }

    /**
     * @Then /^I expect this node to be a child of node (.*)$/
     * @param string $serializedParentNodeDiscriminator
     */
    public function iExpectThisNodeToBeTheChildOfNode(string $serializedParentNodeDiscriminator): void
    {
        $expectedParentDiscriminator = NodeDiscriminator::fromShorthand($serializedParentNodeDiscriminator);
        $this->assertOnCurrentNodes(function (Node $currentNode, string $adapterName) use ($expectedParentDiscriminator) {
            $subgraph = $this->getCurrentSubgraphs()[$adapterName];

            $parent = $subgraph->findParentNode($currentNode->nodeAggregateId);
            Assert::assertInstanceOf(Node::class, $parent, 'Parent not found.');
            $actualParentDiscriminator = NodeDiscriminator::fromNode($parent);
            Assert::assertTrue($expectedParentDiscriminator->equals($actualParentDiscriminator), 'Parent discriminator does not match in adapter "' . $adapterName . '". Expected was ' . json_encode($expectedParentDiscriminator) . ', given was ' . json_encode($actualParentDiscriminator));

            $expectedChildDiscriminator = NodeDiscriminator::fromNode($currentNode);
            $child = $subgraph->findChildNodeConnectedThroughEdgeName($parent->nodeAggregateId, $currentNode->nodeName);
            $actualChildDiscriminator = NodeDiscriminator::fromNode($child);
            Assert::assertTrue($expectedChildDiscriminator->equals($actualChildDiscriminator), 'Child discriminator does not match in adapter "' . $adapterName . '". Expected was ' . json_encode($expectedChildDiscriminator) . ', given was ' . json_encode($actualChildDiscriminator));
        });
    }

    /**
     * @Then /^I expect this node to have no parent node$/
     */
    public function iExpectThisNodeToHaveNoParentNode(): void
    {
        $this->assertOnCurrentNodes(function (Node $currentNode, string $adapterName) {
            $parentNode = $this->getCurrentSubgraphs()[$adapterName]->findParentNode($currentNode->nodeAggregateId);
            $unexpectedNodeAggregateId = $parentNode ? $parentNode->nodeAggregateId : '';
            Assert::assertNull($parentNode, 'Parent node ' . $unexpectedNodeAggregateId . ' was found in adapter "' . $adapterName . '", but none was expected.');
        });
    }

    /**
     * @Then /^I expect this node to have the following child nodes:$/
     * @param TableNode $expectedChildNodesTable
     */
    public function iExpectThisNodeToHaveTheFollowingChildNodes(TableNode $expectedChildNodesTable): void
    {
        $this->assertOnCurrentNodes(function (Node $currentNode, string $adapterName) use ($expectedChildNodesTable) {
            $subgraph = $this->getCurrentSubgraphs()[$adapterName];
            $actualChildNodes = [];
            foreach ($subgraph->findChildNodes($currentNode->nodeAggregateId, FindChildNodesFilter::create()) as $actualChildNode) {
                $actualChildNodes[] = $actualChildNode;
            }

            Assert::assertCount(count($expectedChildNodesTable->getHash()), $actualChildNodes, 'ContentSubgraph::findChildNodes: Child node count does not match in adapter "' . $adapterName . '"');

            foreach ($expectedChildNodesTable->getHash() as $index => $row) {
                $expectedNodeName = NodeName::fromString($row['Name']);
                $actualNodeName = $actualChildNodes[$index]->nodeName;
                Assert::assertEquals($expectedNodeName, $actualNodeName, 'ContentSubgraph::findChildNodes: Node name in index ' . $index . ' does not match in adapter "' . $adapterName . '". Expected: "' . $expectedNodeName . '" Actual: "' . $actualNodeName . '"');
                if (isset($row['NodeDiscriminator'])) {
                    $expectedNodeDiscriminator = NodeDiscriminator::fromShorthand($row['NodeDiscriminator']);
                    $actualNodeDiscriminator = NodeDiscriminator::fromNode($actualChildNodes[$index]);
                    Assert::assertTrue($expectedNodeDiscriminator->equals($actualNodeDiscriminator), 'ContentSubgraph::findChildNodes: Node discriminator in index ' . $index . ' does not match in adapter "' . $adapterName . '". Expected: ' . json_encode($expectedNodeDiscriminator->jsonSerialize()). ' Actual: ' . json_encode($actualNodeDiscriminator));
                }
            }
        });
    }

    /**
     * @Then /^I expect this node to have no child nodes$/
     */
    public function iExpectThisNodeToHaveNoChildNodes(): void
    {
        $this->assertOnCurrentNodes(function (Node $currentNode, string $adapterName) {
            $subgraph = $this->getCurrentSubgraphs()[$adapterName];
            $actualChildNodes = $subgraph->findChildNodes($currentNode->nodeAggregateId, FindChildNodesFilter::create());

            Assert::assertEquals(0, count($actualChildNodes), 'ContentSubgraph::findChildNodes returned present child nodes in adapter "' . $adapterName . '"');
        });
    }

    /**
     * @Then /^I expect this node to have the following preceding siblings:$/
     * @param TableNode $expectedPrecedingSiblingsTable
     */
    public function iExpectThisNodeToHaveTheFollowingPrecedingSiblings(TableNode $expectedPrecedingSiblingsTable): void
    {
        $this->assertOnCurrentNodes(function (Node $currentNode, string $adapterName) use ($expectedPrecedingSiblingsTable) {
            $actualSiblings = [];
            foreach ($this->getCurrentSubgraphs()[$adapterName]
                         ->findPrecedingSiblingNodes($currentNode->nodeAggregateId, FindPrecedingSiblingNodesFilter::create())
                     as $actualSibling
            ) {
                $actualSiblings[] = $actualSibling;
            }
            Assert::assertCount(count($expectedPrecedingSiblingsTable->getHash()), $actualSiblings, 'ContentSubgraph::findPrecedingSiblingNodes: Sibling count does not match in adapter "' . $adapterName . '"');
            foreach ($expectedPrecedingSiblingsTable->getHash() as $index => $row) {
                $expectedNodeDiscriminator = NodeDiscriminator::fromShorthand($row['NodeDiscriminator']);
                $actualNodeDiscriminator = NodeDiscriminator::fromNode($actualSiblings[$index]);
                Assert::assertTrue($expectedNodeDiscriminator->equals($actualNodeDiscriminator), 'ContentSubgraph::findPrecedingSiblingNodes: Node discriminator in index ' . $index . ' does not match in adapter "' . $adapterName . '". Expected: ' . json_encode($expectedNodeDiscriminator) . ' Actual: ' . json_encode($actualNodeDiscriminator));
            }
        });
    }

    /**
     * @Then /^I expect this node to have no preceding siblings$/
     */
    public function iExpectThisNodeToHaveNoPrecedingSiblings(): void
    {
        $this->assertOnCurrentNodes(function (Node $currentNode, string $adapterName) {
            $actualSiblings = $this->getCurrentSubgraphs()[$adapterName]
                ->findPrecedingSiblingNodes($currentNode->nodeAggregateId, FindPrecedingSiblingNodesFilter::create());
            Assert::assertCount(0, $actualSiblings, 'ContentSubgraph::findPrecedingSiblingNodes: No siblings were expected in adapter "' . $adapterName . '"');
        });
    }

    /**
     * @Then /^I expect this node to have the following succeeding siblings:$/
     * @param TableNode $expectedSucceedingSiblingsTable
     */
    public function iExpectThisNodeToHaveTheFollowingSucceedingSiblings(TableNode $expectedSucceedingSiblingsTable): void
    {
        $this->assertOnCurrentNodes(function (Node $currentNode, string $adapterName) use ($expectedSucceedingSiblingsTable) {
            $actualSiblings = [];
            foreach (
                $this->getCurrentSubgraphs()[$adapterName]
                    ->findSucceedingSiblingNodes($currentNode->nodeAggregateId, FindSucceedingSiblingNodesFilter::create())
                as $actualSibling
            ) {
                $actualSiblings[] = $actualSibling;
            }
            Assert::assertCount(count($expectedSucceedingSiblingsTable->getHash()), $actualSiblings, 'ContentSubgraph::findSucceedingSiblingNodes: Sibling count does not match in adapter "' . $adapterName . '"');
            foreach ($expectedSucceedingSiblingsTable->getHash() as $index => $row) {
                $expectedNodeDiscriminator = NodeDiscriminator::fromShorthand($row['NodeDiscriminator']);
                $actualNodeDiscriminator = NodeDiscriminator::fromNode($actualSiblings[$index]);
                Assert::assertTrue($expectedNodeDiscriminator->equals($actualNodeDiscriminator), 'ContentSubgraph::findSucceedingSiblingNodes: Node discriminator in index ' . $index . ' does not match in adapter "' . $adapterName . '". Expected: ' . json_encode($expectedNodeDiscriminator) . ' Actual: ' . json_encode($actualNodeDiscriminator));
            }
        });
    }

    /**
     * @Then /^I expect this node to have no succeeding siblings$/
     */
    public function iExpectThisNodeToHaveNoSucceedingSiblings(): void
    {
        $this->assertOnCurrentNodes(function (Node $currentNode, string $adapterName) {
            $actualSiblings = $this->getCurrentSubgraphs()[$adapterName]
                ->findSucceedingSiblingNodes($currentNode->nodeAggregateId, FindSucceedingSiblingNodesFilter::create());
            Assert::assertCount(0, $actualSiblings, 'ContentSubgraph::findSucceedingSiblingNodes: No siblings were expected');
        });
    }

    protected function assertOnCurrentNodes(callable $assertions): void
    {
        $this->expectCurrentNodes();
        foreach ($this->currentNodes as $adapterName => $currentNode) {
            $assertions($currentNode, $adapterName);
        }
    }

    protected function expectCurrentNodes(): void
    {
        Assert::assertNotNull($this->currentNodes, 'No current nodes present');
        foreach ($this->currentNodes as $adapterName => $currentNode) {
            Assert::assertNotNull($currentNode, 'No current node present for adapter "' . $adapterName . '"');
        }
    }
}
