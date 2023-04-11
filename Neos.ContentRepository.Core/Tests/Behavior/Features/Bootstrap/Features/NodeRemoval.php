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
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\EventStore\Model\Event\StreamName;

/**
 * The node removal trait for behavioral tests
 */
trait NodeRemoval
{
    abstract protected function getContentRepository(): ContentRepository;

    abstract protected function getCurrentContentStreamId(): ?ContentStreamId;

    abstract protected function getCurrentDimensionSpacePoint(): ?DimensionSpacePoint;

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
        $contentStreamId = isset($commandArguments['contentStreamId'])
            ? ContentStreamId::fromString($commandArguments['contentStreamId'])
            : $this->getCurrentContentStreamId();
        $coveredDimensionSpacePoint = isset($commandArguments['coveredDimensionSpacePoint'])
            ? DimensionSpacePoint::fromArray($commandArguments['coveredDimensionSpacePoint'])
            : $this->getCurrentDimensionSpacePoint();

        $command = new RemoveNodeAggregate(
            $contentStreamId,
            NodeAggregateId::fromString($commandArguments['nodeAggregateId']),
            $coveredDimensionSpacePoint,
            NodeVariantSelectionStrategy::from($commandArguments['nodeVariantSelectionStrategy']),
            isset($commandArguments['removalAttachmentPoint'])
                ? NodeAggregateId::fromString($commandArguments['removalAttachmentPoint'])
                : null
        );

        $this->lastCommandOrEventResult = $this->getContentRepository()->handle($command);
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
        if (!isset($eventPayload['contentStreamId'])) {
            $eventPayload['contentStreamId'] = (string)$this->getCurrentContentStreamId();
        }
        $contentStreamId = ContentStreamId::fromString($eventPayload['contentStreamId']);
        $streamName = ContentStreamEventStreamName::fromContentStreamId($contentStreamId);

        $this->publishEvent('NodeAggregateWasRemoved', $streamName->getEventStreamName(), $eventPayload);
    }
}
