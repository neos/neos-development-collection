<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Features;

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
use Neos\ContentRepository\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\EventStore\Model\Event\StreamName;
use PHPUnit\Framework\Assert;

/**
 * The node renaming trait for behavioral tests
 */
trait NodeRenaming
{
    abstract protected function getCurrentContentStreamIdentifier(): ?ContentStreamIdentifier;

    abstract protected function getCurrentDimensionSpacePoint(): ?DimensionSpacePoint;

    abstract protected function getCurrentUserIdentifier(): ?UserIdentifier;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    abstract protected function publishEvent(string $eventType, StreamName $streamName, array $eventPayload): void;

    /**
     * @Then /^I expect the node "([^"]*)" to have the name "([^"]*)"$/
     * @param string $nodeAggregateIdentifier
     * @param string $nodeName
     */
    public function iExpectTheNodeToHaveTheName(string $nodeAggregateIdentifier, string $nodeName)
    {
        foreach ($this->getCurrentSubgraphs() as $adapterName => $subgraph) {
            $node = $subgraph->findNodeByNodeAggregateIdentifier(NodeAggregateIdentifier::fromString($nodeAggregateIdentifier));
            Assert::assertEquals($nodeName, (string)$node->nodeName, 'Node Names do not match in adapter ' . $adapterName);
        }
    }
}
