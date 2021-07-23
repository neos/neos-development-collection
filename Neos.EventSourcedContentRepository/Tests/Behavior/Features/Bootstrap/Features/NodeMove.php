<?php
declare(strict_types=1);

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
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\RelationDistributionStrategy;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\MoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcing\EventStore\StreamName;

/**
 * The node move trait for behavioral tests
 */
trait NodeMove
{
    abstract protected function getCurrentContentStreamIdentifier(): ?ContentStreamIdentifier;

    abstract protected function getCurrentDimensionSpacePoint(): ?DimensionSpacePoint;

    abstract protected function getCurrentUserIdentifier(): ?UserIdentifier;

    abstract protected function getNodeAggregateCommandHandler(): NodeAggregateCommandHandler;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    abstract protected function publishEvent(string $eventType, StreamName $streamName, array $eventPayload): void;

    /**
     * @Given /^the command MoveNodeAggregate is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theCommandMoveNodeIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $contentStreamIdentifier = isset($commandArguments['contentStreamIdentifier'])
            ? ContentStreamIdentifier::fromString($commandArguments['contentStreamIdentifier'])
            : $this->getCurrentContentStreamIdentifier();
        $dimensionSpacePoint = isset($commandArguments['dimensionSpacePoint'])
            ? DimensionSpacePoint::fromArray($commandArguments['dimensionSpacePoint'])
            : $this->getCurrentDimensionSpacePoint();
        $newParentNodeAggregateIdentifier = isset($commandArguments['newParentNodeAggregateIdentifier'])
            ? NodeAggregateIdentifier::fromString($commandArguments['newParentNodeAggregateIdentifier'])
            : null;
        $newPrecedingSiblingNodeAggregateIdentifier = isset($commandArguments['newPrecedingSiblingNodeAggregateIdentifier'])
            ? NodeAggregateIdentifier::fromString($commandArguments['newPrecedingSiblingNodeAggregateIdentifier'])
            : null;
        $newSucceedingSiblingNodeAggregateIdentifier = isset($commandArguments['newSucceedingSiblingNodeAggregateIdentifier'])
            ? NodeAggregateIdentifier::fromString($commandArguments['newSucceedingSiblingNodeAggregateIdentifier'])
            : null;
        $relationDistributionStrategy = isset($commandArguments['relationDistributionStrategy'])
            ? RelationDistributionStrategy::fromString($commandArguments['relationDistributionStrategy'])
            : RelationDistributionStrategy::gatherAll();
        $initiatingUserIdentifier = isset($commandArguments['initiatingUserIdentifier'])
            ? UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
            : $this->getCurrentUserIdentifier();

        $command = new MoveNodeAggregate(
            $contentStreamIdentifier,
            $dimensionSpacePoint,
            NodeAggregateIdentifier::fromString($commandArguments['nodeAggregateIdentifier']),
            $newParentNodeAggregateIdentifier,
            $newPrecedingSiblingNodeAggregateIdentifier,
            $newSucceedingSiblingNodeAggregateIdentifier,
            $relationDistributionStrategy,
            $initiatingUserIdentifier
        );

        $this->lastCommandOrEventResult = $this->getNodeAggregateCommandHandler()
            ->handleMoveNodeAggregate($command);
    }

    /**
     * @Given /^the command MoveNodeAggregate is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     */
    public function theCommandMoveNodeIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable): void
    {
        try {
            $this->theCommandMoveNodeIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @Given /^the event NodeAggregateWasMoved was published with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theEventNodeAggregateWasMovedWasPublishedWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (!isset($eventPayload['initiatingUserIdentifier'])) {
            $eventPayload['initiatingUserIdentifier'] = (string)$this->getCurrentUserIdentifier();
        }
        if (!isset($eventPayload['contentStreamIdentifier'])) {
            $eventPayload['contentStreamIdentifier'] = (string)$this->getCurrentContentStreamIdentifier();
        }
        $contentStreamIdentifier = ContentStreamIdentifier::fromString($eventPayload['contentStreamIdentifier']);
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier);

        $this->publishEvent('Neos.EventSourcedContentRepository:NodeAggregateWasMoved', $streamName->getEventStreamName(), $eventPayload);
    }
}
