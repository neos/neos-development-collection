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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\Common\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Feature\NodeCreation\Command\CreateNodeAggregateWithNodeAndSerializedProperties;
use Neos\ContentRepository\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Feature\Common\NodeAggregateIdentifiersByNodePaths;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Feature\Common\PropertyValuesToWrite;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\EventStore\Model\Event\StreamName;

/**
 * The node creation trait for behavioral tests
 */
trait NodeCreation
{
    abstract protected function getContentRepository(): ContentRepository;

    abstract protected function getCurrentContentStreamIdentifier(): ?ContentStreamIdentifier;

    abstract protected function getCurrentDimensionSpacePoint(): ?DimensionSpacePoint;

    abstract protected function getCurrentUserIdentifier(): ?UserIdentifier;

    abstract protected function deserializeProperties(array $properties): PropertyValuesToWrite;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    abstract protected function publishEvent(string $eventType, StreamName $streamName, array $eventPayload): void;

    /**
     * @When /^the command CreateRootNodeAggregateWithNode is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws ContentStreamDoesNotExistYet
     * @throws \Exception
     */
    public function theCommandCreateRootNodeAggregateWithNodeIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $contentStreamIdentifier = isset($commandArguments['contentStreamIdentifier'])
            ? ContentStreamIdentifier::fromString($commandArguments['contentStreamIdentifier'])
            : $this->getCurrentContentStreamIdentifier();
        $initiatingUserIdentifier = isset($commandArguments['initiatingUserIdentifier'])
            ? UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
            : $this->getCurrentUserIdentifier();
        $nodeAggregateIdentifier = NodeAggregateIdentifier::fromString($commandArguments['nodeAggregateIdentifier']);

        $command = new CreateRootNodeAggregateWithNode(
            $contentStreamIdentifier,
            $nodeAggregateIdentifier,
            NodeTypeName::fromString($commandArguments['nodeTypeName']),
            $initiatingUserIdentifier
        );

