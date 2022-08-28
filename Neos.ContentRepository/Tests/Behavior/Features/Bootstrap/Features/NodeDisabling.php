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
use Neos\ContentRepository\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\NodeDisabling\Command\DisableNodeAggregate;
use Neos\ContentRepository\Feature\NodeDisabling\Command\EnableNodeAggregate;
use Neos\ContentRepository\Feature\Common\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\EventStore\Model\Event\StreamName;

/**
 * The node disabling trait for behavioral tests
 */
trait NodeDisabling
{
    abstract protected function getContentRepository(): ContentRepository;

    abstract protected function getCurrentContentStreamIdentifier(): ?ContentStreamIdentifier;

    abstract protected function getCurrentDimensionSpacePoint(): ?DimensionSpacePoint;

    abstract protected function getCurrentUserIdentifier(): ?UserIdentifier;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    abstract protected function publishEvent(string $eventType, StreamName $streamName, array $eventPayload): void;

    /**
     * @Given /^the command DisableNodeAggregate is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandDisableNodeAggregateIsExecutedWithPayload(TableNode $payloadTable): void
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

        $command = new DisableNodeAggregate(
            $contentStreamIdentifier,
            NodeAggregateIdentifier::fromString($commandArguments['nodeAggregateIdentifier']),
            $coveredDimensionSpacePoint,
            NodeVariantSelectionStrategy::from($commandArguments['nodeVariantSelectionStrategy']),
            $initiatingUserIdentifier
        );

        $this->lastCommandOrEventResult = $this->getContentRepository()->handle($command);
    }

    /**
     * @Given /^the command DisableNodeAggregate is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     */
    public function theCommandDisableNodeAggregateIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable): void
    {
        try {
            $this->theCommandDisableNodeAggregateIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @Given /^the event NodeAggregateWasDisabled was published with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theEventNodeAggregateWasDisabledWasPublishedWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (!isset($eventPayload['initiatingUserIdentifier'])) {
            $eventPayload['initiatingUserIdentifier'] = (string)$this->getCurrentUserIdentifier();
        }
        if (!isset($eventPayload['contentStreamIdentifier'])) {
            $eventPayload['contentStreamIdentifier'] = (string)$this->getCurrentContentStreamIdentifier();
        }
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            ContentStreamIdentifier::fromString($eventPayload['contentStreamIdentifier'])
        );

        $this->publishEvent('NodeAggregateWasDisabled', $streamName->getEventStreamName(), $eventPayload);
    }


    /**
     * @Given /^the event NodeAggregateWasEnabled was published with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theEventNodeAggregateWasEnabledWasPublishedWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (!isset($eventPayload['initiatingUserIdentifier'])) {
            $eventPayload['initiatingUserIdentifier'] = (string)$this->getCurrentUserIdentifier();
        }
        if (!isset($eventPayload['contentStreamIdentifier'])) {
            $eventPayload['contentStreamIdentifier'] = (string)$this->getCurrentContentStreamIdentifier();
        }
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            ContentStreamIdentifier::fromString($eventPayload['contentStreamIdentifier'])
        );

        $this->publishEvent('NodeAggregateWasEnabled', $streamName->getEventStreamName(), $eventPayload);
    }


    /**
     * @Given /^the command EnableNodeAggregate is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandEnableNodeAggregateIsExecutedWithPayload(TableNode $payloadTable): void
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

        $command = new EnableNodeAggregate(
            $contentStreamIdentifier,
            NodeAggregateIdentifier::fromString($commandArguments['nodeAggregateIdentifier']),
            $coveredDimensionSpacePoint,
            NodeVariantSelectionStrategy::from($commandArguments['nodeVariantSelectionStrategy']),
            $initiatingUserIdentifier
        );

        $this->lastCommandOrEventResult = $this->getContentRepository()->handle($command);
    }

    /**
     * @Given /^the command EnableNodeAggregate is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     */
    public function theCommandEnableNodeAggregateIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable): void
    {
        try {
            $this->theCommandEnableNodeAggregateIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }
}
