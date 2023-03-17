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
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\EventStore\Model\Event\StreamName;
use PHPUnit\Framework\Assert;

/**
 * The node modification trait for behavioral tests
 */
trait NodeModification
{
    abstract protected function getContentRepository(): ContentRepository;

    abstract protected function getCurrentContentStreamId(): ?ContentStreamId;

    abstract protected function getCurrentDimensionSpacePoint(): ?DimensionSpacePoint;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    abstract protected function publishEvent(string $eventType, StreamName $streamName, array $eventPayload): void;

    /**
     * @When /^the command SetNodeProperties is executed with payload:$/
     * @param TableNode $payloadTable
     */
    public function theCommandSetPropertiesIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        if (!isset($commandArguments['contentStreamId'])) {
            $commandArguments['contentStreamId'] = (string)$this->getCurrentContentStreamId();
        }
        if (!isset($commandArguments['originDimensionSpacePoint'])) {
            $commandArguments['originDimensionSpacePoint'] = $this->getCurrentDimensionSpacePoint()->jsonSerialize();
        }

        $command = new SetNodeProperties(
            ContentStreamId::fromString($commandArguments['contentStreamId']),
            NodeAggregateId::fromString($commandArguments['nodeAggregateId']),
            OriginDimensionSpacePoint::fromArray($commandArguments['originDimensionSpacePoint']),
            $this->deserializeProperties($commandArguments['propertyValues']),
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
        if (!isset($eventPayload['contentStreamId'])) {
            $eventPayload['contentStreamId'] = (string)$this->getCurrentContentStreamId();
        }
        if (!isset($eventPayload['originDimensionSpacePoint'])) {
            $eventPayload['originDimensionSpacePoint'] = json_encode($this->getCurrentDimensionSpacePoint());
        }
        $contentStreamId = ContentStreamId::fromString($eventPayload['contentStreamId']);
        $streamName = ContentStreamEventStreamName::fromContentStreamId(
            $contentStreamId
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
