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

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use PHPUnit\Framework\Assert;

/**
 * The feature trait to test node aggregates
 */
trait ProjectedNodeAggregateTrait
{
    use CRTestSuiteRuntimeVariables;

    /**
     * @Then /^I expect the node aggregate "([^"]*)" to exist$/
     * @throws NodeAggregatesTypeIsAmbiguous
     */
    public function iExpectTheNodeAggregateToExist(string $serializedNodeAggregateId): void
    {
        $nodeAggregateId = NodeAggregateId::fromString($serializedNodeAggregateId);
        $this->initializeCurrentNodeAggregate(function (ContentGraphInterface $contentGraph) use ($nodeAggregateId) {
            $currentNodeAggregate = $contentGraph->findNodeAggregateById($this->currentContentStreamId, $nodeAggregateId);
            Assert::assertNotNull($currentNodeAggregate, sprintf('Node aggregate "%s" was not found in the current content stream "%s".', $nodeAggregateId->value, $this->currentContentStreamId->value));
            return $currentNodeAggregate;
        });
    }

    protected function initializeCurrentNodeAggregate(callable $query): void
    {
        $this->currentNodeAggregate = $query($this->currentContentRepository->getContentGraph());
    }

    /**
     * @Then /^I expect this node aggregate to occupy dimension space points (.*)$/
     */
    public function iExpectThisNodeAggregateToOccupyDimensionSpacePoints(string $serializedExpectedOriginDimensionSpacePoints): void
    {
        $expectedOccupation = OriginDimensionSpacePointSet::fromJsonString($serializedExpectedOriginDimensionSpacePoints);
        $this->assertOnCurrentNodeAggregate(function (NodeAggregate $nodeAggregate) use ($expectedOccupation) {
            Assert::assertEquals(
                $expectedOccupation,
                $nodeAggregate->occupiedDimensionSpacePoints,
                'Node aggregate origins do not match. Expected: ' .
                $expectedOccupation->toJson() . ', actual: ' . $nodeAggregate->occupiedDimensionSpacePoints->toJson()
            );
        });
    }

    /**
     * @Then /^I expect this node aggregate to cover dimension space points (.*)$/
     */
    public function iExpectThisNodeAggregateToCoverDimensionSpacePoints(string $serializedCoveredDimensionSpacePointSet): void
    {
        $expectedCoverage = DimensionSpacePointSet::fromJsonString($serializedCoveredDimensionSpacePointSet);
        $this->assertOnCurrentNodeAggregate(function (NodeAggregate $nodeAggregate) use ($expectedCoverage) {
            Assert::assertEquals(
                $expectedCoverage,
                $nodeAggregate->coveredDimensionSpacePoints,
                'Expected node aggregate coverage ' . $expectedCoverage->toJson() . ', got ' . $nodeAggregate->coveredDimensionSpacePoints->toJson()
            );
        });
    }

    /**
     * @Then /^I expect this node aggregate to disable dimension space points (.*)$/
     */
    public function iExpectThisNodeAggregateToDisableDimensionSpacePoints(string $serializedExpectedDisabledDimensionSpacePoints): void
    {
        $expectedDisabledDimensionSpacePoints = DimensionSpacePointSet::fromJsonString($serializedExpectedDisabledDimensionSpacePoints);
        $this->assertOnCurrentNodeAggregate(function (NodeAggregate $nodeAggregate) use ($expectedDisabledDimensionSpacePoints) {
            Assert::assertEquals(
                $expectedDisabledDimensionSpacePoints,
                $nodeAggregate->disabledDimensionSpacePoints,
                'Expected disabled dimension space point set ' . $expectedDisabledDimensionSpacePoints->toJson() . ', got ' .
                $nodeAggregate->disabledDimensionSpacePoints->toJson()
            );
        });
    }

    /**
     * @Then /^I expect this node aggregate to be classified as "([^"]*)"$/
     */
    public function iExpectThisNodeAggregateToBeClassifiedAs(string $serializedExpectedClassification): void
    {
        $expectedClassification = NodeAggregateClassification::from($serializedExpectedClassification);
        $this->assertOnCurrentNodeAggregate(function (NodeAggregate $nodeAggregate) use ($expectedClassification) {
            Assert::assertTrue(
                $expectedClassification->equals($nodeAggregate->classification),
                'Node aggregate classifications do not match. Expected "' .
                $expectedClassification->value . '", got "' . $nodeAggregate->classification->value . '".'
            );
        });
    }

    /**
     * @Then /^I expect this node aggregate to be of type "([^"]*)"$/
     */
    public function iExpectThisNodeAggregateToBeOfType(string $serializedExpectedNodeTypeName): void
    {
        $expectedNodeTypeName = NodeTypeName::fromString($serializedExpectedNodeTypeName);
        $this->assertOnCurrentNodeAggregate(function (NodeAggregate $nodeAggregate) use ($expectedNodeTypeName) {
            Assert::assertSame(
                $expectedNodeTypeName->value,
                $nodeAggregate->nodeTypeName->value,
                'Node types do not match. Expected "' . $expectedNodeTypeName->value . '", got "' . $nodeAggregate->nodeTypeName->value . '".'
            );
        });
    }

    /**
     * @Then /^I expect this node aggregate to be unnamed$/
     */
    public function iExpectThisNodeAggregateToBeUnnamed(): void
    {
        $this->assertOnCurrentNodeAggregate(function (NodeAggregate $nodeAggregate) {
            Assert::assertNull($nodeAggregate->nodeName, 'Did not expect node name');
        });
    }

