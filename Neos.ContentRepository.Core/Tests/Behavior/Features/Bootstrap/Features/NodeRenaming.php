<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Features;

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
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\EventStore\Model\Event\StreamName;
use PHPUnit\Framework\Assert;

/**
 * The node renaming trait for behavioral tests
 */
trait NodeRenaming
{
    abstract protected function getCurrentContentStreamId(): ?ContentStreamId;

    abstract protected function getCurrentDimensionSpacePoint(): ?DimensionSpacePoint;

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
            Assert::assertEquals($nodeName, (string)$node->nodeName, 'Node Names do not match in adapter ' . $adapterName);
        }
    }
}
