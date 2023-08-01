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

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features;

use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use Neos\EventStore\Model\Event\StreamName;
use PHPUnit\Framework\Assert;

/**
 * The node renaming trait for behavioral tests
 */
trait NodeRenaming
{
    use CRTestSuiteRuntimeVariables;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    abstract protected function publishEvent(string $eventType, StreamName $streamName, array $eventPayload): void;

    /**
     * @Then /^I expect the node "([^"]*)" to have the name "([^"]*)"$/
     * @param string $nodeAggregateId
     * @param string $nodeName
     */
    public function iExpectTheNodeToHaveTheName(string $nodeAggregateId, string $nodeName)
    {
        foreach ($this->getCurrentSubgraphs() as $adapterName => $subgraph) {
            assert($subgraph instanceof ContentSubgraphInterface);
            $node = $subgraph->findNodeById(NodeAggregateId::fromString($nodeAggregateId));
            Assert::assertEquals($nodeName, $node->nodeName->value, 'Node Names do not match in adapter ' . $adapterName);
        }
    }
}
