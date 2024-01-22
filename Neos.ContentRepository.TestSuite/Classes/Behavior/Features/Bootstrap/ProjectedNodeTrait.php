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

use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Tests\Behavior\Fixtures\DayOfWeek;
use Neos\ContentRepository\Core\Tests\Behavior\Fixtures\PostalAddress;
use Neos\ContentRepository\Core\Tests\Behavior\Fixtures\PriceSpecification;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\NodeDiscriminator;
use PHPUnit\Framework\Assert;

/**
 * The feature trait to test projected nodes
 */
trait ProjectedNodeTrait
{
    use CRTestSuiteRuntimeVariables;

    abstract protected function getRootNodeAggregateId(): ?NodeAggregateId;

    /**
     * @When /^I go to the parent node of node aggregate "([^"]*)"$/
     * @param string $serializedNodeAggregateId
     */
    public function iGoToTheParentNodeOfNode(string $serializedNodeAggregateId): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($serializedNodeAggregateId);
        $this->initializeCurrentNodeFromContentSubgraph(function (ContentSubgraphInterface $subgraph) use ($nodeAggregateId) {
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
        $this->initializeCurrentNodeFromContentSubgraph(function (ContentSubgraphInterface $subgraph) use ($nodePath) {
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
        $this->initializeCurrentNodeFromContentGraph(function (ContentGraphInterface $contentGraph) use ($nodeDiscriminator) {
            $currentNodeAggregate = $contentGraph->findNodeAggregateById(
                $nodeDiscriminator->contentStreamId,
                $nodeDiscriminator->nodeAggregateId
            );
            Assert::assertTrue(
                $currentNodeAggregate?->occupiesDimensionSpacePoint($nodeDiscriminator->originDimensionSpacePoint),
                'Node with aggregate id "' . $nodeDiscriminator->nodeAggregateId->value
                . '" and originating in dimension space point "' . $nodeDiscriminator->originDimensionSpacePoint->toJson()
                . '" was not found in content stream "' . $nodeDiscriminator->contentStreamId->value . '"'
            );

            return $currentNodeAggregate->getNodeByOccupiedDimensionSpacePoint($nodeDiscriminator->originDimensionSpacePoint);
        });
    }

    /**
     * @Then /^I expect node aggregate identifier "([^"]*)" to lead to node (.*)$/
     */
    public function iExpectNodeAggregateIdToLeadToNode(
        string $serializedNodeAggregateId,
        string $serializedNodeDiscriminator
    ): void {
        $nodeAggregateId = NodeAggregateId::fromString($serializedNodeAggregateId);
        $expectedDiscriminator = NodeDiscriminator::fromShorthand($serializedNodeDiscriminator);
        $this->initializeCurrentNodeFromContentSubgraph(function (ContentSubgraphInterface $subgraph) use ($nodeAggregateId, $expectedDiscriminator) {
            $currentNode = $subgraph->findNodeById($nodeAggregateId);
            Assert::assertNotNull($currentNode, 'No node could be found by node aggregate id "' . $nodeAggregateId->value . '" in content subgraph "' . $this->currentDimensionSpacePoint->toJson() . '@' . $this->currentContentStreamId->value . '"');
            $actualDiscriminator = NodeDiscriminator::fromNode($currentNode);
            Assert::assertTrue($expectedDiscriminator->equals($actualDiscriminator), 'Node discriminators do not match. Expected was ' . json_encode($expectedDiscriminator) . ' , given was ' . json_encode($actualDiscriminator));
            return $currentNode;
        });
    }

    /**
     * @Then /^I expect node aggregate identifier "([^"]*)" to lead to no node$/
     */
    public function iExpectNodeAggregateIdToLeadToNoNode(string $serializedNodeAggregateId): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($serializedNodeAggregateId);
        $nodeByAggregateId = $this->getCurrentSubgraph()->findNodeById($nodeAggregateId);
        if (!is_null($nodeByAggregateId)) {
            Assert::fail(
                'A node was found by node aggregate id "' . $nodeAggregateId->value
                . '" in content subgraph {' . $this->currentDimensionSpacePoint->toJson() . ',' . $this->currentContentStreamId->value . '}'
            );
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
        $this->initializeCurrentNodeFromContentSubgraph(function (ContentSubgraphInterface $subgraph) use ($nodePath, $expectedDiscriminator) {
            $currentNode = $subgraph->findNodeByPath($nodePath, $this->getRootNodeAggregateId());
            Assert::assertNotNull($currentNode, 'No node could be found by node path "' . $nodePath->serializeToString() . '" in content subgraph "' . $this->currentDimensionSpacePoint->toJson() . '@' . $this->currentContentStreamId->value . '"');
            $actualDiscriminator = NodeDiscriminator::fromNode($currentNode);
            Assert::assertTrue($expectedDiscriminator->equals($actualDiscriminator), 'Node discriminators do not match. Expected was ' . json_encode($expectedDiscriminator) . ' , given was ' . json_encode($actualDiscriminator));
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
        $nodeByPath = $this->getCurrentSubgraph()->findNodeByPath($nodePath, $this->getRootNodeAggregateId());
        Assert::assertNull(
            $nodeByPath,
            'A node was found by node path "' . $nodePath->serializeToString()
                . '" in content subgraph "' . $this->currentDimensionSpacePoint->toJson() . '@' . $this->currentContentStreamId->value . '"'
        );
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

    protected function initializeCurrentNodeFromContentGraph(callable $query): void
    {
        $this->currentNode = $query($this->currentContentRepository->getContentGraph());
    }

    protected function initializeCurrentNodeFromContentSubgraph(callable $query): void
    {
        $this->currentNode = $query($this->getCurrentSubgraph());
    }

    /**
     * @Then /^I expect this node to be classified as "([^"]*)"$/
     */
    public function iExpectThisNodeToBeClassifiedAs(string $serializedExpectedClassification): void
    {
        $expectedClassification = NodeAggregateClassification::from($serializedExpectedClassification);
        $this->assertOnCurrentNode(function (Node $currentNode) use ($expectedClassification) {
            Assert::assertTrue(
                $expectedClassification->equals($currentNode->classification),
                'Node was expected to be classified as "' . $expectedClassification->value . '" but is as "' . $currentNode->classification->value . '"'
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
        $this->assertOnCurrentNode(function (Node $currentNode) use ($expectedNodeTypeName) {
            $actualNodeTypeName = $currentNode->nodeTypeName;
            Assert::assertSame($expectedNodeTypeName, $actualNodeTypeName, 'Actual node type name "' . $actualNodeTypeName->value . '" does not match expected "' . $expectedNodeTypeName->value . '"');
        });
    }

    /**
     * @Then /^I expect this node to be named "([^"]*)"$/
     * @param string $serializedExpectedNodeName
     */
    public function iExpectThisNodeToBeNamed(string $serializedExpectedNodeName): void
    {
        $expectedNodeName = NodeName::fromString($serializedExpectedNodeName);
        $this->assertOnCurrentNode(function (Node $currentNode) use ($expectedNodeName) {
            $actualNodeName = $currentNode->nodeName;
            Assert::assertSame($expectedNodeName->value, $actualNodeName->value, 'Actual node name "' . $actualNodeName->value . '" does not match expected "' . $expectedNodeName->value . '"');
        });
    }

    /**
     * @Then /^I expect this node to be unnamed$/
     */
    public function iExpectThisNodeToBeUnnamed(): void
    {
        $this->assertOnCurrentNode(function (Node $currentNode) {
            Assert::assertNull($currentNode->nodeName, 'Node was not expected to be named');
        });
    }

    /**
     * @Then /^I expect this node to have the following properties:$/
     * @param TableNode $expectedProperties
     */
    public function iExpectThisNodeToHaveTheFollowingProperties(TableNode $expectedProperties): void
    {
        $this->assertOnCurrentNode(function (Node $currentNode) use ($expectedProperties) {
            $properties = $currentNode->properties;
            foreach ($expectedProperties->getHash() as $row) {
                Assert::assertTrue($properties->offsetExists($row['Key']), 'Property "' . $row['Key'] . '" not found');
                $expectedPropertyValue = $this->resolvePropertyValue($row['Value']);
                $actualPropertyValue = $properties->offsetGet($row['Key']);
                if ($row['Value'] === 'Date:now') {
                    // we accept 10s offset for the projector to be fine
                    Assert::assertLessThan($actualPropertyValue, $expectedPropertyValue->sub(new \DateInterval('PT10S')), 'Node property ' . $row['Key'] . ' does not match. Expected: ' . json_encode($expectedPropertyValue) . '; Actual: ' . json_encode($actualPropertyValue));
                } else {
                    Assert::assertEquals($expectedPropertyValue, $actualPropertyValue, 'Node property ' . $row['Key'] . ' does not match. Expected: ' . json_encode($expectedPropertyValue) . '; Actual: ' . json_encode($actualPropertyValue));
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
            case 'PriceSpecification:dummy':
                return PriceSpecification::dummy();
            case 'PriceSpecification:anotherDummy':
                return PriceSpecification::anotherDummy();
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
        $this->assertOnCurrentNode(function (Node $currentNode) {
            $properties = $currentNode->properties;
            $properties = iterator_to_array($properties);
            Assert::assertCount(0, $properties, 'No properties were expected');
        });
    }

    /**
     * @Then /^I expect this node to have the following references:$/
     * @throws \Exception
     */
    public function iExpectThisNodeToHaveTheFollowingReferences(TableNode $expectedReferences): void
    {
        $this->assertOnCurrentNode(function (Node $currentNode) use ($expectedReferences) {
            $actualReferences = $this->getCurrentSubgraph()
                ->findReferences($currentNode->nodeAggregateId, FindReferencesFilter::create());

            $this->assertReferencesMatch($expectedReferences, $actualReferences);
        });
    }

    /**
     * @Then /^I expect this node to have no references$/
     * @throws \Exception
     */
    public function iExpectThisNodeToHaveNoReferences(): void
    {
        $this->assertOnCurrentNode(function (Node $currentNode) {
            $references = $this->getCurrentSubgraph()
                ->findReferences($currentNode->nodeAggregateId, FindReferencesFilter::create());

            Assert::assertCount(0, $references, 'No references were expected.');
        });
    }

    /**
     * @Then /^I expect this node to be referenced by:$/
     * @throws \Exception
     */
    public function iExpectThisNodeToBeReferencedBy(TableNode $expectedReferences): void
    {
        $this->assertOnCurrentNode(function (Node $currentNode) use ($expectedReferences) {
            $actualReferences = $this->getCurrentSubgraph()
                ->findBackReferences($currentNode->nodeAggregateId, FindBackReferencesFilter::create());

            $this->assertReferencesMatch($expectedReferences, $actualReferences);
        });
    }

    private function assertReferencesMatch(TableNode $expectedReferencesTable, References $actualReferences): void
    {
        $expectedReferences = $expectedReferencesTable->getHash();
        Assert::assertSame(
            $actualReferences->count(),
            count($expectedReferences),
            'Node reference count does not match. Expected: ' . count($expectedReferences)
                . ', actual: ' . $actualReferences->count()
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
                'Reference discriminator does not match.'
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
                            . ' are not null as expected.'
                    );
                } else {
                    foreach ($rawExpectedProperties as $propertyName => $rawExpectedPropertyValue) {
                        Assert::assertTrue(
                            $actualProperties->offsetExists($propertyName),
                            'Reference property "' . $propertyName . '" not found.'
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
                                . json_encode($actualPropertyValue)
                            );
                        } else {
                            Assert::assertEquals(
                                $expectedPropertyValue,
                                $actualPropertyValue,
                                'Reference property ' . $propertyName . ' does not match. Expected: '
                                . json_encode($expectedPropertyValue) . '; Actual: '
                                . json_encode($actualPropertyValue)
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
        $this->assertOnCurrentNode(function (Node $currentNode) {
            $originNodes = $this->getCurrentSubgraph()
                ->findBackReferences($currentNode->nodeAggregateId, FindBackReferencesFilter::create());
            Assert::assertCount(0, $originNodes, 'No referencing nodes were expected.');
        });
    }

    /**
     * @Then /^I expect this node to be a child of node (.*)$/
     * @param string $serializedParentNodeDiscriminator
     */
    public function iExpectThisNodeToBeTheChildOfNode(string $serializedParentNodeDiscriminator): void
    {
        $expectedParentDiscriminator = NodeDiscriminator::fromShorthand($serializedParentNodeDiscriminator);
        $this->assertOnCurrentNode(function (Node $currentNode) use ($expectedParentDiscriminator) {
            $subgraph = $this->getCurrentSubgraph();

            $parent = $subgraph->findParentNode($currentNode->nodeAggregateId);
            Assert::assertInstanceOf(Node::class, $parent, 'Parent not found.');
            $actualParentDiscriminator = NodeDiscriminator::fromNode($parent);
            Assert::assertTrue($expectedParentDiscriminator->equals($actualParentDiscriminator), 'Parent discriminator does not match. Expected was ' . json_encode($expectedParentDiscriminator) . ', given was ' . json_encode($actualParentDiscriminator));

            $expectedChildDiscriminator = NodeDiscriminator::fromNode($currentNode);
            $child = $subgraph->findNodeByPath($currentNode->nodeName, $parent->nodeAggregateId);
            $actualChildDiscriminator = NodeDiscriminator::fromNode($child);
            Assert::assertTrue($expectedChildDiscriminator->equals($actualChildDiscriminator), 'Child discriminator does not match. Expected was ' . json_encode($expectedChildDiscriminator) . ', given was ' . json_encode($actualChildDiscriminator));
        });
    }

    /**
     * @Then /^I expect this node to have no parent node$/
     */
    public function iExpectThisNodeToHaveNoParentNode(): void
    {
        $this->assertOnCurrentNode(function (Node $currentNode) {
            $parentNode = $this->getCurrentSubgraph()->findParentNode($currentNode->nodeAggregateId);
            $unexpectedNodeAggregateId = $parentNode ? $parentNode->nodeAggregateId : '';
            Assert::assertNull($parentNode, 'Parent node ' . $unexpectedNodeAggregateId . ' was found, but none was expected.');
        });
    }

    /**
     * @Then /^I expect this node to have the following child nodes:$/
     * @param TableNode $expectedChildNodesTable
     */
    public function iExpectThisNodeToHaveTheFollowingChildNodes(TableNode $expectedChildNodesTable): void
    {
        $this->assertOnCurrentNode(function (Node $currentNode) use ($expectedChildNodesTable) {
            $subgraph = $this->getCurrentSubgraph();
            $actualChildNodes = [];
            foreach ($subgraph->findChildNodes($currentNode->nodeAggregateId, FindChildNodesFilter::create()) as $actualChildNode) {
                $actualChildNodes[] = $actualChildNode;
            }

            Assert::assertCount(count($expectedChildNodesTable->getHash()), $actualChildNodes, 'ContentSubgraph::findChildNodes: Child node count does not match');

            foreach ($expectedChildNodesTable->getHash() as $index => $row) {
                $expectedNodeName = NodeName::fromString($row['Name']);
                $actualNodeName = $actualChildNodes[$index]->nodeName;
                Assert::assertTrue($expectedNodeName->equals($actualNodeName), 'ContentSubgraph::findChildNodes: Node name in index ' . $index . ' does not match. Expected: "' . $expectedNodeName->value . '" Actual: "' . $actualNodeName->value . '"');
                if (isset($row['NodeDiscriminator'])) {
                    $expectedNodeDiscriminator = NodeDiscriminator::fromShorthand($row['NodeDiscriminator']);
                    $actualNodeDiscriminator = NodeDiscriminator::fromNode($actualChildNodes[$index]);
                    Assert::assertTrue($expectedNodeDiscriminator->equals($actualNodeDiscriminator), 'ContentSubgraph::findChildNodes: Node discriminator in index ' . $index . ' does not match. Expected: ' . json_encode($expectedNodeDiscriminator->jsonSerialize()) . ' Actual: ' . json_encode($actualNodeDiscriminator));
                }
            }
        });
    }

    /**
     * @Then /^I expect this node to have no child nodes$/
     */
    public function iExpectThisNodeToHaveNoChildNodes(): void
    {
        $this->assertOnCurrentNode(function (Node $currentNode) {
            $subgraph = $this->getCurrentSubgraph();
            $actualChildNodes = $subgraph->findChildNodes($currentNode->nodeAggregateId, FindChildNodesFilter::create());

            Assert::assertEquals(0, count($actualChildNodes), 'ContentSubgraph::findChildNodes returned present child nodes.');
        });
    }

    /**
     * @Then /^I expect this node to have the following preceding siblings:$/
     * @param TableNode $expectedPrecedingSiblingsTable
     */
    public function iExpectThisNodeToHaveTheFollowingPrecedingSiblings(TableNode $expectedPrecedingSiblingsTable): void
    {
        $this->assertOnCurrentNode(function (Node $currentNode) use ($expectedPrecedingSiblingsTable) {
            $actualSiblings = [];
            foreach (
                $this->getCurrentSubgraph()->findPrecedingSiblingNodes(
                    $currentNode->nodeAggregateId,
                    FindPrecedingSiblingNodesFilter::create()
                ) as $actualSibling
            ) {
                $actualSiblings[] = $actualSibling;
            }
            Assert::assertCount(count($expectedPrecedingSiblingsTable->getHash()), $actualSiblings, 'ContentSubgraph::findPrecedingSiblingNodes: Sibling count does not match');
            foreach ($expectedPrecedingSiblingsTable->getHash() as $index => $row) {
                $expectedNodeDiscriminator = NodeDiscriminator::fromShorthand($row['NodeDiscriminator']);
                $actualNodeDiscriminator = NodeDiscriminator::fromNode($actualSiblings[$index]);
                Assert::assertTrue($expectedNodeDiscriminator->equals($actualNodeDiscriminator), 'ContentSubgraph::findPrecedingSiblingNodes: Node discriminator in index ' . $index . ' does not match. Expected: ' . json_encode($expectedNodeDiscriminator) . ' Actual: ' . json_encode($actualNodeDiscriminator));
            }
        });
    }

    /**
     * @Then /^I expect this node to have no preceding siblings$/
     */
    public function iExpectThisNodeToHaveNoPrecedingSiblings(): void
    {
        $this->assertOnCurrentNode(function (Node $currentNode) {
            $actualSiblings = $this->getCurrentSubgraph()
                ->findPrecedingSiblingNodes($currentNode->nodeAggregateId, FindPrecedingSiblingNodesFilter::create());
            Assert::assertCount(0, $actualSiblings, 'ContentSubgraph::findPrecedingSiblingNodes: No siblings were expected');
        });
    }

    /**
     * @Then /^I expect this node to have the following succeeding siblings:$/
     * @param TableNode $expectedSucceedingSiblingsTable
     */
    public function iExpectThisNodeToHaveTheFollowingSucceedingSiblings(TableNode $expectedSucceedingSiblingsTable): void
    {
        $this->assertOnCurrentNode(function (Node $currentNode) use ($expectedSucceedingSiblingsTable) {
            $actualSiblings = [];
            foreach (
                $this->getCurrentSubgraph()->findSucceedingSiblingNodes(
                    $currentNode->nodeAggregateId,
                    FindSucceedingSiblingNodesFilter::create()
                ) as $actualSibling
            ) {
                $actualSiblings[] = $actualSibling;
            }
            Assert::assertCount(count($expectedSucceedingSiblingsTable->getHash()), $actualSiblings, 'ContentSubgraph::findSucceedingSiblingNodes: Sibling count does not match');
            foreach ($expectedSucceedingSiblingsTable->getHash() as $index => $row) {
                $expectedNodeDiscriminator = NodeDiscriminator::fromShorthand($row['NodeDiscriminator']);
                $actualNodeDiscriminator = NodeDiscriminator::fromNode($actualSiblings[$index]);
                Assert::assertTrue($expectedNodeDiscriminator->equals($actualNodeDiscriminator), 'ContentSubgraph::findSucceedingSiblingNodes: Node discriminator in index ' . $index . ' does not match. Expected: ' . json_encode($expectedNodeDiscriminator) . ' Actual: ' . json_encode($actualNodeDiscriminator));
            }
        });
    }

    /**
     * @Then /^I expect this node to have no succeeding siblings$/
     */
    public function iExpectThisNodeToHaveNoSucceedingSiblings(): void
    {
        $this->assertOnCurrentNode(function (Node $currentNode) {
            $actualSiblings = $this->getCurrentSubgraph()
                ->findSucceedingSiblingNodes($currentNode->nodeAggregateId, FindSucceedingSiblingNodesFilter::create());
            Assert::assertCount(0, $actualSiblings, 'ContentSubgraph::findSucceedingSiblingNodes: No siblings were expected');
        });
    }

    protected function assertOnCurrentNode(callable $assertions): void
    {
        $this->expectCurrentNode();
        $assertions($this->currentNode);
    }

    protected function expectCurrentNode(): void
    {
        Assert::assertNotNull($this->currentNode, 'No current node present');
    }
}
