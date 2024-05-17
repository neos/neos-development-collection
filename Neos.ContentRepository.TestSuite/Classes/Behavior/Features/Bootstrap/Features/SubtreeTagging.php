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
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\TagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\UntagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
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
     * @Given /^the command TagSubtree is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandTagSubtreeIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $workspaceName = isset($commandArguments['workspaceName'])
            ? WorkspaceName::fromString($commandArguments['workspaceName'])
            : $this->currentWorkspaceName;
        $coveredDimensionSpacePoint = isset($commandArguments['coveredDimensionSpacePoint'])
            ? DimensionSpacePoint::fromArray($commandArguments['coveredDimensionSpacePoint'])
            : $this->currentDimensionSpacePoint;

        $command = TagSubtree::create(
            $workspaceName,
            NodeAggregateId::fromString($commandArguments['nodeAggregateId']),
            $coveredDimensionSpacePoint,
            NodeVariantSelectionStrategy::from($commandArguments['nodeVariantSelectionStrategy']),
            SubtreeTag::fromString($commandArguments['tag']),
        );

        $this->lastCommandOrEventResult = $this->currentContentRepository->handle($command);
    }

    /**
     * @Given /^the command TagSubtree is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     */
    public function theCommandTagSubtreeIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable): void
    {
        try {
            $this->theCommandTagSubtreeIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @Given /^the event SubtreeWasTagged was published with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theEventSubtreeWasTaggedWasPublishedWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (!isset($eventPayload['workspaceName'])) {
            $eventPayload['workspaceName'] = 'some-workspace';
        }
        $streamName = ContentStreamEventStreamName::fromContentStreamId(
            ContentStreamId::fromString($eventPayload['contentStreamId'])
        );

        $this->publishEvent('SubtreeWasTagged', $streamName->getEventStreamName(), $eventPayload);
    }


    /**
     * @Given /^the event SubtreeWasUntagged was published with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theEventSubtreeWasUntaggedWasPublishedWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (!isset($eventPayload['workspaceName'])) {
            $eventPayload['workspaceName'] = 'some-workspace';
        }
        $streamName = ContentStreamEventStreamName::fromContentStreamId(
            ContentStreamId::fromString($eventPayload['contentStreamId'])
        );

        $this->publishEvent('SubtreeWasUntagged', $streamName->getEventStreamName(), $eventPayload);
    }


    /**
     * @Given /^the command UntagSubtree is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandUntagSubtreeIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $workspaceName = isset($commandArguments['workspaceName'])
            ? WorkspaceName::fromString($commandArguments['workspaceName'])
            : $this->currentWorkspaceName;
        $coveredDimensionSpacePoint = isset($commandArguments['coveredDimensionSpacePoint'])
            ? DimensionSpacePoint::fromArray($commandArguments['coveredDimensionSpacePoint'])
            : $this->currentDimensionSpacePoint;

        $command = UntagSubtree::create(
            $workspaceName,
            NodeAggregateId::fromString($commandArguments['nodeAggregateId']),
            $coveredDimensionSpacePoint,
            NodeVariantSelectionStrategy::from($commandArguments['nodeVariantSelectionStrategy']),
            SubtreeTag::fromString($commandArguments['tag']),
        );

        $this->lastCommandOrEventResult = $this->currentContentRepository->handle($command);
    }

    /**
     * @Given /^the command UntagSubtree is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     */
    public function theCommandUntagSubtreeIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable): void
    {
        try {
            $this->theCommandUntagSubtreeIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }
}
