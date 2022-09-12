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

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\NodeAccess\FlowQueryOperations\FindOperation;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Helper\ContentGraphs;
use Neos\Eel\FlowQuery\FlowQuery;
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
     * @var ReadModelFactory
     */
    private $readModelFactory;

    /**
     * @var ContentStreamId
     */
    private ?ContentStreamId $contentStreamIdentifier = null;

    /**
     * @var DimensionSpacePoint
     */
    private ?DimensionSpacePoint $dimensionSpacePoint = null;

    abstract protected function getAvailableContentGraphs(): ContentGraphs;

    /**
     * @When /^I have a FlowQuery with node "([^"]*)"$/
     * @param string $serializedNodeAggregateIdentifier
     * @throws \Neos\Eel\Exception
     */
    public function iHaveAFlowQueryWithNode(string $serializedNodeAggregateIdentifier)
    {
        $subgraph = $this->contentGraph->getSubgraph(
            $this->contentStreamId,
            $this->dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );
        $nodeAggregateIdentifier = NodeAggregateId::fromString($serializedNodeAggregateIdentifier);
        $node = $subgraph->findNodeById($nodeAggregateIdentifier);
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
        $expectedNodeAggregateIdentifier = NodeAggregateId::fromString($serializedExpectedNodeAggregateIdentifier);
        $expectationMet = false;
        foreach ($this->currentFlowQuery->getContext() as $node) {
            /** @var \Neos\ContentRepository\Core\Projection\ContentGraph\Node $node */
            if ($node->nodeAggregateId->equals($expectedNodeAggregateIdentifier)) {
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