        $this->lastCommandOrEventResult = $this->getContentRepository()->handle($command);
        $this->rootNodeAggregateIdentifier = $nodeAggregateIdentifier;
    }

    /**
     * @When /^the command CreateRootNodeAggregateWithNode is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     */
    public function theCommandCreateRootNodeAggregateWithNodeIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable)
    {
        try {
            $this->theCommandCreateRootNodeAggregateWithNodeIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @Given /^the event RootNodeAggregateWithNodeWasCreated was published with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theEventRootNodeAggregateWithNodeWasCreatedWasPublishedToStreamWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (!isset($eventPayload['contentStreamIdentifier'])) {
            $eventPayload['contentStreamIdentifier'] = (string)$this->getCurrentContentStreamIdentifier();
        }
        if (!isset($eventPayload['initiatingUserIdentifier'])) {
            $eventPayload['initiatingUserIdentifier'] = (string)$this->getCurrentUserIdentifier();
        }
        $contentStreamIdentifier = ContentStreamIdentifier::fromString($eventPayload['contentStreamIdentifier']);
        $nodeAggregateIdentifier = NodeAggregateIdentifier::fromString($eventPayload['nodeAggregateIdentifier']);
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier);

        $this->publishEvent('RootNodeAggregateWithNodeWasCreated', $streamName->getEventStreamName(), $eventPayload);
        $this->rootNodeAggregateIdentifier = $nodeAggregateIdentifier;
    }

    /**
     * @When /^the command CreateNodeAggregateWithNode is executed with payload:$/
     * @param TableNode $payloadTable
     */
    public function theCommandCreateNodeAggregateWithNodeIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $contentStreamIdentifier = isset($commandArguments['contentStreamIdentifier'])
            ? ContentStreamIdentifier::fromString($commandArguments['contentStreamIdentifier'])
            : $this->getCurrentContentStreamIdentifier();
        $originDimensionSpacePoint = isset($commandArguments['originDimensionSpacePoint'])
            ? OriginDimensionSpacePoint::fromArray($commandArguments['originDimensionSpacePoint'])
            : OriginDimensionSpacePoint::fromDimensionSpacePoint($this->getCurrentDimensionSpacePoint());
        $userIdentifier = isset($commandArguments['initiatingUserIdentifier'])
            ? UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
            : $this->getCurrentUserIdentifier();

        $command = new CreateNodeAggregateWithNode(
            $contentStreamIdentifier,
            NodeAggregateIdentifier::fromString($commandArguments['nodeAggregateIdentifier']),
            NodeTypeName::fromString($commandArguments['nodeTypeName']),
            $originDimensionSpacePoint,
            $userIdentifier,
            NodeAggregateIdentifier::fromString($commandArguments['parentNodeAggregateIdentifier']),
            isset($commandArguments['succeedingSiblingNodeAggregateIdentifier'])
                ? NodeAggregateIdentifier::fromString($commandArguments['succeedingSiblingNodeAggregateIdentifier'])
                : null,
            isset($commandArguments['nodeName'])
                ? NodeName::fromString($commandArguments['nodeName'])
                : null,
            isset($commandArguments['initialPropertyValues'])
                ? $this->deserializeProperties($commandArguments['initialPropertyValues'])
                : null,
            isset($commandArguments['tetheredDescendantNodeAggregateIdentifiers'])
                ? NodeAggregateIdentifiersByNodePaths::fromArray($commandArguments['tetheredDescendantNodeAggregateIdentifiers'])
                : null
        );

        $this->lastCommandOrEventResult = $this->getContentRepository()->handle($command);
    }

    /**
     * @When /^the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     */
    public function theCommandCreateNodeAggregateWithNodeIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable)
    {
        try {
            $this->theCommandCreateNodeAggregateWithNodeIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @When the following CreateNodeAggregateWithNode commands are executed:
     */
    public function theFollowingCreateNodeAggregateWithNodeCommandsAreExecuted(TableNode $table): void
    {
        foreach ($table->getHash() as $row) {
            $contentStreamIdentifier = isset($row['contentStreamIdentifier'])
                ? ContentStreamIdentifier::fromString($row['contentStreamIdentifier'])
                : $this->getCurrentContentStreamIdentifier();
            $originDimensionSpacePoint = isset($row['originDimensionSpacePoint'])
                ? OriginDimensionSpacePoint::fromJsonString($row['originDimensionSpacePoint'])
                : OriginDimensionSpacePoint::fromDimensionSpacePoint($this->getCurrentDimensionSpacePoint());
            $initiatingUserIdentifier = isset($row['initiatingUserIdentifier'])
                ? UserIdentifier::fromString($row['initiatingUserIdentifier'])
                : $this->getCurrentUserIdentifier();
            $command = new CreateNodeAggregateWithNode(
                $contentStreamIdentifier,
                NodeAggregateIdentifier::fromString($row['nodeAggregateIdentifier']),
                NodeTypeName::fromString($row['nodeTypeName']),
                $originDimensionSpacePoint,
                $initiatingUserIdentifier,
                NodeAggregateIdentifier::fromString($row['parentNodeAggregateIdentifier']),
                isset($row['succeedingSiblingNodeAggregateIdentifier'])
                    ? NodeAggregateIdentifier::fromString($row['succeedingSiblingNodeAggregateIdentifier'])
                    : null,
                isset($row['nodeName'])
                    ? NodeName::fromString($row['nodeName'])
                    : null,
                isset($row['initialPropertyValues'])
                    ? PropertyValuesToWrite::fromJsonString($row['initialPropertyValues'])
                    : null,
                isset($row['tetheredDescendantNodeAggregateIdentifiers'])
                    ? NodeAggregateIdentifiersByNodePaths::fromJsonString($row['tetheredDescendantNodeAggregateIdentifiers'])
                    : null
            );
            $this->lastCommandOrEventResult = $this->getContentRepository()->handle($command);
            $this->theGraphProjectionIsFullyUpToDate();
        }
    }
    /**
     * @When /^the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function theCommandCreateNodeAggregateWithNodeAndSerializedPropertiesIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $contentStreamIdentifier = isset($commandArguments['contentStreamIdentifier'])
            ? ContentStreamIdentifier::fromString($commandArguments['contentStreamIdentifier'])
            : $this->getCurrentContentStreamIdentifier();
        $initiatingUserIdentifier = isset($commandArguments['initiatingUserIdentifier'])
            ? UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
            : $this->getCurrentUserIdentifier();
        $originDimensionSpacePoint = isset($commandArguments['originDimensionSpacePoint'])
            ? OriginDimensionSpacePoint::fromArray($commandArguments['originDimensionSpacePoint'])
            : OriginDimensionSpacePoint::fromDimensionSpacePoint($this->getCurrentDimensionSpacePoint());

        $command = new CreateNodeAggregateWithNodeAndSerializedProperties(
            $contentStreamIdentifier,
            NodeAggregateIdentifier::fromString($commandArguments['nodeAggregateIdentifier']),
            NodeTypeName::fromString($commandArguments['nodeTypeName']),
            $originDimensionSpacePoint,
            $initiatingUserIdentifier,
            NodeAggregateIdentifier::fromString($commandArguments['parentNodeAggregateIdentifier']),
            isset($commandArguments['succeedingSiblingNodeAggregateIdentifier'])
                ? NodeAggregateIdentifier::fromString($commandArguments['succeedingSiblingNodeAggregateIdentifier'])
                : null,
            isset($commandArguments['nodeName'])
                ? NodeName::fromString($commandArguments['nodeName'])
                : null,
            isset($commandArguments['initialPropertyValues'])
                ? SerializedPropertyValues::fromArray($commandArguments['initialPropertyValues'])
                : null,
            isset($commandArguments['tetheredDescendantNodeAggregateIdentifiers'])
                ? NodeAggregateIdentifiersByNodePaths::fromArray($commandArguments['tetheredDescendantNodeAggregateIdentifiers'])
                : null
        );

        $this->lastCommandOrEventResult = $this->getContentRepository()->handle($command);
    }

    /**
     * @When /^the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     */
    public function theCommandCreateNodeAggregateWithNodeAndSerializedPropertiesIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable)
    {
        try {
            $this->theCommandCreateNodeAggregateWithNodeAndSerializedPropertiesIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @Given /^the event NodeAggregateWithNodeWasCreated was published with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theEventNodeAggregateWithNodeWasCreatedWasPublishedToStreamWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (!isset($eventPayload['initialPropertyValues'])) {
            $eventPayload['initialPropertyValues'] = [];
        }
        if (!isset($eventPayload['originDimensionSpacePoint'])) {
            $eventPayload['originDimensionSpacePoint'] = [];
        }
        if (!isset($eventPayload['coveredDimensionSpacePoints'])) {
            $eventPayload['coveredDimensionSpacePoints'] = [[]];
        }
        if (!isset($eventPayload['nodeName'])) {
            $eventPayload['nodeName'] = null;
        }
        if (!isset($eventPayload['initiatingUserIdentifier'])) {
            $eventPayload['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }

        $contentStreamIdentifier = ContentStreamIdentifier::fromString($eventPayload['contentStreamIdentifier']);
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier);

        $this->publishEvent('NodeAggregateWithNodeWasCreated', $streamName->getEventStreamName(), $eventPayload);
    }
}
