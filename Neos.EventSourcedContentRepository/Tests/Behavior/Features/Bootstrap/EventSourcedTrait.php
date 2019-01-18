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
use Neos\ContentRepository\Domain\Factory\NodeTypeConstraintFactory;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\ContentRepository\Domain\ValueObject\RootNodeIdentifiers;
use Neos\ContentRepository\Service\AuthorizationService;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Command\ForkContentStream;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamRepository;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\RemoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\RemoveNodesFromAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\Node\NodeCommandHandler;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Domain\Context\Node\SubtreeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\ChangeNodeAggregateType;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeGeneralization;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeSpecialization;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedNeosAdjustments\Domain\Context\Content\NodeAddressFactory;
use Neos\EventSourcing\Event\Decorator\EventWithIdentifier;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\Event\EventTypeResolver;
use Neos\EventSourcing\EventBus\EventBus;
use Neos\EventSourcing\EventStore\EventEnvelope;
use Neos\EventSourcing\EventStore\EventNormalizer;
use Neos\EventSourcing\EventStore\EventStoreManager;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Utility\Arrays;
use PHPUnit\Framework\Assert;

/**
 * Features context
 */
trait EventSourcedTrait
{

    /**
     * @var EventTypeResolver
     */
    private $eventTypeResolver;

    /**
     * @var EventStoreManager
     */
    private $eventStoreManager;

    /**
     * @var WorkspaceFinder
     */
    private $workspaceFinder;

    /**
     * @var NodeTypeConstraintFactory
     */
    private $nodeTypeConstraintFactory;

    /**
     * @var array
     */
    private $currentEventStreamAsArray = null;

    /**
     * @var \Exception
     */
    private $lastCommandException = null;

    /**
     * @var NodeIdentifier
     */
    protected $rootNodeIdentifier;

    /**
     * @var NodeInterface
     */
    protected $currentNode;

    /**
     * @var EventNormalizer
     */
    protected $eventNormalizer;

    /**
     * @var VisibilityConstraints
     */
    protected $visibilityConstraints;

    /**
     * @var EventBus
     */
    private $eventBus;

    /**
     * @var CommandResult
     */
    protected $lastCommandOrEventResult;

    /**
     * @return \Neos\Flow\ObjectManagement\ObjectManagerInterface
     */
    abstract protected function getObjectManager();

    protected function setupEventSourcedTrait()
    {
        $this->nodeAuthorizationService = $this->getObjectManager()->get(AuthorizationService::class);
        $this->nodeTypeManager = $this->getObjectManager()->get(NodeTypeManager::class);
        $this->eventTypeResolver = $this->getObjectManager()->get(EventTypeResolver::class);
        $this->eventStoreManager = $this->getObjectManager()->get(EventStoreManager::class);
        $this->contentGraphInterface = $this->getObjectManager()->get(ContentGraphInterface::class);
        $this->workspaceFinder = $this->getObjectManager()->get(WorkspaceFinder::class);
        $this->nodeTypeConstraintFactory = $this->getObjectManager()->get(NodeTypeConstraintFactory::class);
        $this->eventNormalizer = $this->getObjectManager()->get(EventNormalizer::class);
        $this->eventBus = $this->getObjectManager()->get(EventBus::class);

        $contentStreamRepository = $this->getObjectManager()->get(ContentStreamRepository::class);
        \Neos\Utility\ObjectAccess::setProperty($contentStreamRepository, 'contentStreams', [], true);
    }

    /**
     * @BeforeScenario
     * @return void
     * @throws \Exception
     */
    public function beforeEventSourcedScenarioDispatcher()
    {
        $this->contentGraphInterface->resetCache();
        $this->visibilityConstraints = VisibilityConstraints::frontend();
    }

