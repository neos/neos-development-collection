<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Tests\Behavior\Features\Bootstrap;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifiers;
use Neos\ContentRepository\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Tests\Behavior\Features\Helper\ContentGraphs;
use Neos\ContentRepository\Tests\Behavior\Features\Helper\NodeAggregatesByAdapter;
use PHPUnit\Framework\Assert;

/**
 * The feature trait to test node aggregates
 */
trait ProjectedNodeAggregateTrait
{
    use CurrentSubgraphTrait;

    protected ?NodeAggregatesByAdapter $currentNodeAggregates = null;

    abstract protected function getAvailableContentGraphs(): ContentGraphs;

    /**
     * @Then /^I expect the node aggregate "([^"]*)" to exist$/
     * @param string $serializedNodeAggregateIdentifier
     * @throws NodeAggregatesTypeIsAmbiguous
     */
    public function iExpectTheNodeAggregateToExist(string $serializedNodeAggregateIdentifier): void
    {
        $nodeAggregateIdentifier = NodeAggregateIdentifier::fromString($serializedNodeAggregateIdentifier);
        $this->initializeCurrentNodeAggregates(function (ContentGraphInterface $contentGraph, string $adapterName) use ($nodeAggregateIdentifier) {
            $currentNodeAggregate = $contentGraph->findNodeAggregateByIdentifier($this->contentStreamIdentifier, $nodeAggregateIdentifier);
            Assert::assertNotNull($currentNodeAggregate, sprintf('Node aggregate "%s" was not found in the current content stream "%s" in adapter "%s".', $nodeAggregateIdentifier, $this->contentStreamIdentifier, $adapterName));
            return $currentNodeAggregate;
        });
    }

    protected function initializeCurrentNodeAggregates(callable $query): void
    {
        $currentNodeAggregates = [];
        foreach ($this->getActiveContentGraphs() as $adapterName => $graph) {
            $currentNodeAggregates[$adapterName] = $query($graph, $adapterName);
        }

        $this->currentNodeAggregates = new NodeAggregatesByAdapter($currentNodeAggregates);
    }

    /**
     * @Then /^I expect this node aggregate to occupy dimension space points (.*)$/
     * @param string $serializedExpectedOriginDimensionSpacePoints
     */
    public function iExpectThisNodeAggregateToOccupyDimensionSpacePoints(string $serializedExpectedOriginDimensionSpacePoints): void
    {
        $expectedOccupation = OriginDimensionSpacePointSet::fromJsonString($serializedExpectedOriginDimensionSpacePoints);
        $this->assertOnCurrentNodeAggregates(function (NodeAggregate $nodeAggregate, string $adapterName) use ($expectedOccupation) {
            Assert::assertEquals(
                $expectedOccupation,
                $nodeAggregate->occupiedDimensionSpacePoints,
                'Node aggregate origins do not match in adapter "' . $adapterName . '". Expected: ' .
                $expectedOccupation . ', actual: ' . $nodeAggregate->occupiedDimensionSpacePoints
            );
        });
    }

    /**
     * @Then /^I expect this node aggregate to cover dimension space points (.*)$/
     * @param string $serializedCoveredDimensionSpacePointSet
     */
    public function iExpectThisNodeAggregateToCoverDimensionSpacePoints(string $serializedCoveredDimensionSpacePointSet): void
    {
        $expectedCoverage = DimensionSpacePointSet::fromJsonString($serializedCoveredDimensionSpacePointSet);
        $this->assertOnCurrentNodeAggregates(function (NodeAggregate $nodeAggregate, string $adapterName) use ($expectedCoverage) {
            Assert::assertEquals(
                $expectedCoverage,
                $nodeAggregate->coveredDimensionSpacePoints,
                'Expected node aggregate coverage ' . $expectedCoverage . ', got ' . $nodeAggregate->coveredDimensionSpacePoints . ' in adapter "' . $adapterName . '"'
            );
        });
    }

