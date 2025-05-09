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

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\AddDimensionShineThrough;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\MoveDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\DisableNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\EnableNodeAggregate;
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
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Exception\PartialWorkspaceRebaseFailed;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Exception\WorkspaceRebaseFailed;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngine;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\StreamName;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\Events;
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

    /*
     * Stubs for all commands to be easily resolvable for IDE's
     */

    /**
     * @When the command AddDimensionShineThrough is executed with payload:
     */
    public function theCommandAddDimensionShineThroughIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(AddDimensionShineThrough::class, $commandArguments);
    }

    /**
     * @When the command MoveDimensionSpacePoint is executed with payload:
     */
    public function theCommandMoveDimensionSpacePointIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(MoveDimensionSpacePoint::class, $commandArguments);
    }

    /**
     * @When the command CreateNodeAggregateWithNode is executed with payload:
     */
    public function theCommandCreateNodeAggregateWithNodeIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(CreateNodeAggregateWithNode::class, $commandArguments);
    }

    /**
     * @When the command SetNodeProperties is executed with payload:
     */
    public function theCommandSetNodePropertiesIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(SetNodeProperties::class, $commandArguments);
    }

    /**
     * @When the command MoveNodeAggregate is executed with payload:
     */
    public function theCommandMoveNodeAggregateIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(MoveNodeAggregate::class, $commandArguments);
    }

    /**
     * @When the command SetNodeReferences is executed with payload:
     */
    public function theCommandSetNodeReferencesIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(SetNodeReferences::class, $commandArguments);
    }

    /**
     * @When the command RemoveNodeAggregate is executed with payload:
     */
    public function theCommandRemoveNodeAggregateIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(RemoveNodeAggregate::class, $commandArguments);
    }

    /**
     * @When the command ChangeNodeAggregateName is executed with payload:
     */
    public function theCommandChangeNodeAggregateNameIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(ChangeNodeAggregateName::class, $commandArguments);
    }

    /**
     * @When the command ChangeNodeAggregateType is executed with payload:
     */
    public function theCommandChangeNodeAggregateTypeIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(ChangeNodeAggregateType::class, $commandArguments);
    }

    /**
     * @When the command CreateNodeVariant is executed with payload:
     */
    public function theCommandCreateNodeVariantIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(CreateNodeVariant::class, $commandArguments);
    }

    /**
     * @When the command CreateRootNodeAggregateWithNode is executed with payload:
     */
    public function theCommandCreateRootNodeAggregateWithNodeIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(CreateRootNodeAggregateWithNode::class, $commandArguments);
    }

    /**
     * @When the command UpdateRootNodeAggregateDimensions is executed with payload:
     */
    public function theCommandUpdateRootNodeAggregateDimensionsIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(UpdateRootNodeAggregateDimensions::class, $commandArguments);
    }

    /**
     * @When the command TagSubtree is executed with payload:
     */
    public function theCommandTagSubtreeIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(TagSubtree::class, $commandArguments);
    }

    /**
     * @When the command UntagSubtree is executed with payload:
     */
    public function theCommandUntagSubtreeIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(UntagSubtree::class, $commandArguments);
    }

    /**
     * @When the command DisableNodeAggregate is executed with payload:
     */
    public function theCommandDisableNodeAggregateIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(DisableNodeAggregate::class, $commandArguments);
    }

    /**
     * @When the command EnableNodeAggregate is executed with payload:
     */
    public function theCommandEnableNodeAggregateIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(EnableNodeAggregate::class, $commandArguments);
    }

    /**
     * @When the command CreateRootWorkspace is executed with payload:
     */
    public function theCommandCreateRootWorkspaceIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(CreateRootWorkspace::class, $commandArguments);
    }

    /**
     * @When the command CreateWorkspace is executed with payload:
     */
    public function theCommandCreateWorkspaceIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(CreateWorkspace::class, $commandArguments);
    }

    /**
     * @When the command ChangeBaseWorkspace is executed with payload:
     */
    public function theCommandChangeBaseWorkspaceIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(ChangeBaseWorkspace::class, $commandArguments);
    }

    /**
     * @When the command DeleteWorkspace is executed with payload:
     */
    public function theCommandDeleteWorkspaceIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(DeleteWorkspace::class, $commandArguments);
    }

    /**
     * @When the command DiscardIndividualNodesFromWorkspace is executed with payload:
     */
    public function theCommandDiscardIndividualNodesFromWorkspaceIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(DiscardIndividualNodesFromWorkspace::class, $commandArguments);
    }

    /**
     * @When the command DiscardWorkspace is executed with payload:
     */
    public function theCommandDiscardWorkspaceIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(DiscardWorkspace::class, $commandArguments);
    }

    /**
     * @When the command PublishIndividualNodesFromWorkspace is executed with payload:
     */
    public function theCommandPublishIndividualNodesFromWorkspaceIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(PublishIndividualNodesFromWorkspace::class, $commandArguments);
    }

    /**
     * @When the command PublishWorkspace is executed with payload:
     */
    public function theCommandPublishWorkspaceIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(PublishWorkspace::class, $commandArguments);
    }

    /**
     * @When the command RebaseWorkspace is executed with payload:
     */
    public function theCommandRebaseWorkspaceIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $this->handleCommand(RebaseWorkspace::class, $commandArguments);
    }

    /**
     * @When the command :shortCommandName is executed with payload and exceptions are caught:
     */
    public function theCommandIsExecutedWithPayloadAndExceptionsAreCaught(string $shortCommandName, TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        try {
            $this->handleCommand(self::resolveShortCommandName($shortCommandName), $commandArguments);
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
        $this->handleCommand(self::resolveShortCommandName($shortCommandName), $commandArguments);
    }

    /**
     * @When the command :shortCommandName is executed with payload :payload and exceptions are caught
     */
    public function theCommandIsExecutedWithJsonPayloadAndExceptionsAreCaught(string $shortCommandName, string $payload): void
    {
        $commandArguments = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        try {
            $this->handleCommand(self::resolveShortCommandName($shortCommandName), $commandArguments);
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
            $this->handleCommand(self::resolveShortCommandName($shortCommandName), $row);
        }
    }

    /**
     * @param class-string<CommandInterface> $commandClassName
     */
    private function handleCommand(string $commandClassName, array $commandArguments): void
    {
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
        if (is_string($commandArguments['coveredDimensionSpacePoint'])) {
            $commandArguments['coveredDimensionSpacePoint'] = \json_decode($commandArguments['coveredDimensionSpacePoint'], true, 512, JSON_THROW_ON_ERROR);
        }
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
            if (empty($commandArguments['nodeName'])) {
                unset($commandArguments['nodeName']);
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
            if (is_string($commandArguments['references'] ?? null)) {
                $commandArguments['references'] = iterator_to_array($this->mapRawNodeReferencesToNodeReferencesToWrite(json_decode($commandArguments['references'], true, 512, JSON_THROW_ON_ERROR)));
            } elseif (is_array($commandArguments['references'] ?? null)) {
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

        // HACK can be replaced, once https://github.com/neos/neos-development-collection/pull/5341 is merged
        $eventStoreAndSubscriptionEngine = new class implements ContentRepositoryServiceFactoryInterface {
            public EventStoreInterface|null $eventStore;
            public SubscriptionEngine|null $subscriptionEngine;
            public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentRepositoryServiceInterface
            {
                $this->eventStore = $serviceFactoryDependencies->eventStore;
                $this->subscriptionEngine = $serviceFactoryDependencies->subscriptionEngine;
                return new class implements ContentRepositoryServiceInterface
                {
                };
            }
        };
        $this->getContentRepositoryService($eventStoreAndSubscriptionEngine);
        $eventStoreAndSubscriptionEngine->eventStore->commit($streamName, Events::with($artificiallyConstructedEvent), ExpectedVersion::ANY());
        $eventStoreAndSubscriptionEngine->subscriptionEngine->catchUpActive();
    }

    /**
     * @Then the last command should have thrown an exception of type :shortExceptionName with code :expectedCode and message:
     * @Then the last command should have thrown an exception of type :shortExceptionName with code :expectedCode
     * @Then the last command should have thrown an exception of type :shortExceptionName with message:
     * @Then the last command should have thrown an exception of type :shortExceptionName
     */
    public function theLastCommandShouldHaveThrown(string $shortExceptionName, ?int $expectedCode = null, ?PyStringNode $expectedMessage = null): void
    {
        if ($shortExceptionName === 'WorkspaceRebaseFailed' || $shortExceptionName === 'PartialWorkspaceRebaseFailed') {
            throw new \RuntimeException('Please use the assertion "the last command should have thrown the WorkspaceRebaseFailed exception with" instead.');
        }

        Assert::assertNotNull($this->lastCommandException, 'Command did not throw exception');
        $lastCommandExceptionShortName = (new \ReflectionClass($this->lastCommandException))->getShortName();
        Assert::assertSame($shortExceptionName, $lastCommandExceptionShortName, sprintf('Actual exception: %s (%s): %s', get_debug_type($this->lastCommandException), $this->lastCommandException->getCode(), $this->lastCommandException->getMessage()));
        if ($expectedCode !== null) {
            Assert::assertSame($expectedCode, $this->lastCommandException->getCode(), sprintf(
                'Expected exception code %s, got exception code %s instead; Message: %s',
                $expectedCode,
                $this->lastCommandException->getCode(),
                $this->lastCommandException->getMessage()
            ));
        }
        if ($expectedMessage !== null) {
            Assert::assertSame($expectedMessage->getRaw(), $this->lastCommandException->getMessage());
        }
        $this->lastCommandException = null;
    }

    /**
     * @Then /^the last command should have thrown the (WorkspaceRebaseFailed|PartialWorkspaceRebaseFailed) exception with:$/
     */
    public function theLastCommandShouldHaveThrownTheWorkspaceRebaseFailedWith(string $shortExceptionName, TableNode $payloadTable)
    {
        /** @var WorkspaceRebaseFailed|PartialWorkspaceRebaseFailed $exception */
        $exception = $this->lastCommandException;
        Assert::assertNotNull($exception, 'Command did not throw exception');

        match ($shortExceptionName) {
            'WorkspaceRebaseFailed' => Assert::assertInstanceOf(WorkspaceRebaseFailed::class, $exception, sprintf('Actual exception: %s (%s): %s', get_class($exception), $exception->getCode(), $exception->getMessage())),
            'PartialWorkspaceRebaseFailed' => Assert::assertInstanceOf(PartialWorkspaceRebaseFailed::class, $exception, sprintf('Actual exception: %s (%s): %s', get_class($exception), $exception->getCode(), $exception->getMessage())),
        };

        $actualComparableHash = [];
        foreach ($exception->conflictingEvents as $conflictingEvent) {
            $actualComparableHash[] = [
                'SequenceNumber' => (string)$conflictingEvent->getSequenceNumber()->value,
                'Event' =>  (new \ReflectionClass($conflictingEvent->getEvent()))->getShortName(),
                'Exception' =>  (new \ReflectionClass($conflictingEvent->getException()))->getShortName(),
            ];
        }

        Assert::assertSame($payloadTable->getHash(), $actualComparableHash);
        $this->lastCommandException = null;
    }

    /**
     * @AfterScenario
     */
    public function ensureNoUnhandledCommandExceptions(\Behat\Behat\Hook\Scope\AfterScenarioScope $event): void
    {
        if ($this->lastCommandException !== null) {
            Assert::fail(sprintf(
                'Last command did throw with exception which was not asserted: %s: "%s" in %s:%s',
                $this->lastCommandException::class,
                $this->lastCommandException->getMessage(),
                $event->getFeature()->getFile(),
                $event->getScenario()->getLine(),
            ));
        }
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
