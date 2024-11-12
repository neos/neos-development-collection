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

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap;

use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\EventStore\EventPersister;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\AddDimensionShineThrough;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\MoveDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\DisableNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\EnableNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeDuplication\Command\CopyNodesRecursively;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeMove\Command\MoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferencesForName;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferencesToWrite;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferenceToWrite;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Command\ChangeNodeAggregateName;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Command\ChangeNodeAggregateType;
use Neos\ContentRepository\Core\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\UpdateRootNodeAggregateDimensions;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\TagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\UntagSubtree;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\ChangeBaseWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\DeleteWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Exception\WorkspaceRebaseFailed;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\StreamName;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\EventStore\Model\EventStream\VirtualStreamName;
use Neos\Utility\Arrays;
use PHPUnit\Framework\Assert;

/**
 * The content stream forking feature trait for behavioral tests
 */
trait GenericCommandExecutionAndEventPublication
{
    use CRTestSuiteRuntimeVariables;

    private ?array $currentEventStreamAsArray = null;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    abstract protected function getEventStore(): EventStoreInterface;

    abstract protected function deserializeProperties(array $properties): PropertyValuesToWrite;

    /**
     * @When the command :shortCommandName is executed with payload:
     * @throws \Exception
     */
    public function theCommandIsExecutedWithPayload(string $shortCommandName, TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand($shortCommandName, $commandArguments);
    }