    /**
     * @Then /^I expect this node aggregate to disable dimension space points (.*)$/
     * @param string $serializedExpectedDisabledDimensionSpacePoints
     */
    public function iExpectThisNodeAggregateToDisableDimensionSpacePoints(string $serializedExpectedDisabledDimensionSpacePoints): void
    {
        $expectedDisabledDimensionSpacePoints = DimensionSpacePointSet::fromJsonString($serializedExpectedDisabledDimensionSpacePoints);
        $this->assertOnCurrentNodeAggregates(function (NodeAggregate $nodeAggregate, string $adapterName) use ($expectedDisabledDimensionSpacePoints) {
            Assert::assertEquals(
                $expectedDisabledDimensionSpacePoints,
                $nodeAggregate->disabledDimensionSpacePoints,
                'Expected disabled dimension space point set ' . $expectedDisabledDimensionSpacePoints . ', got ' .
                $nodeAggregate->disabledDimensionSpacePoints . ' in adapter "' . $adapterName . '"'
            );
        });
    }

    /**
     * @Then /^I expect this node aggregate to be classified as "([^"]*)"$/
     * @param string $serializedExpectedClassification
     */
    public function iExpectThisNodeAggregateToBeClassifiedAs(string $serializedExpectedClassification): void
    {
        $expectedClassification = NodeAggregateClassification::from($serializedExpectedClassification);
        $this->assertOnCurrentNodeAggregates(function (NodeAggregate $nodeAggregate, string $adapterName) use ($expectedClassification) {
            Assert::assertTrue(
                $expectedClassification->equals($nodeAggregate->classification),
                'Node aggregate classifications do not match in adapter "' . $adapterName . '". Expected "' .
                $expectedClassification->value . '", got "' . $nodeAggregate->classification->value . '".'
            );
        });
    }

    /**
     * @Then /^I expect this node aggregate to be of type "([^"]*)"$/
     * @param string $serializedExpectedNodeTypeName
     */
    public function iExpectThisNodeAggregateToBeOfType(string $serializedExpectedNodeTypeName): void
    {
        $expectedNodeTypeName = NodeTypeName::fromString($serializedExpectedNodeTypeName);
        $this->assertOnCurrentNodeAggregates(function (NodeAggregate $nodeAggregate, string $adapterName) use ($expectedNodeTypeName) {
            Assert::assertSame(
                (string)$expectedNodeTypeName,
                (string)$nodeAggregate->nodeTypeName,
                'Node types do not match in adapter "' . $adapterName . '". Expected "' . $expectedNodeTypeName . '", got "' . $nodeAggregate->nodeTypeName . '".'
            );
        });
    }

    /**
     * @Then /^I expect this node aggregate to be unnamed$/
     */
    public function iExpectThisNodeAggregateToBeUnnamed(): void
    {
        $this->assertOnCurrentNodeAggregates(function (NodeAggregate $nodeAggregate, string $adapterName) {
            Assert::assertNull($nodeAggregate->nodeName, 'Did not expect node name for adapter "' . $adapterName . '"');
        });
    }

    /**
     * @Then /^I expect this node aggregate to be named "([^"]*)"$/
     * @param string $serializedExpectedNodeName
     */
    public function iExpectThisNodeAggregateToHaveTheName(string $serializedExpectedNodeName): void
    {
        $expectedNodeName = NodeName::fromString($serializedExpectedNodeName);
        $this->assertOnCurrentNodeAggregates(function (NodeAggregate $nodeAggregate, string $adapterName) use ($expectedNodeName) {
            Assert::assertSame((string)$expectedNodeName, (string)$nodeAggregate->nodeName, 'Node names do not match in adapter "' . $adapterName . '", expected "' . $expectedNodeName . '", got "' . $nodeAggregate->nodeName . '".');
        });
    }

    /**
     * @Then /^I expect this node aggregate to have no parent node aggregates$/
     */
    public function iExpectThisNodeAggregateToHaveNoParentNodeAggregates(): void
    {
        $this->assertOnCurrentNodeAggregates(function (NodeAggregate $nodeAggregate, string $adapterName) {
            Assert::assertEmpty(
                iterator_to_array($this->getActiveContentGraphs()[$adapterName]->findParentNodeAggregates(
                    $nodeAggregate->contentStreamIdentifier,
                    $nodeAggregate->nodeAggregateIdentifier
                )),
                'Did not expect parent node aggregates in adapter "' . $adapterName . '".'
            );
        });
    }

