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
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\UpdateRootNodeAggregateDimensions;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNodeAndSerializedProperties;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeCreation\Dto\NodeAggregateIdsByNodePaths;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\EventStore\Model\Event\StreamName;

/**
 * The node creation trait for behavioral tests
 */
trait NodeCreation
{
    abstract protected function getContentRepository(): ContentRepository;

    abstract protected function getCurrentContentStreamId(): ?ContentStreamId;

    abstract protected function getCurrentDimensionSpacePoint(): ?DimensionSpacePoint;

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
        $contentStreamId = isset($commandArguments['contentStreamId'])
            ? ContentStreamId::fromString($commandArguments['contentStreamId'])
            : $this->getCurrentContentStreamId();
        $nodeAggregateId = NodeAggregateId::fromString($commandArguments['nodeAggregateId']);

        $command = new CreateRootNodeAggregateWithNode(
            $contentStreamId,
            $nodeAggregateId,
            NodeTypeName::fromString($commandArguments['nodeTypeName']),
        );

        $this->lastCommandOrEventResult = $this->getContentRepository()->handle($command);
        $this->rootNodeAggregateId = $nodeAggregateId;
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
        if (!isset($eventPayload['contentStreamId'])) {
            $eventPayload['contentStreamId'] = (string)$this->getCurrentContentStreamId();
        }
        $contentStreamId = ContentStreamId::fromString($eventPayload['contentStreamId']);
        $nodeAggregateId = NodeAggregateId::fromString($eventPayload['nodeAggregateId']);
        $streamName = ContentStreamEventStreamName::fromContentStreamId($contentStreamId);

