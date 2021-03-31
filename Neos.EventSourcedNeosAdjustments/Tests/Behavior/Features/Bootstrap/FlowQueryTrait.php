<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModelInterface;
use Neos\ContentRepository\Intermediary\Domain\ReadModelFactory;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedNeosAdjustments\Eel\FlowQueryOperations\FindOperation;
use PHPUnit\Framework\Assert;

/**
 * FlowQuery trait for Behat feature contexts
 */
trait FlowQueryTrait
{
    /**
     * @var FlowQuery
     */
    protected $currentFlowQuery;

    /**
     * @var ContentGraphInterface
     */
    private ContentGraphInterface $contentGraph;

    /**
     * @var ReadModelFactory
     */
    private $readModelFactory;

    /**
     * @var ContentStreamIdentifier
     */
    private ?ContentStreamIdentifier $contentStreamIdentifier = null;

    /**
     * @var DimensionSpacePoint
     */
    private ?DimensionSpacePoint $dimensionSpacePoint = null;

    /**
     * @When /^I have a FlowQuery with node "([^"]*)"$/
     * @param string $serializedNodeAggregateIdentifier
     * @throws \Neos\Eel\Exception
     */
    public function iHaveAFlowQueryWithNode(string $serializedNodeAggregateIdentifier)
    {
        $subgraph = $this->contentGraph->getSubgraphByIdentifier(
            $this->contentStreamIdentifier,
            $this->dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );
        $nodeAggregateIdentifier = NodeAggregateIdentifier::fromString($serializedNodeAggregateIdentifier);
        $node = $this->readModelFactory->createReadModel(
            $subgraph->findNodeByNodeAggregateIdentifier($nodeAggregateIdentifier),
            $subgraph
        );
        $this->currentFlowQuery = new FlowQuery([$node]);
    }

    /**
     * @When /^I call FlowQuery operation "([^"]*)" with argument "([^"]*)"$/
     * @param string $operationName
     * @param string $argument
     * @throws \Neos\Eel\Exception
     * @throws \Neos\Eel\FlowQuery\FizzleException
     * @throws \Neos\Eel\FlowQuery\FlowQueryException
     */
    public function iCallFlowQueryOperationWithArgument(string $operationName, string $argument)
    {
        switch ($operationName) {
            case 'find':
                $operation = new FindOperation();
                $operation->evaluate($this->currentFlowQuery, [$argument]);
            break;
            default:
                throw new \InvalidArgumentException('given FlowQuery operation ' . $operationName . ' is currently not supported in test cases');
        }
    }

    /**
     * @When /^I expect a node identified by aggregate identifier "([^"]*)" to exist in the FlowQuery context$/
     * @param string $serializedExpectedNodeAggregateIdentifier
     */
    public function iExpectANodeIdentifiedByAggregateIdentifierToExistInTheFlowQueryContext(string $serializedExpectedNodeAggregateIdentifier)
    {
        $expectedNodeAggregateIdentifier = NodeAggregateIdentifier::fromString($serializedExpectedNodeAggregateIdentifier);
        $expectationMet = false;
        foreach ($this->currentFlowQuery->getContext() as $node) {
            /** @var NodeBasedReadModelInterface $node */
            if ($node->getNodeAggregateIdentifier()->equals($expectedNodeAggregateIdentifier)) {
                $expectationMet = true;
                break;
            }
        }

        Assert::assertSame(true, $expectationMet);
    }

    /**
     * @When /^I expect the FlowQuery context to consist of exactly (\d+) items?$/
     * @param int $expectedNumberOfItems
     */
    public function iExpectTheFlowQueryContextToConsistOfExactlyNItems(int $expectedNumberOfItems)
    {
        Assert::assertSame($expectedNumberOfItems, count($this->currentFlowQuery->getContext()));
    }
}