    /**
     * @Then /^I expect this node aggregate to be named "([^"]*)"$/
     */
    public function iExpectThisNodeAggregateToHaveTheName(string $serializedExpectedNodeName): void
    {
        $expectedNodeName = NodeName::fromString($serializedExpectedNodeName);
        $this->assertOnCurrentNodeAggregate(function (NodeAggregate $nodeAggregate) use ($expectedNodeName) {
            Assert::assertSame($expectedNodeName->value, $nodeAggregate->nodeName->value, 'Node names do not match, expected "' . $expectedNodeName->value . '", got "' . $nodeAggregate->nodeName->value . '".');
        });
    }

    /**
     * @Then /^I expect this node aggregate to have no parent node aggregates$/
     */
    public function iExpectThisNodeAggregateToHaveNoParentNodeAggregates(): void
    {
        $this->assertOnCurrentNodeAggregate(function (NodeAggregate $nodeAggregate) {
            Assert::assertEmpty(
                iterator_to_array($this->currentContentRepository->getContentGraph()->findParentNodeAggregates(
                    $nodeAggregate->contentStreamId,
                    $nodeAggregate->nodeAggregateId
                )),
                'Did not expect parent node aggregates.'
            );
        });
    }

    /**
     * @Then /^I expect this node aggregate to have the parent node aggregates (.*)$/
     */
    public function iExpectThisNodeAggregateToHaveTheParentNodeAggregates(string $serializedExpectedNodeAggregateIds): void
    {
        $expectedNodeAggregateIds = NodeAggregateIds::fromJsonString($serializedExpectedNodeAggregateIds);
        $this->assertOnCurrentNodeAggregate(function (NodeAggregate $nodeAggregate) use ($expectedNodeAggregateIds) {
            $expectedDiscriminators = array_values(array_map(function (NodeAggregateId $nodeAggregateId) {
                return $this->currentContentStreamId->value . ';' . $nodeAggregateId->value;
            }, $expectedNodeAggregateIds->getIterator()->getArrayCopy()));
            $actualDiscriminators = array_values(array_map(
                fn (NodeAggregate $parentNodeAggregate): string
                    => $parentNodeAggregate->contentStreamId->value . ';' . $parentNodeAggregate->nodeAggregateId->value,
                iterator_to_array(
                    $this->currentContentRepository->getContentGraph()->findParentNodeAggregates(
                        $nodeAggregate->contentStreamId,
                        $nodeAggregate->nodeAggregateId
                    )
                )
            ));
            Assert::assertSame(
                $expectedDiscriminators,
                $actualDiscriminators,
                'Parent node aggregate ids do not match, expected ' . json_encode($expectedDiscriminators) . ', got ' . json_encode($actualDiscriminators)
            );
        });
    }

    /**
     * @Then /^I expect this node aggregate to have no child node aggregates$/
     */
    public function iExpectThisNodeAggregateToHaveNoChildNodeAggregates(): void
    {
        $this->assertOnCurrentNodeAggregate(function (NodeAggregate $nodeAggregate) {
            Assert::assertEmpty(
                iterator_to_array($this->currentContentRepository->getContentGraph()->findChildNodeAggregates(
                    $nodeAggregate->contentStreamId,
                    $nodeAggregate->nodeAggregateId
                )),
                'No child node aggregates were expected.'
            );
        });
    }

    /**
     * @Then /^I expect this node aggregate to have the child node aggregates (.*)$/
     * @param string $serializedExpectedNodeAggregateIds
     */
    public function iExpectThisNodeAggregateToHaveTheChildNodeAggregates(string $serializedExpectedNodeAggregateIds): void
    {
        $expectedNodeAggregateIds = NodeAggregateIds::fromJsonString($serializedExpectedNodeAggregateIds);
        $this->assertOnCurrentNodeAggregate(function (NodeAggregate $nodeAggregate) use ($expectedNodeAggregateIds) {
            $expectedDiscriminators = array_values(array_map(
                fn (NodeAggregateId $nodeAggregateId): string => $this->currentContentStreamId->value . ':' . $nodeAggregateId->value,
                iterator_to_array($expectedNodeAggregateIds)
            ));
            $actualDiscriminators = array_values(array_map(
                fn (NodeAggregate $parentNodeAggregate): string
                    => $parentNodeAggregate->contentStreamId->value . ':' . $parentNodeAggregate->nodeAggregateId->value,
                iterator_to_array($this->currentContentRepository->getContentGraph()->findChildNodeAggregates(
                    $nodeAggregate->contentStreamId,
                    $nodeAggregate->nodeAggregateId
                ))
            ));

            Assert::assertSame(
                $expectedDiscriminators,
                $actualDiscriminators,
                'Child node aggregate ids do not match, expected ' . json_encode($expectedDiscriminators) . ', got ' . json_encode($actualDiscriminators)
            );
        });
    }

    protected function assertOnCurrentNodeAggregate(callable $assertions): void
    {
        $this->expectCurrentNodeAggregate();
        $assertions($this->currentNodeAggregate);
    }

    protected function expectCurrentNodeAggregate(): void
    {
        Assert::assertNotNull($this->currentNodeAggregate, 'No current node aggregate present');
    }
}