    /**
     * @Then /^I expect this node aggregate to have the parent node aggregates (.*)$/
     * @param string $serializedExpectedNodeAggregateIdentifiers
     */
    public function iExpectThisNodeAggregateToHaveTheParentNodeAggregates(string $serializedExpectedNodeAggregateIdentifiers): void
    {
        $expectedNodeAggregateIdentifiers = NodeAggregateIdentifiers::fromJsonString($serializedExpectedNodeAggregateIdentifiers);
        $this->assertOnCurrentNodeAggregates(function (NodeAggregate $nodeAggregate, string $adapterName) use ($expectedNodeAggregateIdentifiers) {
            $expectedDiscriminators = array_values(array_map(function (NodeAggregateIdentifier $nodeAggregateIdentifier) {
                return $this->contentStreamIdentifier . ';' . $nodeAggregateIdentifier;
            }, $expectedNodeAggregateIdentifiers->getIterator()->getArrayCopy()));
            $actualDiscriminators = array_values(array_map(function (NodeAggregate $parentNodeAggregate) {
                return $parentNodeAggregate->contentStreamIdentifier . ';' . $parentNodeAggregate->nodeAggregateIdentifier;
            }, iterator_to_array(
                $this->getActiveContentGraphs()[$adapterName]->findParentNodeAggregates(
                    $nodeAggregate->contentStreamIdentifier,
                    $nodeAggregate->nodeAggregateIdentifier
                )
            )));
            Assert::assertSame(
                $expectedDiscriminators,
                $actualDiscriminators,
                'Parent node aggregate identifiers do not match in adapter "' . $adapterName . '", expected ' . json_encode($expectedDiscriminators) . ', got ' . json_encode($actualDiscriminators)
            );
        });
    }

    /**
     * @Then /^I expect this node aggregate to have no child node aggregates$/
     */
    public function iExpectThisNodeAggregateToHaveNoChildNodeAggregates(): void
    {
        $this->assertOnCurrentNodeAggregates(function (NodeAggregate $nodeAggregate, string $adapterName) {
            Assert::assertEmpty(
                iterator_to_array($this->getActiveContentGraphs()[$adapterName]->findChildNodeAggregates(
                    $nodeAggregate->contentStreamIdentifier,
                    $nodeAggregate->nodeAggregateIdentifier
                )),
                'No child node aggregates were expected in adapter "' . $adapterName . '".'
            );
        });
    }

    /**
     * @Then /^I expect this node aggregate to have the child node aggregates (.*)$/
     * @param string $serializedExpectedNodeAggregateIdentifiers
     */
    public function iExpectThisNodeAggregateToHaveTheChildNodeAggregates(string $serializedExpectedNodeAggregateIdentifiers): void
    {
        $expectedNodeAggregateIdentifiers = NodeAggregateIdentifiers::fromJsonString($serializedExpectedNodeAggregateIdentifiers);
        $this->assertOnCurrentNodeAggregates(function (NodeAggregate $nodeAggregate, string $adapterName) use ($expectedNodeAggregateIdentifiers) {
            $expectedDiscriminators = array_values(array_map(function (NodeAggregateIdentifier $nodeAggregateIdentifier) {
                return $this->contentStreamIdentifier . ':' . $nodeAggregateIdentifier;
            }, $expectedNodeAggregateIdentifiers->getIterator()->getArrayCopy()));
            $actualDiscriminators = array_values(array_map(function (NodeAggregate $parentNodeAggregate) {
                return $parentNodeAggregate->contentStreamIdentifier . ':' . $parentNodeAggregate->nodeAggregateIdentifier;
            }, iterator_to_array($this->getActiveContentGraphs()[$adapterName]->findChildNodeAggregates(
                $nodeAggregate->contentStreamIdentifier,
                $nodeAggregate->nodeAggregateIdentifier
            ))));

            Assert::assertSame(
                $expectedDiscriminators,
                $actualDiscriminators,
                'Child node aggregate identifiers do not match in adapter "' . $adapterName . '", expected ' . json_encode($expectedDiscriminators) . ', got ' . json_encode($actualDiscriminators)
            );
        });
    }

    protected function assertOnCurrentNodeAggregates(callable $assertions): void
    {
        $this->expectCurrentNodeAggregates();
        foreach ($this->currentNodeAggregates as $adapterName => $currentNode) {
            $assertions($currentNode, $adapterName);
        }
    }

    protected function expectCurrentNodeAggregates(): void
    {
        foreach ($this->currentNodeAggregates as $adapterName => $currentNodeAggregate) {
            Assert::assertNotNull($currentNodeAggregate, 'No current node aggregate present for adapter "' . $adapterName . '"');
        }
    }
}
