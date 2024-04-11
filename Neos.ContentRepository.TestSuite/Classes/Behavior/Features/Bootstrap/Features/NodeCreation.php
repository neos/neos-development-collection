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
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNodeAndSerializedProperties;
use Neos\ContentRepository\Core\Feature\NodeCreation\Dto\NodeAggregateIdsByNodePaths;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\UpdateRootNodeAggregateDimensions;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use Neos\EventStore\Model\Event\StreamName;

/**
 * The node creation trait for behavioral tests
 */
trait NodeCreation
{
    use CRTestSuiteRuntimeVariables;

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
        $workspaceName = isset($commandArguments['workspaceName'])
            ? WorkspaceName::fromString($commandArguments['workspaceName'])
            : $this->currentWorkspaceName;
        $nodeAggregateId = NodeAggregateId::fromString($commandArguments['nodeAggregateId']);

        $command = CreateRootNodeAggregateWithNode::create(
            $workspaceName,
            $nodeAggregateId,
            NodeTypeName::fromString($commandArguments['nodeTypeName']),
        );
        if (isset($commandArguments['tetheredDescendantNodeAggregateIds'])) {
            $command = $command->withTetheredDescendantNodeAggregateIds(NodeAggregateIdsByNodePaths::fromArray($commandArguments['tetheredDescendantNodeAggregateIds']));
        }

        $this->currentContentRepository->handle($command);
        $this->currentRootNodeAggregateId = $nodeAggregateId;
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
            $eventPayload['contentStreamId'] = $this->currentContentStreamId?->value;
        }
        $contentStreamId = ContentStreamId::fromString($eventPayload['contentStreamId']);
        $nodeAggregateId = NodeAggregateId::fromString($eventPayload['nodeAggregateId']);
        $streamName = ContentStreamEventStreamName::fromContentStreamId($contentStreamId);

        $this->publishEvent('RootNodeAggregateWithNodeWasCreated', $streamName->getEventStreamName(), $eventPayload);
        $this->currentRootNodeAggregateId = $nodeAggregateId;
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
        $workspaceName = isset($commandArguments['workspaceName'])
            ? WorkspaceName::fromString($commandArguments['workspaceName'])
            : $this->currentWorkspaceName;
        $nodeAggregateId = NodeAggregateId::fromString($commandArguments['nodeAggregateId']);

        $command = UpdateRootNodeAggregateDimensions::create(
            $workspaceName,
            $nodeAggregateId,
        );

        $this->currentContentRepository->handle($command);
        $this->currentRootNodeAggregateId = $nodeAggregateId;
    }

    /**
     * @When /^the command CreateNodeAggregateWithNode is executed with payload:$/
     * @param TableNode $payloadTable
     */
    public function theCommandCreateNodeAggregateWithNodeIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $workspaceName = isset($commandArguments['workspaceName'])
            ? WorkspaceName::fromString($commandArguments['workspaceName'])
            : $this->currentWorkspaceName;
        $originDimensionSpacePoint = isset($commandArguments['originDimensionSpacePoint'])
            ? OriginDimensionSpacePoint::fromArray($commandArguments['originDimensionSpacePoint'])
            : OriginDimensionSpacePoint::fromDimensionSpacePoint($this->currentDimensionSpacePoint);

        $command = CreateNodeAggregateWithNode::create(
            $workspaceName,
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
        );
        if (isset($commandArguments['tetheredDescendantNodeAggregateIds'])) {
            $command = $command->withTetheredDescendantNodeAggregateIds(NodeAggregateIdsByNodePaths::fromArray($commandArguments['tetheredDescendantNodeAggregateIds']));
        }
        $this->currentContentRepository->handle($command);
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
            $workspaceName = isset($row['workspaceName'])
                ? WorkspaceName::fromString($row['workspaceName'])
                : $this->currentWorkspaceName;
            $originDimensionSpacePoint = isset($row['originDimensionSpacePoint'])
                ? OriginDimensionSpacePoint::fromJsonString($row['originDimensionSpacePoint'])
                : OriginDimensionSpacePoint::fromDimensionSpacePoint($this->currentDimensionSpacePoint);
            $rawParentNodeAggregateId = $row['parentNodeAggregateId'];
            $command = CreateNodeAggregateWithNode::create(
                $workspaceName,
                NodeAggregateId::fromString($row['nodeAggregateId']),
                NodeTypeName::fromString($row['nodeTypeName']),
                $originDimensionSpacePoint,
                \str_starts_with($rawParentNodeAggregateId, '$')
                    ? $this->rememberedNodeAggregateIds[\mb_substr($rawParentNodeAggregateId, 1)]
                    : NodeAggregateId::fromString($rawParentNodeAggregateId),
                !empty($row['succeedingSiblingNodeAggregateId'])
                    ? NodeAggregateId::fromString($row['succeedingSiblingNodeAggregateId'])
                    : null,
                isset($row['nodeName'])
                    ? NodeName::fromString($row['nodeName'])
                    : null,
                isset($row['initialPropertyValues'])
                    ? $this->parsePropertyValuesJsonString($row['initialPropertyValues'])
                    : null,
            );
            if (isset($row['tetheredDescendantNodeAggregateIds'])) {
                $command = $command->withTetheredDescendantNodeAggregateIds(NodeAggregateIdsByNodePaths::fromJsonString($row['tetheredDescendantNodeAggregateIds']));
            }
            $this->currentContentRepository->handle($command);
        }
    }

    private function parsePropertyValuesJsonString(string $jsonString): PropertyValuesToWrite
    {
        $array = \json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);

        return $this->deserializeProperties($array);
    }

    /**
     * @When /^the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandCreateNodeAggregateWithNodeAndSerializedPropertiesIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $workspaceName = isset($commandArguments['workspaceName'])
            ? WorkspaceName::fromString($commandArguments['workspaceName'])
            : $this->currentWorkspaceName;
        $originDimensionSpacePoint = isset($commandArguments['originDimensionSpacePoint'])
            ? OriginDimensionSpacePoint::fromArray($commandArguments['originDimensionSpacePoint'])
            : OriginDimensionSpacePoint::fromDimensionSpacePoint($this->currentDimensionSpacePoint);

        $command = CreateNodeAggregateWithNodeAndSerializedProperties::create(
            $workspaceName,
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
                : null
        );
        if (isset($commandArguments['tetheredDescendantNodeAggregateIds'])) {
            $command = $command->withTetheredDescendantNodeAggregateIds(NodeAggregateIdsByNodePaths::fromArray($commandArguments['tetheredDescendantNodeAggregateIds']));
        }
        $this->currentContentRepository->handle($command);
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
