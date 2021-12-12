<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Tests\Behavior\Features\Bootstrap\Features;

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
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetNodeProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\PropertyValuesToWrite;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Tests\Behavior\Features\Helper\ContentSubgraphs;
use PHPUnit\Framework\Assert;

/**
 * The node property modification trait for behavioral tests
 */
trait NodeProperties
{
    abstract protected function getNodeAggregateCommandHandler(): NodeAggregateCommandHandler;

    abstract protected function deserializeProperties(array $properties): PropertyValuesToWrite;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    abstract protected function assertOnCurrentNodes(callable $assertions): void;

    abstract protected function getCurrentSubgraphs(): ContentSubgraphs;

    /**
     * @When /^the command SetNodeProperties is executed with payload:$/
     * @param TableNode $payloadTable
     */
    public function theCommandSetPropertiesIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        if (!isset($commandArguments['initiatingUserIdentifier'])) {
            $commandArguments['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }

        $command = new SetNodeProperties(
            ContentStreamIdentifier::fromString($commandArguments['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($commandArguments['nodeAggregateIdentifier']),
            OriginDimensionSpacePoint::fromArray($commandArguments['originDimensionSpacePoint']),
            $this->deserializeProperties($commandArguments['propertyValues']),
            UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
        );

        $this->lastCommandOrEventResult = $this->getNodeAggregateCommandHandler()
            ->handleSetNodeProperties($command);
    }

    /**
     * @When /^the command SetNodeProperties is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     */
    public function theCommandSetPropertiesIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable)
    {
        try {
            $this->theCommandSetPropertiesIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @Then I expect this node to not have the property :propertyName
     */
    public function iExpectThisNodeToNotHaveTheProperty(string $propertyName)
    {
        $this->assertOnCurrentNodes(function (NodeInterface $currentNode, string $adapterName) use($propertyName) {
            Assert::assertFalse($currentNode->hasProperty($propertyName), 'Node should not exist for adapter ' . $adapterName);
        });
    }

    /**
     * @Then /^I expect the node "([^"]*)" to have the name "([^"]*)"$/
     * @param string $nodeAggregateIdentifier
     * @param string $nodeName
     */
    public function iExpectTheNodeToHaveTheName(string $nodeAggregateIdentifier, string $nodeName)
    {
        foreach ($this->getCurrentSubgraphs() as $adapterName => $subgraph) {
            $node = $subgraph->findNodeByNodeAggregateIdentifier(NodeAggregateIdentifier::fromString($nodeAggregateIdentifier));
            Assert::assertEquals($nodeName, (string)$node->getNodeName(), 'Node Names do not match in adapter ' . $adapterName);
        }
    }
}
