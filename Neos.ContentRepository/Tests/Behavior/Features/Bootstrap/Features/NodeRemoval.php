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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Feature\NodeRemoval\Command\RestoreNodeAggregateCoverage;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Feature\Common\NodeVariantSelectionStrategy;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\EventSourcing\EventStore\StreamName;

/**
 * The node removal trait for behavioral tests
 */
trait NodeRemoval
{
    abstract protected function getCurrentContentStreamIdentifier(): ?ContentStreamIdentifier;

    abstract protected function getCurrentDimensionSpacePoint(): ?DimensionSpacePoint;

    abstract protected function getCurrentUserIdentifier(): ?UserIdentifier;

    abstract protected function getNodeAggregateCommandHandler(): NodeAggregateCommandHandler;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    abstract protected function publishEvent(string $eventType, StreamName $streamName, array $eventPayload): void;

    /**
     * @Given /^the command RemoveNodeAggregate is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandRemoveNodeAggregateIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $contentStreamIdentifier = isset($commandArguments['contentStreamIdentifier'])
            ? ContentStreamIdentifier::fromString($commandArguments['contentStreamIdentifier'])
            : $this->getCurrentContentStreamIdentifier();
        $coveredDimensionSpacePoint = isset($commandArguments['coveredDimensionSpacePoint'])
            ? DimensionSpacePoint::fromArray($commandArguments['coveredDimensionSpacePoint'])
            : $this->getCurrentDimensionSpacePoint();
        $initiatingUserIdentifier = isset($commandArguments['initiatingUserIdentifier'])
            ? UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
            : $this->getCurrentUserIdentifier();

        $command = new RemoveNodeAggregate(
            $contentStreamIdentifier,
            NodeAggregateIdentifier::fromString($commandArguments['nodeAggregateIdentifier']),
            $coveredDimensionSpacePoint,
            NodeVariantSelectionStrategy::from($commandArguments['nodeVariantSelectionStrategy']),
            $initiatingUserIdentifier,
            isset($commandArguments['removalAttachmentPoint'])
                ? NodeAggregateIdentifier::fromString($commandArguments['removalAttachmentPoint'])
                : null
        );

        $this->lastCommandOrEventResult = $this->getNodeAggregateCommandHandler()
            ->handleRemoveNodeAggregate($command);
    }

    /**
     * @Given /^the command RemoveNodeAggregate is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandRemoveNodeAggregateIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable)
    {
        try {
            $this->theCommandRemoveNodeAggregateIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @Given /^the event NodeAggregateWasRemoved was published with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theEventNodeAggregateWasRemovedWasPublishedWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (!isset($eventPayload['contentStreamIdentifier'])) {
            $eventPayload['contentStreamIdentifier'] = (string)$this->getCurrentContentStreamIdentifier();
        }
        if (!isset($eventPayload['initiatingUserIdentifier'])) {
            $eventPayload['initiatingUserIdentifier'] = (string)$this->getCurrentUserIdentifier();
        }
        $contentStreamIdentifier = ContentStreamIdentifier::fromString($eventPayload['contentStreamIdentifier']);
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier);

        $this->publishEvent('NodeAggregateWasRemoved', $streamName->getEventStreamName(), $eventPayload);
    }


    /**
     * @Given /^the command RestoreNodeAggregateCoverage is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandRestoreNodeAggregateCoverageIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $contentStreamIdentifier = isset($commandArguments['contentStreamIdentifier'])
            ? ContentStreamIdentifier::fromString($commandArguments['contentStreamIdentifier'])
            : $this->getCurrentContentStreamIdentifier();
        $originDimensionSpacePoint = isset($commandArguments['originDimensionSpacePoint'])
            ? OriginDimensionSpacePoint::fromArray($commandArguments['originDimensionSpacePoint'])
            : OriginDimensionSpacePoint::fromDimensionSpacePoint($this->getCurrentDimensionSpacePoint());
        $dimensionSpacePointToCover = DimensionSpacePoint::fromArray($commandArguments['dimensionSpacePointToCover']);
        $initiatingUserIdentifier = isset($commandArguments['initiatingUserIdentifier'])
            ? UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
            : $this->getCurrentUserIdentifier();

        $command = new RestoreNodeAggregateCoverage(
            $contentStreamIdentifier,
            NodeAggregateIdentifier::fromString($commandArguments['nodeAggregateIdentifier']),
            $originDimensionSpacePoint,
            $dimensionSpacePointToCover,
            $commandArguments['withSpecializations'],
            $commandArguments['recursive'],
            $initiatingUserIdentifier
        );

        $this->lastCommandOrEventResult = $this->getNodeAggregateCommandHandler()
            ->handleRestoreNodeAggregateCoverage($command);
    }

    /**
     * @Given /^the command RestoreNodeAggregateCoverage is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandRestoreNodeAggregateCoverageIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable)
    {
        try {
            $this->theCommandRestoreNodeAggregateCoverageIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }
}
