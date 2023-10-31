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
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\Tagging\Command\AddSubtreeTag;
use Neos\ContentRepository\Core\Feature\Tagging\Command\RemoveSubtreeTag;
use Neos\ContentRepository\Core\Feature\Tagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use Neos\EventStore\Model\Event\StreamName;

/**
 * The tagging trait for behavioral tests
 */
trait SubtreeTagging
{
    use CRTestSuiteRuntimeVariables;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    abstract protected function publishEvent(string $eventType, StreamName $streamName, array $eventPayload): void;

    /**
     * @Given /^the command AddSubtreeTag is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandAddSubtreeTagIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $contentStreamId = isset($commandArguments['contentStreamId'])
            ? ContentStreamId::fromString($commandArguments['contentStreamId'])
            : $this->currentContentStreamId;
        $coveredDimensionSpacePoint = isset($commandArguments['coveredDimensionSpacePoint'])
            ? DimensionSpacePoint::fromArray($commandArguments['coveredDimensionSpacePoint'])
            : $this->currentDimensionSpacePoint;

        $command = AddSubtreeTag::create(
            $contentStreamId,
            NodeAggregateId::fromString($commandArguments['nodeAggregateId']),
            $coveredDimensionSpacePoint,
            NodeVariantSelectionStrategy::from($commandArguments['nodeVariantSelectionStrategy']),
            SubtreeTag::fromString($commandArguments['tag']),
        );

        $this->lastCommandOrEventResult = $this->currentContentRepository->handle($command);
    }

    /**
     * @Given /^the command AddSubtreeTag is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     */
    public function theCommandAddSubtreeTagIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable): void
    {
        try {
            $this->theCommandAddSubtreeTagIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @Given /^the event SubtreeTagWasAdded was published with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theEventSubtreeTagWasAddedWasPublishedWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        $streamName = ContentStreamEventStreamName::fromContentStreamId(
            array_key_exists('contentStreamId', $eventPayload)
                ? ContentStreamId::fromString($eventPayload['contentStreamId'])
                : $this->currentContentStreamId
        );

        $this->publishEvent('SubtreeTagWasAdded', $streamName->getEventStreamName(), $eventPayload);
    }


    /**
     * @Given /^the event SubtreeTagWasRemoved was published with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theEventSubtreeTagWasRemovedWasPublishedWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        $streamName = ContentStreamEventStreamName::fromContentStreamId(
            array_key_exists('contentStreamId', $eventPayload)
                ? ContentStreamId::fromString($eventPayload['contentStreamId'])
                : $this->currentContentStreamId
        );

        $this->publishEvent('SubtreeTagWasRemoved', $streamName->getEventStreamName(), $eventPayload);
    }


    /**
     * @Given /^the command RemoveSubtreeTag is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandRemoveSubtreeTagIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $contentStreamId = isset($commandArguments['contentStreamId'])
            ? ContentStreamId::fromString($commandArguments['contentStreamId'])
            : $this->currentContentStreamId;
        $coveredDimensionSpacePoint = isset($commandArguments['coveredDimensionSpacePoint'])
            ? DimensionSpacePoint::fromArray($commandArguments['coveredDimensionSpacePoint'])
            : $this->currentDimensionSpacePoint;

        $command = RemoveSubtreeTag::create(
            $contentStreamId,
            NodeAggregateId::fromString($commandArguments['nodeAggregateId']),
            $coveredDimensionSpacePoint,
            NodeVariantSelectionStrategy::from($commandArguments['nodeVariantSelectionStrategy']),
            SubtreeTag::fromString($commandArguments['tag']),
        );

        $this->lastCommandOrEventResult = $this->currentContentRepository->handle($command);
    }

    /**
     * @Given /^the command RemoveSubtreeTag is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     */
    public function theCommandRemoveSubtreeTagIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable): void
    {
        try {
            $this->theCommandRemoveSubtreeTagIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }
}
