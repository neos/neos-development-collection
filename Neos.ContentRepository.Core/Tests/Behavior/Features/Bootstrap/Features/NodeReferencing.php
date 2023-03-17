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
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferencesToWrite;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferenceToWrite;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\EventStore\Model\Event\StreamName;

/**
 * The node referencing trait for behavioral tests
 */
trait NodeReferencing
{
    abstract protected function getContentRepository(): ContentRepository;

    abstract protected function getCurrentContentStreamId(): ?ContentStreamId;

    abstract protected function getCurrentDimensionSpacePoint(): ?DimensionSpacePoint;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    abstract protected function deserializeProperties(array $properties): PropertyValuesToWrite;

    abstract protected function publishEvent(string $eventType, StreamName $streamName, array $eventPayload): void;

    /**
     * @Given /^the command SetNodeReferences is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandSetNodeReferencesIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $contentStreamId = isset($commandArguments['contentStreamId'])
            ? ContentStreamId::fromString($commandArguments['contentStreamId'])
            : $this->getCurrentContentStreamId();
        $sourceOriginDimensionSpacePoint = isset($commandArguments['sourceOriginDimensionSpacePoint'])
            ? OriginDimensionSpacePoint::fromArray($commandArguments['sourceOriginDimensionSpacePoint'])
            : OriginDimensionSpacePoint::fromDimensionSpacePoint($this->getCurrentDimensionSpacePoint());
        $references = NodeReferencesToWrite::fromReferences(
            array_map(
                fn (array $referenceData): NodeReferenceToWrite => new NodeReferenceToWrite(
                    NodeAggregateId::fromString($referenceData['target']),
                    isset($referenceData['properties'])
                        ? $this->deserializeProperties($referenceData['properties'])
                        : null
                ),
                $commandArguments['references']
            )
        );

        $command = new SetNodeReferences(
            $contentStreamId,
            NodeAggregateId::fromString($commandArguments['sourceNodeAggregateId']),
            $sourceOriginDimensionSpacePoint,
            ReferenceName::fromString($commandArguments['referenceName']),
            $references,
        );

        $this->lastCommandOrEventResult = $this->getContentRepository()->handle($command);
    }

    /**
     * @Given /^the command SetNodeReferences is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandSetNodeReferencesIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable)
    {
        try {
            $this->theCommandSetNodeReferencesIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @Given /^the event NodeReferencesWereSet was published with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theEventNodeReferencesWereSetWasPublishedWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (!isset($eventPayload['contentStreamId'])) {
            $eventPayload['contentStreamId'] = (string)$this->getCurrentContentStreamId();
        }
        $contentStreamId = ContentStreamId::fromString($eventPayload['contentStreamId']);
        $streamName = ContentStreamEventStreamName::fromContentStreamId(
            $contentStreamId
        );

        $this->publishEvent('NodeReferencesWereSet', $streamName->getEventStreamName(), $eventPayload);
    }
}