    /**
     * @Given /^the Event RootNodeWasCreated was published with payload:$/
     * @throws Exception
     */
    public function theEventRootNodeWasCreatedWasPublishedToStreamWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(ContentStreamIdentifier::fromString($eventPayload['contentStreamIdentifier']));
        $this->publishEvent('Neos.EventSourcedContentRepository:RootNodeWasCreated', $streamName->getEventStreamName(), $eventPayload);
        $this->rootNodeIdentifier = NodeIdentifier::fromString($eventPayload['nodeIdentifier']);
    }

    /**
     * @Given /^the Event NodeAggregateWithNodeWasCreated was published with payload:$/
     * @throws Exception
     */
    public function theEventNodeAggregateWithNodeWasCreatedWasPublishedToStreamWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (empty($eventPayload['propertyDefaultValuesAndTypes'])) {
            $eventPayload['propertyDefaultValuesAndTypes'] = [];
        }
        if (empty($eventPayload['dimensionSpacePoint'])) {
            $eventPayload['dimensionSpacePoint'] = [];
        }
        if (empty($eventPayload['visibleInDimensionSpacePoints'])) {
            $eventPayload['visibleInDimensionSpacePoints'] = [[]];
        }

        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(ContentStreamIdentifier::fromString($eventPayload['contentStreamIdentifier']));
        $streamName .= ':NodeAggregate:' . $eventPayload['nodeAggregateIdentifier'];
        $this->publishEvent('Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated', StreamName::fromString($streamName), $eventPayload);
    }

    /**
     * @Given /^the event NodeSpecializationWasCreated was published with payload:$/
     * @throws Exception
     */
    public function theEventNodeSpecializationWasCreatedWasPublishedToStreamWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(ContentStreamIdentifier::fromString($eventPayload['contentStreamIdentifier']));
        $streamName .= ':NodeAggregate:' . $eventPayload['nodeAggregateIdentifier'];
        $this->publishEvent('Neos.EventSourcedContentRepository:NodeSpecializationWasCreated', StreamName::fromString($streamName), $eventPayload);
    }

    /**
     * @Given /^the event NodeGeneralizationWasCreated was published with payload:$/
     * @throws Exception
     */
    public function theEventNodeGeneralizationWasCreatedWasPublishedToStreamWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(ContentStreamIdentifier::fromString($eventPayload['contentStreamIdentifier']));
        $streamName .= ':NodeAggregate:' . $eventPayload['nodeAggregateIdentifier'];
        $this->publishEvent('Neos.EventSourcedContentRepository:NodeGeneralizationWasCreated', StreamName::fromString($streamName), $eventPayload);
    }

    /**
     * @Given /^the Event "([^"]*)" was published to stream "([^"]*)" with payload:$/
     * @throws Exception
     */
    public function theEventWasPublishedToStreamWithPayload($eventType, $streamName, TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        $this->publishEvent($eventType, StreamName::fromString($streamName), $eventPayload);
    }

    /**
     * @param $eventType
     * @param StreamName $streamName
     * @param $eventPayload
     */
    protected function publishEvent($eventType, StreamName $streamName, $eventPayload)
    {
        $event = $this->eventNormalizer->denormalize($eventPayload, $eventType);
        $event = EventWithIdentifier::create($event);
        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($streamName);
        $events = DomainEvents::withSingleEvent($event);
        $eventStore->commit($streamName, $events);
        $this->lastCommandOrEventResult = CommandResult::fromPublishedEvents($events);
    }


    /**
     * @param TableNode $payloadTable
     * @return array
     * @throws Exception
     */
    protected function readPayloadTable(TableNode $payloadTable)
    {
        $eventPayload = [];
        foreach ($payloadTable->getHash() as $line) {
            if (strpos($line['Value'], '$this->') === 0) {
                // Special case: Referencing stuff from the context here
                $propertyName = substr($line['Value'], strlen('$this->'));
                $value = (string) $this->$propertyName;
            } else {
                // default case
                $value = json_decode($line['Value'], true);
                if ($value === null && json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception(sprintf('The value "%s" is no valid JSON string', $line['Value']), 1546522626);
                }
            }

            $eventPayload[$line['Key']] = $value;
        }
        return $eventPayload;
    }

    /**
     * @Given /^the command CreateNodeSpecialization was published with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function theCommandCreateNodeSpecializationIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);

        $command = CreateNodeSpecialization::fromArray($commandArguments);
        /** @var NodeAggregateCommandHandler $commandHandler */
        $commandHandler = $this->getObjectManager()->get(NodeAggregateCommandHandler::class);

        $this->lastCommandOrEventResult = $commandHandler->handleCreateNodeSpecialization($command);
    }

    /**
     * @Given /^the command CreateNodeSpecialization was published with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theCommandCreateNodeSpecializationIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable)
    {
        try {
            $this->theCommandCreateNodeSpecializationIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @Given /^the command RemoveNodeAggregate was published with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     * @throws Exception
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
     * @Given /^the command RemoveNodeAggregate was published with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function theCommandRemoveNodeAggregateIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);

        $command = RemoveNodeAggregate::fromArray($commandArguments);
        /** @var NodeCommandHandler $commandHandler */
        $commandHandler = $this->getObjectManager()->get(NodeCommandHandler::class);

        $this->lastCommandOrEventResult = $commandHandler->handleRemoveNodeAggregate($command);
    }


    /**
     * @Given /^the command RemoveNodesFromAggregate was published with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theCommandRemoveNodesFromAggregateIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable)
    {
        try {
            $this->theCommandRemoveNodesFromAggregateIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @Given /^the command RemoveNodesFromAggregate was published with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function theCommandRemoveNodesFromAggregateIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);

        $command = RemoveNodesFromAggregate::fromArray($commandArguments);
        /** @var NodeCommandHandler $commandHandler */
        $commandHandler = $this->getObjectManager()->get(NodeCommandHandler::class);

        $this->lastCommandOrEventResult = $commandHandler->handleRemoveNodesFromAggregate($command);
    }


    /**
     * @Given /^the command CreateNodeGeneralization was published with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function theCommandCreateNodeGeneralizationIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);

        $command = CreateNodeGeneralization::fromArray($commandArguments);
        /** @var NodeAggregateCommandHandler $commandHandler */
        $commandHandler = $this->getObjectManager()->get(NodeAggregateCommandHandler::class);

        $this->lastCommandOrEventResult = $commandHandler->handleCreateNodeGeneralization($command);
    }

    /**
     * @Given /^the command CreateNodeGeneralization was published with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theCommandCreateNodeGeneralizationIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable)
    {
        try {
            $this->theCommandCreateNodeGeneralizationIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @Given /^the command ChangeNodeAggregateType was published with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theCommandChangeNodeAggregateTypeIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);

        $command = new ChangeNodeAggregateType(
            ContentStreamIdentifier::fromString($commandArguments['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($commandArguments['nodeAggregateIdentifier']),
            NodeTypeName::fromString($commandArguments['newNodeTypeName']),
            $commandArguments['strategy'] ? new NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy($commandArguments['strategy']) : null
        );
        /** @var NodeAggregateCommandHandler $commandHandler */
        $commandHandler = $this->getObjectManager()->get(NodeAggregateCommandHandler::class);

        $commandHandler->handleChangeNodeAggregateType($command);
    }

    /**
     * @Given /^the command ChangeNodeAggregateType was published with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theCommandChangeNodeAggregateTypeIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable)
    {
        try {
            $this->theCommandChangeNodeAggregateTypeIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @When /^the command "([^"]*)" is executed with payload:$/
     * @Given /^the command "([^"]*)" was executed with payload:$/
     * @throws Exception
     */
    public function theCommandIsExecutedWithPayload($shortCommandName, TableNode $payloadTable = null, $commandArguments = null)
    {
        list($commandClassName, $commandHandlerClassName, $commandHandlerMethod) = self::resolveShortCommandName($shortCommandName);
        if ($commandArguments === null && $payloadTable !== null) {
            $commandArguments = $this->readPayloadTable($payloadTable);
        }

        if (!method_exists($commandClassName, 'fromArray')) {
            throw new \InvalidArgumentException(sprintf('Command "%s" does not implement a static "fromArray" constructor', $commandClassName), 1545564621);
        }
        $command = $commandClassName::fromArray($commandArguments);

        $commandHandler = $this->getObjectManager()->get($commandHandlerClassName);

        $this->lastCommandOrEventResult = $commandHandler->$commandHandlerMethod($command);

        if (isset($commandArguments['rootNodeIdentifier'])) {
            $this->rootNodeIdentifier = NodeIdentifier::fromString($commandArguments['rootNodeIdentifier']);
        } elseif ($commandClassName === \Neos\EventSourcedContentRepository\Domain\Context\Node\Command\CreateRootNode::class) {
            $this->rootNodeIdentifier = NodeIdentifier::fromString($commandArguments['nodeIdentifier']);
        }
    }

    /**
     * @When /^the command CreateWorkspace is executed with payload:$/
     * @throws Exception
     */
    public function theCommandCreateWorkspaceIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);

        $commandArguments['workspaceTitle'] = ucfirst($commandArguments['workspaceName']);
        $commandArguments['workspaceDescription'] = 'The workspace "' . $commandArguments['workspaceName'] . '".';
        $commandArguments['initiatingUserIdentifier'] = 'initiatingUserIdentifier';
        $commandArguments['rootNodeTypeName'] = !empty($commandArguments['rootNodeTypeName']) ? $commandArguments['rootNodeTypeName'] : 'Neos.ContentRepository:Root';

        $commandName = 'CreateRootWorkspace';
        if (!empty($commandArguments['baseWorkspaceName'])) {
            $commandArguments['workspaceOwner'] = 'workspaceOwner';
            $commandName = 'CreateWorkspace';
        }

        $this->theCommandIsExecutedWithPayload($commandName, null, $commandArguments);
    }

    /**
     * @When /^the command "([^"]*)" is executed with payload and exceptions are caught:$/
     */
    public function theCommandIsExecutedWithPayloadAndExceptionsAreCaught($shortCommandName, TableNode $payloadTable)
    {
        try {
            $this->theCommandIsExecutedWithPayload($shortCommandName, $payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @Then /^the last command should have thrown an exception of type "([^"]*)"$/
     * @throws Exception
     */
    public function theLastCommandShouldHaveThrown($shortExceptionName)
    {
        Assert::assertNotNull($this->lastCommandException, 'Command did not throw exception');
        $lastCommandExceptionShortName = (new \ReflectionClass($this->lastCommandException))->getShortName();
        Assert::assertSame($shortExceptionName, $lastCommandExceptionShortName, sprintf('Actual exception: %s (%s): %s', get_class($this->lastCommandException), $this->lastCommandException->getCode(), $this->lastCommandException->getMessage()));
    }

    /**
     * @param $shortCommandName
     * @return array
     * @throws Exception
     */
    protected static function resolveShortCommandName($shortCommandName)
    {
        switch ($shortCommandName) {
            case 'CreateRootWorkspace':
                return [
                    \Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\CreateRootWorkspace::class,
                    \Neos\EventSourcedContentRepository\Domain\Context\Workspace\WorkspaceCommandHandler::class,
                    'handleCreateRootWorkspace'
                ];
            case 'CreateWorkspace':
                return [
                    \Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\CreateWorkspace::class,
                    \Neos\EventSourcedContentRepository\Domain\Context\Workspace\WorkspaceCommandHandler::class,
                    'handleCreateWorkspace'
                ];
            case 'PublishWorkspace':
                return [
                    \Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\PublishWorkspace::class,
                    \Neos\EventSourcedContentRepository\Domain\Context\Workspace\WorkspaceCommandHandler::class,
                    'handlePublishWorkspace'
                ];
            case 'RebaseWorkspace':
                return [
                    \Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\RebaseWorkspace::class,
                    \Neos\EventSourcedContentRepository\Domain\Context\Workspace\WorkspaceCommandHandler::class,
                    'handleRebaseWorkspace'
                ];
            case 'CreateRootNode':
                return [
                    \Neos\EventSourcedContentRepository\Domain\Context\Node\Command\CreateRootNode::class,
                    NodeCommandHandler::class,
                    'handleCreateRootNode'
                ];
            case 'AddNodeToAggregate':
                return [
                    \Neos\EventSourcedContentRepository\Domain\Context\Node\Command\AddNodeToAggregate::class,
                    NodeCommandHandler::class,
                    'handleAddNodeToAggregate'
                ];
            case 'CreateNodeAggregateWithNode':
                return [
                    \Neos\EventSourcedContentRepository\Domain\Context\Node\Command\CreateNodeAggregateWithNode::class,
                    NodeCommandHandler::class,
                    'handleCreateNodeAggregateWithNode'
                ];
            case 'ForkContentStream':
                return [
                    ForkContentStream::class,
                    ContentStreamCommandHandler::class,
                    'handleForkContentStream'
                ];
            case 'ChangeNodeName':
                return [
                    \Neos\EventSourcedContentRepository\Domain\Context\Node\Command\ChangeNodeName::class,
                    NodeCommandHandler::class,
                    'handleChangeNodeName'
                ];
            case 'SetNodeProperty':
                return [
                    \Neos\EventSourcedContentRepository\Domain\Context\Node\Command\SetNodeProperty::class,
                    NodeCommandHandler::class,
                    'handleSetNodeProperty'
                ];
            case 'HideNode':
                return [
                    \Neos\EventSourcedContentRepository\Domain\Context\Node\Command\HideNode::class,
                    NodeCommandHandler::class,
                    'handleHideNode'
                ];
            case 'ShowNode':
                return [
                    \Neos\EventSourcedContentRepository\Domain\Context\Node\Command\ShowNode::class,
                    NodeCommandHandler::class,
                    'handleShowNode'
                ];
            case 'MoveNode':
                return [
                    \Neos\EventSourcedContentRepository\Domain\Context\Node\Command\MoveNode::class,
                    NodeCommandHandler::class,
                    'handleMoveNode'
                ];
            case 'TranslateNodeInAggregate':
                return [
                    \Neos\EventSourcedContentRepository\Domain\Context\Node\Command\TranslateNodeInAggregate::class,
                    NodeCommandHandler::class,
                    'handleTranslateNodeInAggregate'
                ];
            case 'SetNodeReferences':
                return [
                    \Neos\EventSourcedContentRepository\Domain\Context\Node\Command\SetNodeReferences::class,
                    NodeCommandHandler::class,
                    'handleSetNodeReferences'
                ];

            default:
                throw new \Exception('The short command name "' . $shortCommandName . '" is currently not supported by the tests.');
        }
    }

    /**
     * @Then /^I expect exactly (\d+) events? to be published on stream "([^"]*)"$/
     * @throws \Neos\EventSourcing\EventStore\Exception\EventStreamNotFoundException
     */
    public function iExpectExactlyEventToBePublishedOnStream($numberOfEvents, $streamName)
    {
        $streamName = StreamName::fromString($streamName);
        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($streamName);
        $stream = $eventStore->load($streamName);
        $this->currentEventStreamAsArray = iterator_to_array($stream, false);
        Assert::assertEquals($numberOfEvents, count($this->currentEventStreamAsArray), 'Number of events did not match');
    }

    /**
     * @Then /^I expect exactly (\d+) events? to be published on stream with prefix "([^"]*)"$/
     * @throws \Neos\EventSourcing\EventStore\Exception\EventStreamNotFoundException
     */
    public function iExpectExactlyEventToBePublishedOnStreamWithPrefix($numberOfEvents, $streamName)
    {
        $streamName = StreamName::forCategory($streamName);

        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($streamName);
        $stream = $eventStore->load($streamName);
        $this->currentEventStreamAsArray = iterator_to_array($stream, false);
        Assert::assertEquals($numberOfEvents, count($this->currentEventStreamAsArray), 'Number of events did not match');
    }

    /**
     * @Then /^event at index (\d+) is of type "([^"]*)" with payload:/
     */
    public function eventNumberIs($eventNumber, $eventType, TableNode $payloadTable)
    {
        if ($this->currentEventStreamAsArray === null) {
            Assert::fail('Step \'I expect exactly ? events to be published on stream "?"\' was not executed');
        }

        Assert::assertArrayHasKey($eventNumber, $this->currentEventStreamAsArray, 'Event at index does not exist');

        /* @var $actualEvent EventEnvelope */
        $actualEvent = $this->currentEventStreamAsArray[$eventNumber];

        Assert::assertNotNull($actualEvent, sprintf('Event with number %d not found', $eventNumber));
        Assert::assertEquals($eventType, $actualEvent->getRawEvent()->getType(), 'Event Type does not match: "' . $actualEvent->getRawEvent()->getType() . '" !== "' . $eventType . '"');

        $actualEventPayload = $actualEvent->getRawEvent()->getPayload();

        foreach ($payloadTable->getHash() as $assertionTableRow) {
            $actualValue = Arrays::getValueByPath($actualEventPayload, $assertionTableRow['Key']);
            Assert::assertJsonStringEqualsJsonString($assertionTableRow['Expected'], json_encode($actualValue));
        }
    }


    /**
     * @When /^the graph projection is fully up to date$/
     */
    public function theGraphProjectionIsFullyUpToDate()
    {
        if ($this->lastCommandOrEventResult === null) {
            throw new \RuntimeException('lastCommandOrEventResult not filled; so I cannot block!');
        }
        $this->lastCommandOrEventResult->blockUntilProjectionsAreUpToDate();
        $this->lastCommandOrEventResult = null;
    }

    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var DimensionSpacePoint
     */
    private $dimensionSpacePoint;

    /**
     * @var ContentGraphInterface
     */
    private $contentGraphInterface;

    /**
     * @Given /^I am in the active content stream of workspace "([^"]*)" and Dimension Space Point (.*)$/
     */
    public function iAmInTheActiveContentStreamOfWorkspaceAndDimensionSpacePoint(string $workspaceName, string $dimensionSpacePoint)
    {
        $workspaceName = new WorkspaceName($workspaceName);
        $workspace = $this->workspaceFinder->findOneByName($workspaceName);
        if ($workspace === null) {
            throw new \Exception(sprintf('Workspace "%s" does not exist, projection not yet up to date?', $workspaceName), 1548149355);
        }
        $this->contentStreamIdentifier = $workspace->getCurrentContentStreamIdentifier();
        $this->dimensionSpacePoint = new DimensionSpacePoint(json_decode($dimensionSpacePoint, true));
    }

    /**
     * @Given /^I am in content stream "([^"]*)" and Dimension Space Point (.*)$/
     */
    public function iAmInContentStreamAndDimensionSpacePoint(string $contentStreamIdentifier, string $dimensionSpacePoint)
    {
        $this->contentStreamIdentifier = ContentStreamIdentifier::fromString($contentStreamIdentifier);
        $this->dimensionSpacePoint = new DimensionSpacePoint(json_decode($dimensionSpacePoint, true));
    }

    /**
     * @Then /^workspace "([^"]*)" points to another content stream than workspace "([^"]*)"$/
     * @param string $rawWorkspaceNameA
     * @param string $rawWorkspaceNameB
     */
    public function workspacesPointToDifferentContentStreams(string $rawWorkspaceNameA, string $rawWorkspaceNameB)
    {
        $workspaceA = $this->workspaceFinder->findOneByName(new WorkspaceName($rawWorkspaceNameA));
        Assert::assertInstanceOf(\Neos\EventSourcedContentRepository\Domain\Projection\Workspace\Workspace::class, $workspaceA, 'Workspace "' . $rawWorkspaceNameA . '" does not exist.');
        $workspaceB = $this->workspaceFinder->findOneByName(new WorkspaceName($rawWorkspaceNameB));
        Assert::assertInstanceOf(\Neos\EventSourcedContentRepository\Domain\Projection\Workspace\Workspace::class, $workspaceB, 'Workspace "' . $rawWorkspaceNameB . '" does not exist.');
        if ($workspaceA && $workspaceB) {
            Assert::assertNotEquals(
                $workspaceA->getCurrentContentStreamIdentifier(),
                $workspaceB->getCurrentContentStreamIdentifier(),
                'Workspace "' . $rawWorkspaceNameA . '" points to the same content stream as "' . $rawWorkspaceNameB . '"');
        }
    }

    /**
     * @Then /^workspace "([^"]*)" does not point to content stream "([^"]*)"$/
     * @param string $rawWorkspaceName
     * @param string $rawContentStreamIdentifier
     */
    public function workspaceDoesNotPointToContentStream(string $rawWorkspaceName, string $rawContentStreamIdentifier)
    {
        $workspace = $this->workspaceFinder->findOneByName(new WorkspaceName($rawWorkspaceName));

        Assert::assertNotEquals($rawContentStreamIdentifier, (string)$workspace->getCurrentContentStreamIdentifier());
    }

    /**
     * @deprecated use "a node aggregate"
     * @Then /^I expect a node "([^"]*)" to exist in the graph projection$/
     */
    public function iExpectANodeToExistInTheGraphProjection($nodeIdentifier)
    {
        $nodeIdentifier = NodeIdentifier::fromString($nodeIdentifier);
        $node = $this->contentGraphInterface
            ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints)
            ->findNodeByIdentifier($nodeIdentifier);
        Assert::assertNotNull($node, sprintf('Node "%s" was not found in the current Content Stream "%s" / Dimension Space Point "%s".', $nodeIdentifier, $this->contentStreamIdentifier, $this->dimensionSpacePoint->getHash()));
    }

    /**
     * @deprecated use "a node aggregate"
     * @Then /^I expect a node "([^"]*)" not to exist in the graph projection$/
     */
    public function iExpectANodeNotToExistInTheGraphProjection($nodeIdentifier)
    {
        $node = $this->contentGraphInterface
            ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints)
            ->findNodeByIdentifier(NodeIdentifier::fromString($nodeIdentifier));
        // we're not using assertNull here: If $node is not null, the
        Assert::assertTrue($node === null, 'Node "' . $nodeIdentifier . '" was found in the current Content Stream / Dimension Space Point.');
    }

    /**
     * @Then /^I expect a node identified by aggregate identifier "([^"]*)" to exist in the subgraph$/
     * @param string $nodeAggregateIdentifier
     */
    public function iExpectANodeIdentifiedByAggregateIdentifierToExistInTheSubgraph(string $nodeAggregateIdentifier)
    {
        $this->currentNode = $this->contentGraphInterface
            ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints)
            ->findNodeByNodeAggregateIdentifier(NodeAggregateIdentifier::fromString($nodeAggregateIdentifier));
        Assert::assertNotNull($this->currentNode, sprintf('Node with aggregate identifier "%s" was not found in the current Content Stream ("%s") / Dimension Space Point ("%s").', $nodeAggregateIdentifier, $this->contentStreamIdentifier, $this->dimensionSpacePoint));
    }

    /**
     * @Then /^I expect a node identified by aggregate identifier "([^"]*)" not to exist in the subgraph$/
     * @param string $nodeAggregateIdentifier
     */
    public function iExpectANodeIdentifiedByAggregateIdentifierNotToExistInTheSubgraph(string $nodeAggregateIdentifier)
    {
        $node = $this->contentGraphInterface
            ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints)
            ->findNodeByNodeAggregateIdentifier(NodeAggregateIdentifier::fromString($nodeAggregateIdentifier));
        Assert::assertTrue($node === null, 'Node with aggregate identifier "' . $nodeAggregateIdentifier . '" was found in the current Content Stream / Dimension Space Point, but it SHOULD NOT BE FOUND.');
    }

    /**
     * @Then /^I expect the node aggregate "([^"]*)" to have the following child nodes:$/
     */
    public function iExpectTheNodeToHaveTheFollowingChildNodes($nodeAggregateIdentifier, TableNode $expectedChildNodesTable)
    {
        $nodeAggregateIdentifier = $nodeAggregateIdentifier === 'root' ? RootNodeIdentifiers::rootNodeAggregateIdentifier() : NodeAggregateIdentifier::fromString($nodeAggregateIdentifier);
        $nodes = $this->contentGraphInterface
            ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints)
            ->findChildNodes($nodeAggregateIdentifier);

        $numberOfChildNodes = $this->contentGraphInterface
            ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints)
            ->countChildNodes($nodeAggregateIdentifier);

        Assert::assertEquals(count($expectedChildNodesTable->getHash()), $numberOfChildNodes, 'ContentSubgraph::countChildNodes returned a wrong value');
        Assert::assertCount(count($expectedChildNodesTable->getHash()), $nodes, 'ContentSubgraph::findChildNodes: Child Node Count does not match');
        foreach ($expectedChildNodesTable->getHash() as $index => $row) {
            Assert::assertEquals($row['Name'], (string)$nodes[$index]->getNodeName(), 'ContentSubgraph::findChildNodes: Node name in index ' . $index . ' does not match. Actual: ' . $nodes[$index]->getNodeName());
            Assert::assertEquals($row['NodeIdentifier'], (string)$nodes[$index]->getNodeIdentifier(), 'ContentSubgraph::findChildNodes: Node identifier in index ' . $index . ' does not match. Actual: ' . $nodes[$index]->getNodeIdentifier() . ' Expected: ' . $row['NodeIdentifier']);
        }
    }

    /**
     * @Then /^I expect the Node Aggregate "([^"]*)" to resolve to node "([^"]*)"$/
     */
    public function iExpectTheNodeAggregateToHaveTheNodes($nodeAggregateIdentifier, $nodeIdentifier)
    {
        $node = $this->contentGraphInterface
            ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints)
            ->findNodeByNodeAggregateIdentifier(NodeAggregateIdentifier::fromString($nodeAggregateIdentifier));
        Assert::assertNotNull($node, 'Node with ID "' . $nodeIdentifier . '" not found!');
        Assert::assertEquals($nodeIdentifier, (string)$node->getNodeIdentifier(), 'Node ID does not match!');
    }


    /**
     * @Then /^I expect the Node "([^"]*)" to have the type "([^"]*)"$/
     */
    public function iExpectTheNodeToHaveTheType($nodeIdentifier, $nodeType)
    {
        $node = $this->contentGraphInterface
            ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints)
            ->findNodeByIdentifier(NodeIdentifier::fromString($nodeIdentifier));
        Assert::assertEquals($nodeType, (string)$node->getNodeTypeName(), 'Node Type names do not match');
    }

    /**
     * @Then /^I expect the Node "([^"]*)" to have the properties:$/
     */
    public function iExpectTheNodeToHaveTheProperties($nodeIdentifier, TableNode $expectedProperties)
    {
        // TODO the following line is required in order to avoid cached results from previous calls
        $this->contentGraphInterface->resetCache();

        $this->currentNode = $this->contentGraphInterface
            ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints)
            ->findNodeByIdentifier(NodeIdentifier::fromString($nodeIdentifier));
        $this->iExpectTheCurrentNodeToHaveTheProperties($expectedProperties);
    }

    /**
     * @Then /^I expect the current Node to have the properties:$/
     */
    public function iExpectTheCurrentNodeToHaveTheProperties(TableNode $expectedProperties)
    {
        // TODO hack: $this->currentNode might be stale, so we need to re-fetch it
        $this->contentGraphInterface->resetCache();
        $this->currentNode = $this->contentGraphInterface
            ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints)
            ->findNodeByNodeAggregateIdentifier($this->currentNode->getNodeAggregateIdentifier());

        $properties = $this->currentNode->getProperties();
        foreach ($expectedProperties->getHash() as $row) {
            Assert::assertArrayHasKey($row['Key'], $properties, 'Property "' . $row['Key'] . '" not found');
            $actualProperty = $properties[$row['Key']];
            Assert::assertEquals($row['Value'], $actualProperty, 'Node property ' . $row['Key'] . ' does not match. Expected: ' . $row['Value'] . '; Actual: ' . $actualProperty);
        }
    }

    /**
     * @Then /^I expect the Node aggregate "([^"]*)" to have the references:$/
     */
    public function iExpectTheNodeToHaveTheReferences($nodeAggregateIdentifier, TableNode $expectedReferences)
    {
        $expectedReferences = $this->readPayloadTable($expectedReferences);

        /** @var \Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface $subgraph */
        $subgraph = $this->contentGraphInterface->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints);

        foreach ($expectedReferences as $propertyName => $expectedDestinationNodeAggregateIdentifiers) {
            $destinationNodes = $subgraph->findReferencedNodes(NodeAggregateIdentifier::fromString($nodeAggregateIdentifier), PropertyName::fromString($propertyName));
            $destinationNodeAggregateIdentifiers = array_map(
                function ($item) {
                    if ($item instanceof NodeInterface) {
                        return (string)$item->getNodeAggregateIdentifier();
                    } else {
                        return $item;
                    }
                },
                $destinationNodes
            );
            Assert::assertEquals($expectedDestinationNodeAggregateIdentifiers, $destinationNodeAggregateIdentifiers, 'Node references ' . $propertyName . ' does not match. Expected: ' . json_encode($expectedDestinationNodeAggregateIdentifiers) . '; Actual: ' . json_encode($destinationNodeAggregateIdentifiers));
        }
    }

    /**
     * @Then /^I expect the Node aggregate "([^"]*)" to be referenced by:$/
     */
    public function iExpectTheNodeToBeReferencedBy($nodeAggregateIdentifier, TableNode $expectedReferences)
    {
        $expectedReferences = $this->readPayloadTable($expectedReferences);

        /** @var \Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface $subgraph */
        $subgraph = $this->contentGraphInterface->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints);

        foreach ($expectedReferences as $propertyName => $expectedDestinationNodeAggregateIdentifiers) {
            $destinationNodes = $subgraph->findReferencingNodes(NodeAggregateIdentifier::fromString($nodeAggregateIdentifier), PropertyName::fromString($propertyName));
            $destinationNodeAggregateIdentifiers = array_map(
                function ($item) {
                    if ($item instanceof NodeInterface) {
                        return (string)$item->getNodeAggregateIdentifier();
                    } else {
                        return $item;
                    }
                },
                $destinationNodes
            );

            // since the order on the target side is not defined we sort
            // expectation and result before comparison
            sort($expectedDestinationNodeAggregateIdentifiers);
            sort($destinationNodeAggregateIdentifiers);
            Assert::assertEquals($expectedDestinationNodeAggregateIdentifiers, $destinationNodeAggregateIdentifiers, 'Node references ' . $propertyName . ' does not match. Expected: ' . json_encode($expectedDestinationNodeAggregateIdentifiers) . '; Actual: ' . json_encode($destinationNodeAggregateIdentifiers));
        }
    }

    /**
     * @Then /^I expect the path "([^"]*)" to lead to the node "([^"]*)"$/
     * @throws Exception
     */
    public function iExpectThePathToLeadToTheNode($nodePath, $nodeIdentifier)
    {
        if (!$this->rootNodeIdentifier) {
            throw new \Exception('ERROR: RootNodeIdentifier needed for running this step. You need to use "the Event RootNodeWasCreated was published with payload" to create a root node..');
        }
        $this->currentNode = $this->contentGraphInterface
            ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints)
            ->findNodeByPath($nodePath, RootNodeIdentifiers::rootNodeAggregateIdentifier());
        Assert::assertNotNull($this->currentNode, 'Did not find node at path "' . $nodePath . '"');
        Assert::assertEquals($nodeIdentifier, (string)$this->currentNode->getNodeIdentifier(), 'Node identifier does not match.');
    }

    /**
     * @When /^I go to the parent node of node aggregate "([^"]*)"$/
     */
    public function iGoToTheParentNodeOfNode($nodeAggregateIdentifier)
    {
        $this->currentNode = $this->contentGraphInterface
            ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints)
            ->findParentNode(NodeAggregateIdentifier::fromString($nodeAggregateIdentifier));
    }

    /**
     * @Then /^I do not find any node$/
     */
    public function currentNodeIsNull()
    {
        if ($this->currentNode) {
            Assert::fail('Current node was not NULL, but node aggregate: ' . $this->currentNode->getNodeAggregateIdentifier());
        } else {
            Assert::assertTrue(true);
        }
    }

    /**
     * @Then /^I find a node with node aggregate "([^"]*)"$/
     */
    public function currentNodeAggregateShouldBe($nodeAggregateIdentifier)
    {
        Assert::assertEquals($nodeAggregateIdentifier, (string)$this->currentNode->getNodeAggregateIdentifier());
    }

    /**
     * @Then /^I expect the path "([^"]*)" to lead to no node$/
     * @throws Exception
     */
    public function iExpectThePathToLeadToNoNode($nodePath)
    {
        if (!$this->rootNodeIdentifier) {
            throw new \Exception('ERROR: RootNodeIdentifier needed for running this step. You need to use "the Event RootNodeWasCreated was published with payload" to create a root node..');
        }
        $node = $this->contentGraphInterface
            ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints)
            ->findNodeByPath($nodePath, RootNodeIdentifiers::rootNodeAggregateIdentifier());
        Assert::assertNull($node, 'Did find node at path "' . $nodePath . '"');
    }

    /**
     * @When /^VisibilityConstraints are set to "(withoutRestrictions|frontend)"$/
     * @param string $restrictionType
     */
    public function visibilityConstraintsAreSetTo(string $restrictionType)
    {
        switch ($restrictionType) {
            case 'withoutRestrictions':
                $this->visibilityConstraints = VisibilityConstraints::withoutRestrictions();
                break;
            case 'frontend':
                $this->visibilityConstraints = VisibilityConstraints::frontend();
                break;
            default:
                throw new \InvalidArgumentException('Visibility constraint "' . $restrictionType . '" not supported.');
        }
    }


    /**
     * @Then /^the subtree for node aggregate "([^"]*)" with node types "([^"]*)" and (\d+) levels deep should be:$/
     */
    public function theSubtreeForNodeAggregateWithNodeTypesAndLevelsDeepShouldBe($nodeAggregateIdentifier, $nodeTypeConstraints, $maximumLevels, TableNode $table)
    {
        $expectedRows = $table->getHash();
        $nodeAggregateIdentifier = NodeAggregateIdentifier::fromString($nodeAggregateIdentifier);
        $nodeTypeConstraints = $this->nodeTypeConstraintFactory->parseFilterString($nodeTypeConstraints);

        $subtree = $this->contentGraphInterface
            ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints)
            ->findSubtrees([$nodeAggregateIdentifier], (int)$maximumLevels, $nodeTypeConstraints);

        /** @var SubtreeInterface[] $flattenedSubtree */
        $flattenedSubtree = [];
        self::flattenSubtreeForComparison($subtree, $flattenedSubtree);

        Assert::assertEquals(count($expectedRows), count($flattenedSubtree), 'number of expected subtrees do not match');

        foreach ($expectedRows as $i => $expectedRow) {
            Assert::assertEquals($expectedRow['Level'], $flattenedSubtree[$i]->getLevel(), 'Level does not match in index ' . $i);
            if ($expectedRow['NodeAggregateIdentifier'] === 'root') {
                Assert::assertNull($flattenedSubtree[$i]->getNode(), 'root node was not correct at index ' . $i);
            } else {
                Assert::assertEquals($expectedRow['NodeAggregateIdentifier'], (string)$flattenedSubtree[$i]->getNode()->getNodeAggregateIdentifier(), 'NodeAggregateIdentifier does not match in index ' . $i . ', expected: ' . $expectedRow['NodeAggregateIdentifier'] . ', actual: ' . $flattenedSubtree[$i]->getNode()->getNodeAggregateIdentifier());
            }
        }
    }

    private static function flattenSubtreeForComparison(SubtreeInterface $subtree, array &$result)
    {
        $result[] = $subtree;
        foreach ($subtree->getChildren() as $childSubtree) {
            self::flattenSubtreeForComparison($childSubtree, $result);
        }
    }

    /**
     * @var \Neos\EventSourcedNeosAdjustments\Domain\Context\Content\NodeAddress[]
     */
    private $currentNodeAddresses;

    /**
     * @return \Neos\EventSourcedNeosAdjustments\Domain\Context\Content\NodeAddress
     */
    protected function getCurrentNodeAddress(string $alias = null): \Neos\EventSourcedNeosAdjustments\Domain\Context\Content\NodeAddress
    {
        if ($alias === null) {
            $alias = 'DEFAULT';
        }
        return $this->currentNodeAddresses[$alias];
    }

    /**
     * @return \Neos\EventSourcedNeosAdjustments\Domain\Context\Content\NodeAddress[]
     */
    public function getCurrentNodeAddresses(): array
    {
        return $this->currentNodeAddresses;
    }

    /**
     * @Given /^I get the node address for node aggregate "([^"]*)"(?:, remembering it as "([^"]*)")?$/
     */
    public function iGetTheNodeAddressForNodeAggregate($nodeAggregateIdentifier, $alias = 'DEFAULT')
    {
        $node = $this->contentGraphInterface
            ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints)
            ->findNodeByNodeAggregateIdentifier(NodeAggregateIdentifier::fromString($nodeAggregateIdentifier));
        Assert::assertNotNull($node, 'Did find node with aggregate identifier "' . $nodeAggregateIdentifier . '"');

        /* @var $nodeAddressFactory NodeAddressFactory */
        $nodeAddressFactory = $this->getObjectManager()->get(NodeAddressFactory::class);
        $this->currentNodeAddresses[$alias] = $nodeAddressFactory->createFromNode($node);
    }

    /**
     * @Then /^I get the node address for the node at path "([^"]*)"(?:, remembering it as "([^"]*)")?$/
     * @throws Exception
     */
    public function iGetTheNodeAddressForTheNodeAtPath($nodePath, $alias = 'DEFAULT')
    {
        $node = $this->contentGraphInterface
            ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints)
            ->findNodeByPath($nodePath, RootNodeIdentifiers::rootNodeAggregateIdentifier());
        Assert::assertNotNull($node, 'Did find node at path "' . $nodePath . '"');

        /* @var $nodeAddressFactory NodeAddressFactory */
        $nodeAddressFactory = $this->getObjectManager()->get(NodeAddressFactory::class);
        $this->currentNodeAddresses[$alias] = $nodeAddressFactory->createFromNode($node);
    }

    /**
     * @Then /^I get the node at path "([^"]*)"$/
     * @throws Exception
     */
    public function iGetTheNodeAtPath($nodePath)
    {
        $this->currentNode = $this->contentGraphInterface
            ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints)
            ->findNodeByPath($nodePath, RootNodeIdentifiers::rootNodeAggregateIdentifier());
    }
}