        $this->publishEvent('RootNodeAggregateWithNodeWasCreated', $streamName->getEventStreamName(), $eventPayload);
        $this->rootNodeAggregateId = $nodeAggregateId;
    }

    /**
     * @When /^the command UpdateRootNodeAggregateDimensions is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws ContentStreamDoesNotExistYet
     * @throws \Exception
     */
    public function theCommandUpdateRootNodeAggregateDimensionsIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $contentStreamId = isset($commandArguments['contentStreamId'])
            ? ContentStreamId::fromString($commandArguments['contentStreamId'])
            : $this->getCurrentContentStreamId();
        $nodeAggregateId = NodeAggregateId::fromString($commandArguments['nodeAggregateId']);

        $command = new UpdateRootNodeAggregateDimensions(
            $contentStreamId,
            $nodeAggregateId,
        );

        $this->lastCommandOrEventResult = $this->getContentRepository()->handle($command);
        $this->rootNodeAggregateId = $nodeAggregateId;
    }

    /**
     * @When /^the command CreateNodeAggregateWithNode is executed with payload:$/
     * @param TableNode $payloadTable
     */
    public function theCommandCreateNodeAggregateWithNodeIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $contentStreamId = isset($commandArguments['contentStreamId'])
            ? ContentStreamId::fromString($commandArguments['contentStreamId'])
            : $this->getCurrentContentStreamId();
        $originDimensionSpacePoint = isset($commandArguments['originDimensionSpacePoint'])
            ? OriginDimensionSpacePoint::fromArray($commandArguments['originDimensionSpacePoint'])
            : OriginDimensionSpacePoint::fromDimensionSpacePoint($this->getCurrentDimensionSpacePoint());

        $command = new CreateNodeAggregateWithNode(
            $contentStreamId,
            NodeAggregateId::fromString($commandArguments['nodeAggregateId']),
            NodeTypeName::fromString($commandArguments['nodeTypeName']),
            $originDimensionSpacePoint,
            NodeAggregateId::fromString($commandArguments['parentNodeAggregateId']),
            isset($commandArguments['succeedingSiblingNodeAggregateId'])
                ? NodeAggregateId::fromString($commandArguments['succeedingSiblingNodeAggregateId'])
                : null,
            isset($commandArguments['nodeName'])
                ? NodeName::fromString($commandArguments['nodeName'])
                : null,
            isset($commandArguments['initialPropertyValues'])
                ? $this->deserializeProperties($commandArguments['initialPropertyValues'])
                : null,
            isset($commandArguments['tetheredDescendantNodeAggregateIds'])
                ? NodeAggregateIdsByNodePaths::fromArray($commandArguments['tetheredDescendantNodeAggregateIds'])
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
            $contentStreamId = isset($row['contentStreamId'])
                ? ContentStreamId::fromString($row['contentStreamId'])
                : $this->getCurrentContentStreamId();
            $originDimensionSpacePoint = isset($row['originDimensionSpacePoint'])
                ? OriginDimensionSpacePoint::fromJsonString($row['originDimensionSpacePoint'])
                : OriginDimensionSpacePoint::fromDimensionSpacePoint($this->getCurrentDimensionSpacePoint());
            $command = new CreateNodeAggregateWithNode(
                $contentStreamId,
                NodeAggregateId::fromString($row['nodeAggregateId']),
                NodeTypeName::fromString($row['nodeTypeName']),
                $originDimensionSpacePoint,
                NodeAggregateId::fromString($row['parentNodeAggregateId']),
                isset($row['succeedingSiblingNodeAggregateId'])
                    ? NodeAggregateId::fromString($row['succeedingSiblingNodeAggregateId'])
                    : null,
                isset($row['nodeName'])
                    ? NodeName::fromString($row['nodeName'])
                    : null,
                isset($row['initialPropertyValues'])
                    ? $this->parsePropertyValuesJsonString($row['initialPropertyValues'])
                    : null,
                isset($row['tetheredDescendantNodeAggregateIds'])
                    ? NodeAggregateIdsByNodePaths::fromJsonString($row['tetheredDescendantNodeAggregateIds'])
                    : null
            );
            $this->lastCommandOrEventResult = $this->getContentRepository()->handle($command);
            $this->theGraphProjectionIsFullyUpToDate();
        }
    }

    private function parsePropertyValuesJsonString(string $jsonString): PropertyValuesToWrite
    {
        $array = \json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
        return PropertyValuesToWrite::fromArray(
            array_map(
                static fn (mixed $value) => is_array($value) && isset($value['__type']) ? new $value['__type']($value['value']) : $value,
                $array
            )
        );
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
        $contentStreamId = isset($commandArguments['contentStreamId'])
            ? ContentStreamId::fromString($commandArguments['contentStreamId'])
            : $this->getCurrentContentStreamId();
        $originDimensionSpacePoint = isset($commandArguments['originDimensionSpacePoint'])
            ? OriginDimensionSpacePoint::fromArray($commandArguments['originDimensionSpacePoint'])
            : OriginDimensionSpacePoint::fromDimensionSpacePoint($this->getCurrentDimensionSpacePoint());

        $command = new CreateNodeAggregateWithNodeAndSerializedProperties(
            $contentStreamId,
            NodeAggregateId::fromString($commandArguments['nodeAggregateId']),
            NodeTypeName::fromString($commandArguments['nodeTypeName']),
            $originDimensionSpacePoint,
            NodeAggregateId::fromString($commandArguments['parentNodeAggregateId']),
            isset($commandArguments['succeedingSiblingNodeAggregateId'])
                ? NodeAggregateId::fromString($commandArguments['succeedingSiblingNodeAggregateId'])
                : null,
            isset($commandArguments['nodeName'])
                ? NodeName::fromString($commandArguments['nodeName'])
                : null,
            isset($commandArguments['initialPropertyValues'])
                ? SerializedPropertyValues::fromArray($commandArguments['initialPropertyValues'])
                : null,
            isset($commandArguments['tetheredDescendantNodeAggregateIds'])
                ? NodeAggregateIdsByNodePaths::fromArray($commandArguments['tetheredDescendantNodeAggregateIds'])
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

        $contentStreamId = ContentStreamId::fromString($eventPayload['contentStreamId']);
        $streamName = ContentStreamEventStreamName::fromContentStreamId($contentStreamId);

        $this->publishEvent('NodeAggregateWithNodeWasCreated', $streamName->getEventStreamName(), $eventPayload);
    }
}
