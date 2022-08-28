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
use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\EventStore\Model\Event\StreamName;
use PHPUnit\Framework\Assert;

/**
 * The node modification trait for behavioral tests
 */
trait NodeModification
{
    abstract protected function getContentRepository(): ContentRepository;

    abstract protected function getCurrentContentStreamIdentifier(): ?ContentStreamIdentifier;

    abstract protected function getCurrentDimensionSpacePoint(): ?DimensionSpacePoint;

    abstract protected function getCurrentUserIdentifier(): ?UserIdentifier;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    abstract protected function publishEvent(string $eventType, StreamName $streamName, array $eventPayload): void;

    /**
     * @When /^the command SetNodeProperties is executed with payload:$/
     * @param TableNode $payloadTable
     */
    public function theCommandSetPropertiesIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        if (!isset($commandArguments['initiatingUserIdentifier'])) {
            $commandArguments['initiatingUserIdentifier'] = (string)$this->getCurrentUserIdentifier();
        }
        if (!isset($commandArguments['contentStreamIdentifier'])) {
            $commandArguments['contentStreamIdentifier'] = (string)$this->getCurrentContentStreamIdentifier();
        }
        if (!isset($commandArguments['originDimensionSpacePoint'])) {
            $commandArguments['originDimensionSpacePoint'] = $this->getCurrentDimensionSpacePoint()->jsonSerialize();
        }

        $command = new SetNodeProperties(
            ContentStreamIdentifier::fromString($commandArguments['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($commandArguments['nodeAggregateIdentifier']),
            OriginDimensionSpacePoint::fromArray($commandArguments['originDimensionSpacePoint']),
            $this->deserializeProperties($commandArguments['propertyValues']),
            UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
        );

        $this->lastCommandOrEventResult = $this->getContentRepository()->handle($command);
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
     * @Given /^the event NodePropertiesWereSet was published with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theEventNodePropertiesWereSetWasPublishedWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (!isset($eventPayload['initiatingUserIdentifier'])) {
            $eventPayload['initiatingUserIdentifier'] = (string)$this->getCurrentUserIdentifier();
        }
        if (!isset($eventPayload['contentStreamIdentifier'])) {
            $eventPayload['contentStreamIdentifier'] = (string)$this->getCurrentContentStreamIdentifier();
        }
        if (!isset($eventPayload['originDimensionSpacePoint'])) {
            $eventPayload['originDimensionSpacePoint'] = json_encode($this->getCurrentDimensionSpacePoint());
        }
        $contentStreamIdentifier = ContentStreamIdentifier::fromString($eventPayload['contentStreamIdentifier']);
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $contentStreamIdentifier
        );

        $this->publishEvent('NodePropertiesWereSet', $streamName->getEventStreamName(), $eventPayload);
    }

    /**
     * @Then I expect this node to not have the property :propertyName
     */
    public function iExpectThisNodeToNotHaveTheProperty(string $propertyName)
    {
        $this->assertOnCurrentNodes(function (Node $currentNode, string $adapterName) use ($propertyName) {
            Assert::assertFalse($currentNode->hasProperty($propertyName), 'Node should not exist for adapter ' . $adapterName);
        });
    }
}