    /**
     * @When the command :shortCommandName is executed with payload and exceptions are caught:
     */
    public function theCommandIsExecutedWithPayloadAndExceptionsAreCaught(string $shortCommandName, TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        try {
            $this->handleCommand($shortCommandName, $commandArguments);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @When the command :shortCommandName is executed with payload :payload
     */
    public function theCommandIsExecutedWithJsonPayload(string $shortCommandName, string $payload): void
    {
        $commandArguments = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        $this->handleCommand($shortCommandName, $commandArguments);
    }

    /**
     * @When the command :shortCommandName is executed with payload :payload and exceptions are caught
     */
    public function theCommandIsExecutedWithJsonPayloadAndExceptionsAreCaught(string $shortCommandName, string $payload): void
    {
        $commandArguments = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        try {
            $this->handleCommand($shortCommandName, $commandArguments);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @When the following :shortCommandName commands are executed:
     */
    public function theFollowingCreateNodeAggregateWithNodeCommandsAreExecuted(string $shortCommandName, TableNode $table): void
    {
        foreach ($table->getHash() as $row) {
            $this->handleCommand($shortCommandName, $row);
        }
    }

    private function handleCommand(string $shortCommandName, array $commandArguments): void
    {
        $commandClassName = self::resolveShortCommandName($shortCommandName);
        $commandArguments = $this->addDefaultCommandArgumentValues($commandClassName, $commandArguments);
        $command = $commandClassName::fromArray($commandArguments);
        if ($command instanceof CreateRootNodeAggregateWithNode) {
            $this->currentRootNodeAggregateId = $command->nodeAggregateId;
        }
        $this->currentContentRepository->handle($command);
    }

    /**
     * @param class-string<CommandInterface> $commandClassName
     */
    protected function addDefaultCommandArgumentValues(string $commandClassName, array $commandArguments): array
    {
        $commandArguments['workspaceName'] = $commandArguments['workspaceName'] ?? $this->currentWorkspaceName?->value;
        $commandArguments['coveredDimensionSpacePoint'] = $commandArguments['coveredDimensionSpacePoint'] ?? $this->currentDimensionSpacePoint?->coordinates;
        $commandArguments['dimensionSpacePoint'] = $commandArguments['dimensionSpacePoint'] ?? $this->currentDimensionSpacePoint?->coordinates;
        if (is_string($commandArguments['nodeAggregateId'] ?? null) && str_starts_with($commandArguments['nodeAggregateId'], '$')) {
            $commandArguments['nodeAggregateId'] = $this->rememberedNodeAggregateIds[substr($commandArguments['nodeAggregateId'], 1)]?->value;
        } elseif (!isset($commandArguments['nodeAggregateId'])) {
            $commandArguments['nodeAggregateId'] = $this->getCurrentNodeAggregateId()?->value;
        }
        if ($commandClassName === CreateNodeAggregateWithNode::class) {
            if (is_string($commandArguments['initialPropertyValues'] ?? null)) {
                $commandArguments['initialPropertyValues'] = $this->deserializeProperties(json_decode($commandArguments['initialPropertyValues'], true, 512, JSON_THROW_ON_ERROR))->values;
            } elseif (is_array($commandArguments['initialPropertyValues'] ?? null)) {
                $commandArguments['initialPropertyValues'] = $this->deserializeProperties($commandArguments['initialPropertyValues'])->values;
            }
            if (isset($commandArguments['succeedingSiblingNodeAggregateId']) && $commandArguments['succeedingSiblingNodeAggregateId'] === '') {
                unset($commandArguments['succeedingSiblingNodeAggregateId']);
            }
            if (is_string($commandArguments['parentNodeAggregateId'] ?? null) && str_starts_with($commandArguments['parentNodeAggregateId'], '$')) {
                $commandArguments['parentNodeAggregateId'] = $this->rememberedNodeAggregateIds[substr($commandArguments['parentNodeAggregateId'], 1)]?->value;
            }
        }
        if ($commandClassName === SetNodeProperties::class) {
            if (is_string($commandArguments['propertyValues'] ?? null)) {
                $commandArguments['propertyValues'] = $this->deserializeProperties(json_decode($commandArguments['propertyValues'], true, 512, JSON_THROW_ON_ERROR))->values;
            } elseif (is_array($commandArguments['propertyValues'] ?? null)) {
                $commandArguments['propertyValues'] = $this->deserializeProperties($commandArguments['propertyValues'])->values;
            }
        }
        if ($commandClassName === CreateNodeAggregateWithNode::class || $commandClassName === SetNodeProperties::class) {
            if (is_string($commandArguments['originDimensionSpacePoint'] ?? null) && !empty($commandArguments['originDimensionSpacePoint'])) {
                $commandArguments['originDimensionSpacePoint'] = OriginDimensionSpacePoint::fromJsonString($commandArguments['originDimensionSpacePoint'])->coordinates;
            } elseif (!isset($commandArguments['originDimensionSpacePoint'])) {
                $commandArguments['originDimensionSpacePoint'] = $this->currentDimensionSpacePoint?->coordinates;
            }
        }
        if ($commandClassName === CreateNodeAggregateWithNode::class || $commandClassName === SetNodeReferences::class) {
            if (is_array($commandArguments['references'] ?? null)) {
                $commandArguments['references'] = iterator_to_array($this->mapRawNodeReferencesToNodeReferencesToWrite($commandArguments['references']));
            }
        }
        if ($commandClassName === SetNodeReferences::class) {
            if (is_string($commandArguments['sourceOriginDimensionSpacePoint'] ?? null) && !empty($commandArguments['sourceOriginDimensionSpacePoint'])) {
                $commandArguments['sourceOriginDimensionSpacePoint'] = OriginDimensionSpacePoint::fromJsonString($commandArguments['sourceOriginDimensionSpacePoint'])->coordinates;
            } elseif (!isset($commandArguments['sourceOriginDimensionSpacePoint'])) {
                $commandArguments['sourceOriginDimensionSpacePoint'] = $this->currentDimensionSpacePoint?->coordinates;
            }
            if (is_string($commandArguments['sourceNodeAggregateId'] ?? null) && str_starts_with($commandArguments['sourceNodeAggregateId'], '$')) {
                $commandArguments['sourceNodeAggregateId'] = $this->rememberedNodeAggregateIds[substr($commandArguments['sourceNodeAggregateId'], 1)]?->value;
            } elseif (!isset($commandArguments['sourceNodeAggregateId'])) {
                $commandArguments['sourceNodeAggregateId'] = $this->currentNodeAggregate?->nodeAggregateId->value;
            }
        }
        if ($commandClassName === CreateNodeAggregateWithNode::class || $commandClassName === ChangeNodeAggregateType::class || $commandClassName === CreateRootNodeAggregateWithNode::class) {
            if (is_string($commandArguments['tetheredDescendantNodeAggregateIds'] ?? null)) {
                if ($commandArguments['tetheredDescendantNodeAggregateIds'] === '') {
                    unset($commandArguments['tetheredDescendantNodeAggregateIds']);
                } else {
                    $commandArguments['tetheredDescendantNodeAggregateIds'] = json_decode($commandArguments['tetheredDescendantNodeAggregateIds'], true, 512, JSON_THROW_ON_ERROR);
                }
            }
        }
        return $commandArguments;
    }

    protected function mapRawNodeReferencesToNodeReferencesToWrite(array $deserializedTableContent): NodeReferencesToWrite
    {
        $referencesForProperty = [];
        foreach ($deserializedTableContent as $nodeReferencesForProperty) {
            $references = [];
            foreach ($nodeReferencesForProperty['references'] as $referenceData) {
                $properties = isset($referenceData['properties']) ? $this->deserializeProperties($referenceData['properties']) : PropertyValuesToWrite::createEmpty();
                $references[] = NodeReferenceToWrite::fromTargetAndProperties(NodeAggregateId::fromString($referenceData['target']), $properties);
            }
            $referencesForProperty[] = NodeReferencesForName::fromReferences(ReferenceName::fromString($nodeReferencesForProperty['referenceName']), $references);
        }
        return NodeReferencesToWrite::fromArray($referencesForProperty);
    }

    /**
     * @return class-string<CommandInterface>
     */
    protected static function resolveShortCommandName(string $shortCommandName): string
    {
        $commandClassNames = [
            AddDimensionShineThrough::class,
            ChangeBaseWorkspace::class,
            ChangeNodeAggregateName::class,
            ChangeNodeAggregateType::class,
            CopyNodesRecursively::class,
            CreateNodeAggregateWithNode::class,
            CreateNodeVariant::class,
            CreateRootNodeAggregateWithNode::class,
            CreateRootWorkspace::class,
            CreateWorkspace::class,
            DeleteWorkspace::class,
            DisableNodeAggregate::class,
            DiscardIndividualNodesFromWorkspace::class,
            DiscardWorkspace::class,
            EnableNodeAggregate::class,
            MoveDimensionSpacePoint::class,
            MoveNodeAggregate::class,
            PublishIndividualNodesFromWorkspace::class,
            PublishWorkspace::class,
            RebasableToOtherWorkspaceInterface::class,
            RebaseWorkspace::class,
            RemoveNodeAggregate::class,
            SetNodeProperties::class,
            SetNodeReferences::class,
            TagSubtree::class,
            UntagSubtree::class,
            UpdateRootNodeAggregateDimensions::class,
        ];
        foreach ($commandClassNames as $commandClassName) {
            if (substr(strrchr($commandClassName, '\\'), 1) === $shortCommandName) {
                return $commandClassName;
            }
        }
        throw new \RuntimeException('The short command name "' . $shortCommandName . '" is currently not supported by the tests.');
    }

    /**
     * @throws \Exception
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    protected function publishEvent(string $eventType, StreamName $streamName, array $eventPayload): void
    {
        $artificiallyConstructedEvent = new Event(
            Event\EventId::create(),
            Event\EventType::fromString($eventType),
            Event\EventData::fromString(json_encode($eventPayload)),
            Event\EventMetadata::fromArray([])
        );
        /** @var EventPersister $eventPersister */
        $eventPersister = (new \ReflectionClass($this->currentContentRepository))->getProperty('eventPersister')
            ->getValue($this->currentContentRepository);
        /** @var EventNormalizer $eventNormalizer */
        $eventNormalizer = (new \ReflectionClass($eventPersister))->getProperty('eventNormalizer')
            ->getValue($eventPersister);
        $event = $eventNormalizer->denormalize($artificiallyConstructedEvent);

        $eventPersister->publishEvents($this->currentContentRepository, new EventsToPublish(
            $streamName,
            Events::with($event),
            ExpectedVersion::ANY()
        ));
    }

    /**
     * @Then /^the last command should have thrown an exception of type "([^"]*)"(?: with code (\d*))?$/
     */
    public function theLastCommandShouldHaveThrown(string $shortExceptionName, ?int $expectedCode = null): void
    {
        if ($shortExceptionName === 'WorkspaceRebaseFailed') {
            throw new \RuntimeException('Please use the assertion "the last command should have thrown the WorkspaceRebaseFailed exception with" instead.');
        }

        Assert::assertNotNull($this->lastCommandException, 'Command did not throw exception');
        $lastCommandExceptionShortName = (new \ReflectionClass($this->lastCommandException))->getShortName();
        Assert::assertSame($shortExceptionName, $lastCommandExceptionShortName, sprintf('Actual exception: %s (%s): %s', get_class($this->lastCommandException), $this->lastCommandException->getCode(), $this->lastCommandException->getMessage()));
        if (!is_null($expectedCode)) {
            Assert::assertSame($expectedCode, $this->lastCommandException->getCode(), sprintf(
                'Expected exception code %s, got exception code %s instead; Message: %s',
                $expectedCode,
                $this->lastCommandException->getCode(),
                $this->lastCommandException->getMessage()
            ));
        }
    }

    /**
     * @Then the last command should have thrown the WorkspaceRebaseFailed exception with:
     */
    public function theLastCommandShouldHaveThrownTheWorkspaceRebaseFailedWith(TableNode $payloadTable)
    {
        /** @var WorkspaceRebaseFailed $exception */
        $exception = $this->lastCommandException;
        Assert::assertNotNull($exception, 'Command did not throw exception');
        Assert::assertInstanceOf(WorkspaceRebaseFailed::class, $exception, sprintf('Actual exception: %s (%s): %s', get_class($exception), $exception->getCode(), $exception->getMessage()));

        $actualComparableHash = [];
        foreach ($exception->conflictingEvents as $conflictingEvent) {
            $actualComparableHash[] = [
                'SequenceNumber' => (string)$conflictingEvent->getSequenceNumber()->value,
                'Event' =>  (new \ReflectionClass($conflictingEvent->getEvent()))->getShortName(),
                'Exception' =>  (new \ReflectionClass($conflictingEvent->getException()))->getShortName(),
            ];
        }

        Assert::assertSame($payloadTable->getHash(), $actualComparableHash);
    }

    /**
     * @Then /^I expect exactly (\d+) events? to be published on stream "([^"]*)"$/
     * @param int $numberOfEvents
     * @param string $streamName
     */
    public function iExpectExactlyEventToBePublishedOnStream(int $numberOfEvents, string $streamName)
    {
        $streamName = StreamName::fromString($streamName);
        $stream = $this->getEventStore()->load($streamName);
        $this->currentEventStreamAsArray = iterator_to_array($stream, false);
        Assert::assertEquals($numberOfEvents, count($this->currentEventStreamAsArray), 'Number of events did not match');
    }

    /**
     * @Then /^I expect exactly (\d+) events? to be published on stream with prefix "([^"]*)"$/
     * @param int $numberOfEvents
     * @param string $streamName
     */
    public function iExpectExactlyEventToBePublishedOnStreamWithPrefix(int $numberOfEvents, string $streamName)
    {
        $streamName = VirtualStreamName::forCategory($streamName);

        $stream = $this->getEventStore()->load($streamName);
        $this->currentEventStreamAsArray = iterator_to_array($stream, false);
        Assert::assertEquals($numberOfEvents, count($this->currentEventStreamAsArray), 'Number of events did not match');
    }


    /**
     * @Then /^I expect the highest sequence number to be (\d+)$/
     * @param int $numberOfEvents
     * @param string $streamName
     */
    public function iExpectTheHighestSequnceNumberToBe(int $highestSequenceNumber)
    {
        $streamName = VirtualStreamName::all();
        $stream = iterator_to_array($this->getEventStore()->load($streamName)->backwards()->limit(1), false);
        Assert::assertEquals($highestSequenceNumber, ($stream[0] ?? null)?->sequenceNumber->value, 'Sequence number did not match');
    }


    /**
     * @Then /^event at index (\d+) is of type "([^"]*)" with payload:/
     * @param int $eventNumber
     * @param string $eventType
     * @param TableNode $payloadTable
     */
    public function eventNumberIs(int $eventNumber, string $eventType, TableNode $payloadTable)
    {
        if ($this->currentEventStreamAsArray === null) {
            Assert::fail('Step \'I expect exactly ? events to be published on stream "?"\' was not executed');
        }

        Assert::assertArrayHasKey($eventNumber, $this->currentEventStreamAsArray, 'Event at index does not exist');

        $actualEvent = $this->currentEventStreamAsArray[$eventNumber];
        assert($actualEvent instanceof EventEnvelope);

        Assert::assertNotNull($actualEvent, sprintf('Event with number %d not found', $eventNumber));
        Assert::assertEquals($eventType, $actualEvent->event->type->value, 'Event Type does not match: "' . $actualEvent->event->type->value . '" !== "' . $eventType . '"');

        $actualEventPayload = json_decode($actualEvent->event->data->value, true);

        foreach ($payloadTable->getHash() as $assertionTableRow) {
            $key = $assertionTableRow['Key'];
            $actualValue = Arrays::getValueByPath($actualEventPayload, $key);

            if ($key === 'affectedDimensionSpacePoints') {
                $expected = DimensionSpacePointSet::fromJsonString($assertionTableRow['Expected']);
                $actual = DimensionSpacePointSet::fromArray($actualValue);
                Assert::assertTrue($expected->equals($actual), 'Actual Dimension Space Point set "' . json_encode($actualValue) . '" does not match expected Dimension Space Point set "' . $assertionTableRow['Expected'] . '"');
            } else {
                Assert::assertJsonStringEqualsJsonString($assertionTableRow['Expected'], json_encode($actualValue));
            }
        }
    }

    /**
     * @Then /^event metadata at index (\d+) is:/
     * @param int $eventNumber
     * @param TableNode $metadataTable
     */
    public function eventMetadataAtNumberIs(int $eventNumber, TableNode $metadataTable)
    {
        if ($this->currentEventStreamAsArray === null) {
            Assert::fail('Step \'I expect exactly ? events to be published on stream "?"\' was not executed');
        }

        Assert::assertArrayHasKey($eventNumber, $this->currentEventStreamAsArray, 'Event at index does not exist');

        $actualEvent = $this->currentEventStreamAsArray[$eventNumber];
        assert($actualEvent instanceof EventEnvelope);

        Assert::assertNotNull($actualEvent, sprintf('Event with number %d not found', $eventNumber));

        $actualEventMetadata = $actualEvent->event->metadata->value;
        foreach ($metadataTable->getHash() as $assertionTableRow) {
            $key = $assertionTableRow['Key'];
            $actualValue = Arrays::getValueByPath($actualEventMetadata, $key);
            Assert::assertJsonStringEqualsJsonString($assertionTableRow['Expected'], json_encode($actualValue));
        }
    }
}
