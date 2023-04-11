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
use Neos\ContentRepository\Core\Feature\NodeMove\Command\MoveNodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\RelationDistributionStrategy;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\EventStore\Model\Event\StreamName;

/**
 * The node move trait for behavioral tests
 */
trait NodeMove
{
    abstract protected function getContentRepository(): ContentRepository;

    abstract protected function getCurrentContentStreamId(): ?ContentStreamId;

    abstract protected function getCurrentDimensionSpacePoint(): ?DimensionSpacePoint;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    abstract protected function publishEvent(string $eventType, StreamName $streamName, array $eventPayload): void;

    /**
     * @Given /^the command MoveNodeAggregate is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandMoveNodeIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $contentStreamId = isset($commandArguments['contentStreamId'])
            ? ContentStreamId::fromString($commandArguments['contentStreamId'])
            : $this->getCurrentContentStreamId();
        $dimensionSpacePoint = isset($commandArguments['dimensionSpacePoint'])
            ? DimensionSpacePoint::fromArray($commandArguments['dimensionSpacePoint'])
            : $this->getCurrentDimensionSpacePoint();
        $newParentNodeAggregateId = isset($commandArguments['newParentNodeAggregateId'])
            ? NodeAggregateId::fromString($commandArguments['newParentNodeAggregateId'])
            : null;
        $newPrecedingSiblingNodeAggregateId = isset($commandArguments['newPrecedingSiblingNodeAggregateId'])
            ? NodeAggregateId::fromString($commandArguments['newPrecedingSiblingNodeAggregateId'])
            : null;
        $newSucceedingSiblingNodeAggregateId = isset($commandArguments['newSucceedingSiblingNodeAggregateId'])
            ? NodeAggregateId::fromString($commandArguments['newSucceedingSiblingNodeAggregateId'])
            : null;
        $relationDistributionStrategy = RelationDistributionStrategy::fromString(
            $commandArguments['relationDistributionStrategy'] ?? null
        );

        $command = new MoveNodeAggregate(
            $contentStreamId,
            $dimensionSpacePoint,
            NodeAggregateId::fromString($commandArguments['nodeAggregateId']),
            $newParentNodeAggregateId,
            $newPrecedingSiblingNodeAggregateId,
            $newSucceedingSiblingNodeAggregateId,
            $relationDistributionStrategy,
        );

        $this->lastCommandOrEventResult = $this->getContentRepository()->handle($command);
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
     * @throws \Exception
     */
    public function theEventNodeAggregateWasMovedWasPublishedWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (!isset($eventPayload['contentStreamId'])) {
            $eventPayload['contentStreamId'] = (string)$this->getCurrentContentStreamId();
        }
        $contentStreamId = ContentStreamId::fromString($eventPayload['contentStreamId']);
        $streamName = ContentStreamEventStreamName::fromContentStreamId($contentStreamId);

        $this->publishEvent('NodeAggregateWasMoved', $streamName->getEventStreamName(), $eventPayload);
    }
}
