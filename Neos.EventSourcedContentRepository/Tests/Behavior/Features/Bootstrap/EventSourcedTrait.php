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
use GuzzleHttp\Psr7\Uri;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentGraph as DbalContentGraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\ContentHypergraph as PostgreSQLContentHypergraph;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraintFactory;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Exception\NodeException;
use Neos\ContentRepository\Intermediary\Domain\Command\PropertyValuesToWrite;
use Neos\ContentRepository\Intermediary\Tests\Behavior\Fixtures\PostalAddress;
use Neos\ContentRepository\Service\AuthorizationService;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Command\ForkContentStream;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Exception\ContentStreamAlreadyExists;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Exception\ContentStreamDoesNotExistYet;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamRepository;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeAggregateWithNodeAndSerializedProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\DisableNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\RemoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetNodeReferences;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\EnableNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetSerializedNodeProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregateCurrentlyExists;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\ChangeNodeAggregateName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeVariant;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\MoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\DimensionSpacePointIsAlreadyOccupied;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\DimensionSpacePointIsNotYetOccupied;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifierCollection;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\RelationDistributionStrategy;
use Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication\Command\CopyNodesRecursively;
use Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication\Command\Dto\NodeSubtreeSnapshot;
use Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication\NodeDuplicationCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\CreateRootWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\CreateWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\DiscardIndividualNodesFromWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\DiscardWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\PublishIndividualNodesFromWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\PublishWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\RebaseWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Exception\BaseWorkspaceDoesNotExist;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Exception\BaseWorkspaceHasBeenModifiedInTheMeantime;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Exception\WorkspaceDoesNotExist;
use Neos\EventSourcedContentRepository\Domain\Projection\ContentStream\ContentStreamFinder;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\Workspace;
use Neos\EventSourcedContentRepository\Domain\CommandResult;
use Neos\EventSourcedContentRepository\Domain\Context\ContentSubgraph\SubtreeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\ChangeNodeAggregateType;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\WorkspaceCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceDescription;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceTitle;
use Neos\EventSourcedContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\EventSourcedContentRepository\Service\ContentStreamPruner;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Tests\Behavior\Features\Helper\ContentGraphs;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\Event\EventTypeResolver;
use Neos\EventSourcing\EventStore\EventEnvelope;
use Neos\EventSourcing\EventStore\EventNormalizer;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\EventStoreFactory;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\Arrays;
use Neos\Utility\ObjectAccess;
use PHPUnit\Framework\Assert;
use Ramsey\Uuid\Uuid;

/**
 * Features context
 */
trait EventSourcedTrait
{
    use CurrentSubgraphTrait;
    use ProjectedNodeAggregateTrait;
    use ProjectedNodeTrait;
    use NodeCreation;
    use NodeDisabling;

    /**
     * @var EventTypeResolver
     */
    private $eventTypeResolver;

    /**
     * @var EventStore
     */
    private $eventStore;

    protected ContentGraphs $contentGraphs;

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

    protected ?\Exception $lastCommandException = null;

    protected ?ContentStreamIdentifier $contentStreamIdentifier = null;

    protected ?UserIdentifier $currentUserIdentifier = null;

    protected ?DimensionSpacePoint $dimensionSpacePoint = null;

    /**
     * @var NodeAggregateIdentifier
     */
    protected $rootNodeAggregateIdentifier;

    /**
     * @var EventNormalizer
     */
    protected $eventNormalizer;

    protected ?VisibilityConstraints $visibilityConstraints = null;

    /**
     * @var CommandResult
     */
    protected $lastCommandOrEventResult;

    /**
     * @var RuntimeBlocker
     */
    protected $runtimeBlocker;

    /**
     * @var array|\Neos\EventSourcing\Projection\ProjectorInterface[]
     */
    private array $projectorsToBeReset = [];

    /**
     * @return ObjectManagerInterface
     */
    abstract protected function getObjectManager();

    protected function getWorkspaceFinder(): WorkspaceFinder
    {
        return $this->workspaceFinder;
    }

    protected function getContentGraphs(): ContentGraphs
    {
        return $this->contentGraphs;
    }

    protected function setupEventSourcedTrait()
    {
        $this->nodeAuthorizationService = $this->getObjectManager()->get(AuthorizationService::class);
        $this->nodeTypeManager = $this->getObjectManager()->get(NodeTypeManager::class);
        $this->eventTypeResolver = $this->getObjectManager()->get(EventTypeResolver::class);
        /* @var $eventStoreFactory EventStoreFactory */
        $eventStoreFactory = $this->getObjectManager()->get(EventStoreFactory::class);
        $this->eventStore = $eventStoreFactory->create('ContentRepository');
        $this->contentGraphs = new ContentGraphs([
            'DoctrineDbal' => $this->getObjectManager()->get(DbalContentGraph::class),
            'PostgreSQL' => $this->getObjectManager()->get(PostgreSQLContentHypergraph::class)
        ]);
        $this->workspaceFinder = $this->getObjectManager()->get(WorkspaceFinder::class);
        $this->nodeTypeConstraintFactory = $this->getObjectManager()->get(NodeTypeConstraintFactory::class);
        $this->eventNormalizer = $this->getObjectManager()->get(EventNormalizer::class);
        $this->runtimeBlocker = $this->getObjectManager()->get(RuntimeBlocker::class);

        $configurationManager = $this->getObjectManager()->get(\Neos\Flow\Configuration\ConfigurationManager::class);
        foreach ($configurationManager->getConfiguration(
            \Neos\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            'Neos.EventSourcedContentRepository.testing.projectorsToBeReset'
        ) ?: [] as $projectorClassName => $toBeReset) {
            if ($toBeReset) {
                $this->projectorsToBeReset[] = $this->getObjectManager()->get($projectorClassName);
            }
        }

        $contentStreamRepository = $this->getObjectManager()->get(ContentStreamRepository::class);
        ObjectAccess::setProperty($contentStreamRepository, 'contentStreams', [], true);
    }

    /**
     * @BeforeScenario
     * @return void
     * @throws \Exception
     */
    public function beforeEventSourcedScenarioDispatcher()
    {
        foreach ($this->getContentGraphs() as $contentGraph) {
            $contentGraph->enableCache();
        }
        $this->visibilityConstraints = VisibilityConstraints::frontend();
        $this->dimensionSpacePoint = null;
        $this->rootNodeAggregateIdentifier = null;
        $this->contentStreamIdentifier = null;
        $this->currentNodeAggregate = null;
        foreach ($this->projectorsToBeReset as $projector) {
            $projector->reset();
        }
    }

    protected function deserializeProperties(array $properties): PropertyValuesToWrite
    {
        foreach ($properties as &$propertyValue) {
            if ($propertyValue === 'PostalAddress:dummy') {
                $propertyValue = PostalAddress::dummy();
            } elseif ($propertyValue === 'PostalAddress:anotherDummy') {
                $propertyValue = PostalAddress::anotherDummy();
            }
            if (is_string($propertyValue)) {
                if (\mb_strpos($propertyValue, 'Date:') === 0) {
                    $propertyValue = \DateTimeImmutable::createFromFormat(\DateTimeInterface::W3C, \mb_substr($propertyValue, 5));
                } elseif (\mb_strpos($propertyValue, 'URI:') === 0) {
                    $propertyValue = new Uri(\mb_substr($propertyValue, 4));
                } elseif ($propertyValue === 'IMG:dummy') {
                    $propertyValue = $this->requireDummyImage();
                } elseif ($propertyValue === '[IMG:dummy]') {
                    $propertyValue = [$this->requireDummyImage()];
                }
            }
        }

        return PropertyValuesToWrite::fromArray($properties);
    }

    /**
     * @Given /^the event RootWorkspaceWasCreated was published with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theEventRootWorkspaceWasCreatedWasPublishedToStreamWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (!isset($eventPayload['initiatingUserIdentifier'])) {
            $eventPayload['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }
        $newContentStreamIdentifier = ContentStreamIdentifier::fromString($eventPayload['newContentStreamIdentifier']);
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($newContentStreamIdentifier);
        $this->publishEvent('Neos.EventSourcedContentRepository:RootWorkspaceWasCreated', $streamName->getEventStreamName(), $eventPayload);
    }

    /**
     * @Given /^the event NodeSpecializationWasCreated was published with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theEventNodeSpecializationWasCreatedWasPublishedToStreamWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (!isset($eventPayload['initiatingUserIdentifier'])) {
            $eventPayload['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }
        $contentStreamIdentifier = ContentStreamIdentifier::fromString($eventPayload['contentStreamIdentifier']);
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $contentStreamIdentifier
        );

        $this->publishEvent('Neos.EventSourcedContentRepository:NodeSpecializationWasCreated', $streamName->getEventStreamName(), $eventPayload);
    }

    /**
     * @Given /^the event NodeGeneralizationVariantWasCreated was published with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theEventNodeGeneralizationVariantWasCreatedWasPublishedToStreamWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (!isset($eventPayload['initiatingUserIdentifier'])) {
            $eventPayload['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }
        $contentStreamIdentifier = ContentStreamIdentifier::fromString($eventPayload['contentStreamIdentifier']);
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $contentStreamIdentifier
        );

        $this->publishEvent('Neos.EventSourcedContentRepository:NodeGeneralizationVariantWasCreated', $streamName->getEventStreamName(), $eventPayload);
    }

    /**
     * @Given /^the event NodeSpecializationVariantWasCreated was published with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theEventNodeSpecializationVariantWasCreatedWasPublishedToStreamWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (!isset($eventPayload['initiatingUserIdentifier'])) {
            $eventPayload['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }
        $contentStreamIdentifier = ContentStreamIdentifier::fromString($eventPayload['contentStreamIdentifier']);
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $contentStreamIdentifier
        );

        $this->publishEvent('Neos.EventSourcedContentRepository:NodeSpecializationVariantWasCreated', $streamName->getEventStreamName(), $eventPayload);
    }

    /**
     * @Given /^the event NodePeerVariantWasCreated was published with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theEventNodePeerVariantWasCreatedWasPublishedToStreamWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (!isset($eventPayload['initiatingUserIdentifier'])) {
            $eventPayload['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }
        $contentStreamIdentifier = ContentStreamIdentifier::fromString($eventPayload['contentStreamIdentifier']);
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $contentStreamIdentifier
        );

        $this->publishEvent('Neos.EventSourcedContentRepository:NodePeerVariantWasCreated', $streamName->getEventStreamName(), $eventPayload);
    }

    /**
     * @Given /^the event NodePropertiesWereSet was published with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theEventNodePropertiesWereSetWasPublishedWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (!isset($eventPayload['initiatingUserIdentifier'])) {
            $eventPayload['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }
        $contentStreamIdentifier = ContentStreamIdentifier::fromString($eventPayload['contentStreamIdentifier']);
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $contentStreamIdentifier
        );

        $this->publishEvent('Neos.EventSourcedContentRepository:NodePropertiesWereSet', $streamName->getEventStreamName(), $eventPayload);
    }

    /**
     * @Given /^the event NodeReferencesWereSet was published with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theEventNodeReferencesWereSetWasPublishedWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (!isset($eventPayload['initiatingUserIdentifier'])) {
            $eventPayload['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }
        $contentStreamIdentifier = ContentStreamIdentifier::fromString($eventPayload['contentStreamIdentifier']);
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $contentStreamIdentifier
        );

        $this->publishEvent('Neos.EventSourcedContentRepository:NodeReferencesWereSet', $streamName->getEventStreamName(), $eventPayload);
    }


    /**
     * @Given /^the event NodeAggregateWasRemoved was published with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theEventNodeAggregateWasRemovedWasPublishedWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (!isset($eventPayload['initiatingUserIdentifier'])) {
            $eventPayload['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }
        $contentStreamIdentifier = ContentStreamIdentifier::fromString($eventPayload['contentStreamIdentifier']);
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier);

        $this->publishEvent('Neos.EventSourcedContentRepository:NodeAggregateWasRemoved', $streamName->getEventStreamName(), $eventPayload);
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
            $eventPayload['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }
        $contentStreamIdentifier = ContentStreamIdentifier::fromString($eventPayload['contentStreamIdentifier']);
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier);

        $this->publishEvent('Neos.EventSourcedContentRepository:NodeAggregateWasMoved', $streamName->getEventStreamName(), $eventPayload);
    }

    /**
     * @Given /^the Event "([^"]*)" was published to stream "([^"]*)" with payload:$/
     * @param $eventType
     * @param $streamName
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theEventWasPublishedToStreamWithPayload(string $eventType, string $streamName, TableNode $payloadTable)
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
        $event = DecoratedEvent::addIdentifier($event, Uuid::uuid4()->toString());
        $events = DomainEvents::withSingleEvent($event);
        $this->getObjectManager()->get(ReadSideMemoryCacheManager::class)->disableCache();
        $this->eventStore->commit($streamName, $events);
        $this->lastCommandOrEventResult = CommandResult::fromPublishedEvents($events, $this->runtimeBlocker);
    }

    /**
     * @param TableNode $payloadTable
     * @return array
     * @throws Exception
     */
    protected function readPayloadTable(TableNode $payloadTable): array
    {
        $eventPayload = [];
        foreach ($payloadTable->getHash() as $line) {
            if (strpos($line['Value'], '$this->') === 0) {
                // Special case: Referencing stuff from the context here
                $propertyOrMethodName = substr($line['Value'], strlen('$this->'));
                if (method_exists($this, $propertyOrMethodName)) {
                    // is method
                    $value = (string) $this->$propertyOrMethodName();
                } else {
                    // is property
                    $value = (string) $this->$propertyOrMethodName;
                }
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
     * @When /^the command CreateRootWorkspace is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theCommandCreateRootWorkspaceIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);

        $userIdentifier = isset($commandArguments['initiatingUserIdentifier'])
            ? UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
            : $this->currentUserIdentifier;

        $command = new CreateRootWorkspace(
            new WorkspaceName($commandArguments['workspaceName']),
            new WorkspaceTitle($commandArguments['workspaceTitle'] ?? ucfirst($commandArguments['workspaceName'])),
            new WorkspaceDescription($commandArguments['workspaceDescription'] ?? 'The workspace "' . $commandArguments['workspaceName'] . '"'),
            $userIdentifier,
            ContentStreamIdentifier::fromString($commandArguments['newContentStreamIdentifier'])
        );

        $this->lastCommandOrEventResult = $this->getWorkspaceCommandHandler()
            ->handleCreateRootWorkspace($command);
    }

    /**
     * @When /^the command CreateWorkspace is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theCommandCreateWorkspaceIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);

        if (!isset($commandArguments['workspaceTitle'])) {
            $commandArguments['workspaceTitle'] = ucfirst($commandArguments['workspaceName']);
        }
        if (!isset($commandArguments['workspaceDescription'])) {
            $commandArguments['workspaceDescription'] = 'The workspace "' . $commandArguments['workspaceName'] . '"';
        }
        if (!isset($commandArguments['initiatingUserIdentifier'])) {
            $commandArguments['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }
        if (!isset($commandArguments['workspaceOwner'])) {
            $commandArguments['workspaceOwner'] = 'owner-identifier';
        }

        $this->theCommandIsExecutedWithPayload('CreateWorkspace', null, $commandArguments);
    }

    /**
     * @Given /^the command CreateNodeVariant is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeAggregateCurrentlyExists
     * @throws DimensionSpacePointNotFound
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws DimensionSpacePointIsNotYetOccupied
     * @throws DimensionSpacePointIsAlreadyOccupied
     * @throws NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint
     * @throws Exception
     */
    public function theCommandCreateNodeVariantIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $contentStreamIdentifier = isset($commandArguments['contentStreamIdentifier'])
            ? ContentStreamIdentifier::fromString($commandArguments['contentStreamIdentifier'])
            : $this->contentStreamIdentifier;
        $initiatingUserIdentifier = isset($commandArguments['initiatingUserIdentifier'])
            ? UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
            : $this->currentUserIdentifier;

        $command = new CreateNodeVariant(
            $contentStreamIdentifier,
            NodeAggregateIdentifier::fromString($commandArguments['nodeAggregateIdentifier']),
            OriginDimensionSpacePoint::fromArray($commandArguments['sourceOrigin']),
            OriginDimensionSpacePoint::fromArray($commandArguments['targetOrigin']),
            $initiatingUserIdentifier
        );
        $this->lastCommandOrEventResult = $this->getNodeAggregateCommandHandler()
            ->handleCreateNodeVariant($command);
    }

    /**
     * @Given /^the command CreateNodeVariant is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theCommandCreateNodeVariantIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable)
    {
        try {
            $this->theCommandCreateNodeVariantIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @Given /^the command SetNodeReferences is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theCommandSetNodeReferencesIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $contentStreamIdentifier = isset($commandArguments['contentStreamIdentifier'])
            ? ContentStreamIdentifier::fromString($commandArguments['contentStreamIdentifier'])
            : $this->contentStreamIdentifier;
        $initiatingUserIdentifier = isset($commandArguments['initiatingUserIdentifier'])
            ? UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
            : $this->currentUserIdentifier;
        $sourceOriginDimensionSpacePoint = isset($commandArguments['sourceOriginDimensionSpacePoint'])
            ? OriginDimensionSpacePoint::fromArray($commandArguments['sourceOriginDimensionSpacePoint'])
            : OriginDimensionSpacePoint::fromDimensionSpacePoint($this->dimensionSpacePoint);

        $command = new SetNodeReferences(
            $contentStreamIdentifier,
            NodeAggregateIdentifier::fromString($commandArguments['sourceNodeAggregateIdentifier']),
            $sourceOriginDimensionSpacePoint,
            NodeAggregateIdentifierCollection::fromArray($commandArguments['destinationNodeAggregateIdentifiers']),
            PropertyName::fromString($commandArguments['referenceName']),
            $initiatingUserIdentifier
        );

        $this->lastCommandOrEventResult = $this->getNodeAggregateCommandHandler()
            ->handleSetNodeReferences($command);
    }

    /**
     * @Given /^the command SetNodeReferences is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     * @throws Exception
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
     * @Given /^the command RemoveNodeAggregate is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theCommandRemoveNodeAggregateIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        if (!isset($commandArguments['initiatingUserIdentifier'])) {
            $commandArguments['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }
        $command = RemoveNodeAggregate::fromArray($commandArguments);

        $this->lastCommandOrEventResult = $this->getNodeAggregateCommandHandler()
            ->handleRemoveNodeAggregate($command);
    }

    /**
     * @Given /^the command RemoveNodeAggregate is executed with payload and exceptions are caught:$/
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
     * @Given /^the command ChangeNodeAggregateType was published with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theCommandChangeNodeAggregateTypeIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        if (!isset($commandArguments['initiatingUserIdentifier'])) {
            $commandArguments['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }

        $command = ChangeNodeAggregateType::fromArray($commandArguments);

        /** @var NodeAggregateCommandHandler $commandHandler */
        $commandHandler = $this->getObjectManager()->get(NodeAggregateCommandHandler::class);

        $this->lastCommandOrEventResult = $commandHandler->handleChangeNodeAggregateType($command);
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
     * @Given /^the command MoveNodeAggregate is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws Exception
     */
    public function theCommandMoveNodeIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        if (!isset($commandArguments['relationDistributionStrategy'])) {
            $commandArguments['relationDistributionStrategy'] = RelationDistributionStrategy::STRATEGY_GATHER_ALL;
        }
        if (!isset($commandArguments['initiatingUserIdentifier'])) {
            $commandArguments['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }
        $command = MoveNodeAggregate::fromArray($commandArguments);

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
     * @Given /^the command DiscardWorkspace is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeException
     * @throws ContentStreamAlreadyExists
     * @throws BaseWorkspaceDoesNotExist
     * @throws BaseWorkspaceHasBeenModifiedInTheMeantime
     * @throws WorkspaceDoesNotExist
     * @throws Exception
     */
    public function theCommandDiscardWorkspaceIsExecuted(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        if (!isset($commandArguments['initiatingUserIdentifier'])) {
            $commandArguments['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }
        $command = DiscardWorkspace::fromArray($commandArguments);

        $this->lastCommandOrEventResult = $this->getWorkspaceCommandHandler()
            ->handleDiscardWorkspace($command);
    }

    /**
     * @Given /^the command PublishIndividualNodesFromWorkspace is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeException
     * @throws ContentStreamAlreadyExists
     * @throws BaseWorkspaceDoesNotExist
     * @throws BaseWorkspaceHasBeenModifiedInTheMeantime
     * @throws WorkspaceDoesNotExist
     * @throws Exception
     */
    public function theCommandPublishIndividualNodesFromWorkspaceIsExecuted(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        if (!isset($commandArguments['initiatingUserIdentifier'])) {
            $commandArguments['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }
        $command = PublishIndividualNodesFromWorkspace::fromArray($commandArguments);

        $this->lastCommandOrEventResult = $this->getWorkspaceCommandHandler()
            ->handlePublishIndividualNodesFromWorkspace($command);
    }

    /**
     * @Given /^the command DiscardIndividualNodesFromWorkspace is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeException
     * @throws ContentStreamAlreadyExists
     * @throws BaseWorkspaceDoesNotExist
     * @throws BaseWorkspaceHasBeenModifiedInTheMeantime
     * @throws WorkspaceDoesNotExist
     * @throws Exception
     */
    public function theCommandDiscardIndividualNodesFromWorkspaceIsExecuted(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        if (!isset($commandArguments['initiatingUserIdentifier'])) {
            $commandArguments['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }
        $command = DiscardIndividualNodesFromWorkspace::fromArray($commandArguments);

        $this->lastCommandOrEventResult = $this->getWorkspaceCommandHandler()
            ->handleDiscardIndividualNodesFromWorkspace($command);
    }

    /**
     * @Given /^the command ForkContentStream is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws ContentStreamAlreadyExists
     * @throws ContentStreamDoesNotExistYet
     * @throws Exception
     */
    public function theCommandForkContentStreamIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        if (!isset($commandArguments['initiatingUserIdentifier'])) {
            $commandArguments['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }
        $command = ForkContentStream::fromArray($commandArguments);

        $this->lastCommandOrEventResult = $this->getContentStreamCommandHandler()
            ->handleForkContentStream($command);
    }

    /**
     * @When /^the command CopyNodesRecursively is executed, copying the current node aggregate with payload:$/
     */
    public function theCommandCopyNodesRecursivelyIsExecutedCopyingTheCurrentNodeAggregateWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $subgraph = $this->contentGraph->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, VisibilityConstraints::withoutRestrictions());
        $commandArguments['nodeToInsert'] = json_decode(json_encode(NodeSubtreeSnapshot::fromSubgraphAndStartNode($subgraph, $this->currentNode)), true);
        $command = CopyNodesRecursively::fromArray($commandArguments);
        $this->lastCommandOrEventResult = $this->getNodeDuplicationCommandHandler()
            ->handleCopyNodesRecursively($command);
    }


    /**
     * @When /^the command "([^"]*)" is executed with payload:$/
     * @Given /^the command "([^"]*)" was executed with payload:$/
     * @param string $shortCommandName
     * @param TableNode|null $payloadTable
     * @param null $commandArguments
     * @throws Exception
     */
    public function theCommandIsExecutedWithPayload(string $shortCommandName, TableNode $payloadTable = null, $commandArguments = null)
    {
        list($commandClassName, $commandHandlerClassName, $commandHandlerMethod) = self::resolveShortCommandName($shortCommandName);
        if ($commandArguments === null && $payloadTable !== null) {
            $commandArguments = $this->readPayloadTable($payloadTable);
        }

        if (isset($commandArguments['propertyValues.dateProperty'])) {
            // special case to test Date type conversion
            $commandArguments['propertyValues']['dateProperty'] = \DateTime::createFromFormat(\DateTime::W3C, $commandArguments['propertyValues.dateProperty']);
        }

        if (!method_exists($commandClassName, 'fromArray')) {
            throw new \InvalidArgumentException(sprintf('Command "%s" does not implement a static "fromArray" constructor', $commandClassName), 1545564621);
        }

        $command = $commandClassName::fromArray($commandArguments);

        $commandHandler = $this->getObjectManager()->get($commandHandlerClassName);

        $this->lastCommandOrEventResult = $commandHandler->$commandHandlerMethod($command);

        // @todo check whether this is necessary at all
        if (isset($commandArguments['rootNodeAggregateIdentifier'])) {
            $this->rootNodeAggregateIdentifier = NodeAggregateIdentifier::fromString($commandArguments['rootNodeAggregateIdentifier']);
        }
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
     * @Then /^the last command should have thrown an exception of type "([^"]*)"(?: with code (\d*))?$/
     * @param string $shortExceptionName
     * @param int|null $expectedCode
     * @throws ReflectionException
     */
    public function theLastCommandShouldHaveThrown(string $shortExceptionName, ?int $expectedCode = null)
    {
        Assert::assertNotNull($this->lastCommandException, 'Command did not throw exception');
        $lastCommandExceptionShortName = (new \ReflectionClass($this->lastCommandException))->getShortName();
        Assert::assertSame($shortExceptionName, $lastCommandExceptionShortName, sprintf('Actual exception: %s (%s): %s', get_class($this->lastCommandException), $this->lastCommandException->getCode(), $this->lastCommandException->getMessage()));
        if (!is_null($expectedCode)) {
            Assert::assertSame($expectedCode, $this->lastCommandException->getCode(), sprintf(
                'Expected exception code %s, got exception code %s instead',
                $expectedCode,
                $this->lastCommandException->getCode()
            ));
        }
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
                    CreateRootWorkspace::class,
                    WorkspaceCommandHandler::class,
                    'handleCreateRootWorkspace'
                ];
            case 'CreateWorkspace':
                return [
                    CreateWorkspace::class,
                    WorkspaceCommandHandler::class,
                    'handleCreateWorkspace'
                ];
            case 'PublishWorkspace':
                return [
                    PublishWorkspace::class,
                    WorkspaceCommandHandler::class,
                    'handlePublishWorkspace'
                ];
            case 'PublishIndividualNodesFromWorkspace':
                return [
                    PublishIndividualNodesFromWorkspace::class,
                    WorkspaceCommandHandler::class,
                    'handlePublishIndividualNodesFromWorkspace'
                ];
            case 'RebaseWorkspace':
                return [
                    RebaseWorkspace::class,
                    WorkspaceCommandHandler::class,
                    'handleRebaseWorkspace'
                ];
            case 'CreateNodeAggregateWithNodeAndSerializedProperties':
                return [
                    CreateNodeAggregateWithNodeAndSerializedProperties::class,
                    NodeAggregateCommandHandler::class,
                    'handleCreateNodeAggregateWithNode'
                ];
            case 'ForkContentStream':
                return [
                    ForkContentStream::class,
                    ContentStreamCommandHandler::class,
                    'handleForkContentStream'
                ];
            case 'ChangeNodeAggregateName':
                return [
                    ChangeNodeAggregateName::class,
                    NodeAggregateCommandHandler::class,
                    'handleChangeNodeAggregateName'
                ];
            case 'SetSerializedNodeProperties':
                return [
                    SetSerializedNodeProperties::class,
                    NodeAggregateCommandHandler::class,
                    'handleSetSerializedNodeProperties'
                ];
            case 'DisableNodeAggregate':
                return [
                    DisableNodeAggregate::class,
                    NodeAggregateCommandHandler::class,
                    'handleDisableNodeAggregate'
                ];
            case 'EnableNodeAggregate':
                return [
                    EnableNodeAggregate::class,
                    NodeAggregateCommandHandler::class,
                    'handleEnableNodeAggregate'
                ];
            case 'MoveNodeAggregate':
                return [
                    MoveNodeAggregate::class,
                    NodeAggregateCommandHandler::class,
                    'handleMoveNodeAggregate'
                ];
            case 'SetNodeReferences':
                return [
                    SetNodeReferences::class,
                    NodeAggregateCommandHandler::class,
                    'handleSetNodeReferences'
                ];

            default:
                throw new \Exception('The short command name "' . $shortCommandName . '" is currently not supported by the tests.');
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
        $stream = $this->eventStore->load($streamName);
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
        $streamName = StreamName::forCategory($streamName);

        $stream = $this->eventStore->load($streamName);
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
     * @Given /^I am user identified by "([^"]*)"$/
     * @param string $userIdentifier
     */
    public function iAmUserIdentifiedBy(string $userIdentifier): void
    {
        $this->currentUserIdentifier = UserIdentifier::fromString($userIdentifier);
    }

    public function getCurrentUserIdentifier(): ?UserIdentifier
    {
        return $this->currentUserIdentifier;
    }

    /**
     * @Then /^workspace "([^"]*)" points to another content stream than workspace "([^"]*)"$/
     * @param string $rawWorkspaceNameA
     * @param string $rawWorkspaceNameB
     */
    public function workspacesPointToDifferentContentStreams(string $rawWorkspaceNameA, string $rawWorkspaceNameB)
    {
        $workspaceA = $this->workspaceFinder->findOneByName(new WorkspaceName($rawWorkspaceNameA));
        Assert::assertInstanceOf(Workspace::class, $workspaceA, 'Workspace "' . $rawWorkspaceNameA . '" does not exist.');
        $workspaceB = $this->workspaceFinder->findOneByName(new WorkspaceName($rawWorkspaceNameB));
        Assert::assertInstanceOf(Workspace::class, $workspaceB, 'Workspace "' . $rawWorkspaceNameB . '" does not exist.');
        if ($workspaceA && $workspaceB) {
            Assert::assertNotEquals(
                $workspaceA->getCurrentContentStreamIdentifier(),
                $workspaceB->getCurrentContentStreamIdentifier(),
                'Workspace "' . $rawWorkspaceNameA . '" points to the same content stream as "' . $rawWorkspaceNameB . '"'
            );
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
     * @Then /^I expect the graph projection to consist of exactly (\d+) node(?:s)?$/
     * @param int $expectedNumberOfNodes
     */
    public function iExpectTheGraphProjectionToConsistOfExactlyNodes(int $expectedNumberOfNodes)
    {
        foreach ($this->getContentGraphs() as $adapterName => $contentGraph) {
            $actualNumberOfNodes = $contentGraph->countNodes();
            Assert::assertSame($expectedNumberOfNodes, $actualNumberOfNodes, 'Content graph in adapter "' . $adapterName . '" consists of ' . $actualNumberOfNodes . ' nodes, expected were ' . $expectedNumberOfNodes . '.');
        }
    }

    /**
     * @Then /^the subtree for node aggregate "([^"]*)" with node types "([^"]*)" and (\d+) levels deep should be:$/
     * @param string $nodeAggregateIdentifier
     * @param string $nodeTypeConstraints
     * @param int $maximumLevels
     * @param TableNode $table
     */
    public function theSubtreeForNodeAggregateWithNodeTypesAndLevelsDeepShouldBe(string $nodeAggregateIdentifier, string $nodeTypeConstraints, int $maximumLevels, TableNode $table)
    {
        $expectedRows = $table->getHash();
        $nodeAggregateIdentifier = NodeAggregateIdentifier::fromString($nodeAggregateIdentifier);
        $nodeTypeConstraints = $this->nodeTypeConstraintFactory->parseFilterString($nodeTypeConstraints);

        $subtree = $this->contentGraph
            ->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints)
            ->findSubtrees([$nodeAggregateIdentifier], (int)$maximumLevels, $nodeTypeConstraints);

        /** @var SubtreeInterface[] $flattenedSubtree */
        $flattenedSubtree = [];
        self::flattenSubtreeForComparison($subtree, $flattenedSubtree);

        Assert::assertEquals(count($expectedRows), count($flattenedSubtree), 'number of expected subtrees do not match');

        foreach ($expectedRows as $i => $expectedRow) {
            $expectedLevel = (int)$expectedRow['Level'];
            $actualLevel = $flattenedSubtree[$i]->getLevel();
            Assert::assertSame($expectedLevel, $actualLevel, 'Level does not match in index ' . $i . ', expected: ' . $expectedLevel . ', actual: ' . $actualLevel);
            $expectedNodeAggregateIdentifier = NodeAggregateIdentifier::fromString($expectedRow['NodeAggregateIdentifier']);
            $actualNodeAggregateIdentifier = $flattenedSubtree[$i]->getNode()->getNodeAggregateIdentifier();
            Assert::assertTrue($expectedNodeAggregateIdentifier->equals($actualNodeAggregateIdentifier), 'NodeAggregateIdentifier does not match in index ' . $i . ', expected: "' . $expectedNodeAggregateIdentifier . '", actual: "' . $actualNodeAggregateIdentifier . '"');
        }
    }

    private static function flattenSubtreeForComparison(SubtreeInterface $subtree, array &$result)
    {
        if ($subtree->getNode()) {
            $result[] = $subtree;
        }
        foreach ($subtree->getChildren() as $childSubtree) {
            self::flattenSubtreeForComparison($childSubtree, $result);
        }
    }

    /**
     * @var \Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress[]
     */
    private $currentNodeAddresses;

    /**
     * @param string|null $alias
     * @return NodeAddress
     */
    protected function getCurrentNodeAddress(string $alias = null): NodeAddress
    {
        if ($alias === null) {
            $alias = 'DEFAULT';
        }
        return $this->currentNodeAddresses[$alias];
    }

    /**
     * @return NodeAddress[]
     */
    public function getCurrentNodeAddresses(): array
    {
        return $this->currentNodeAddresses;
    }

    /**
     * @Given /^I get the node address for node aggregate "([^"]*)"(?:, remembering it as "([^"]*)")?$/
     * @param string $rawNodeAggregateIdentifier
     * @param string $alias
     */
    public function iGetTheNodeAddressForNodeAggregate(string $rawNodeAggregateIdentifier, $alias = 'DEFAULT')
    {
        $subgraph = $this->contentGraph->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints);
        $nodeAggregateIdentifier = NodeAggregateIdentifier::fromString($rawNodeAggregateIdentifier);
        $node = $subgraph->findNodeByNodeAggregateIdentifier($nodeAggregateIdentifier);
        Assert::assertNotNull($node, 'Did not find a node with aggregate identifier "' . $nodeAggregateIdentifier . '"');

        $this->currentNodeAddresses[$alias] = new NodeAddress(
            $this->contentStreamIdentifier,
            $this->dimensionSpacePoint,
            $nodeAggregateIdentifier,
            $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($this->contentStreamIdentifier)->getWorkspaceName()
        );
    }

    /**
     * @Then /^I get the node address for the node at path "([^"]*)"(?:, remembering it as "([^"]*)")?$/
     * @param string $serializedNodePath
     * @param string $alias
     * @throws Exception
     */
    public function iGetTheNodeAddressForTheNodeAtPath(string $serializedNodePath, $alias = 'DEFAULT')
    {
        $subgraph = $this->contentGraph->getSubgraphByIdentifier($this->contentStreamIdentifier, $this->dimensionSpacePoint, $this->visibilityConstraints);
        if (!$this->getRootNodeAggregateIdentifier()) {
            throw new \Exception('ERROR: rootNodeAggregateIdentifier needed for running this step. You need to use "the event RootNodeAggregateWithNodeWasCreated was published with payload" to create a root node..');
        }
        $node = $subgraph->findNodeByPath(NodePath::fromString($serializedNodePath), $this->getRootNodeAggregateIdentifier());
        Assert::assertNotNull($node, 'Did not find a node at path "' . $serializedNodePath . '"');

        $this->currentNodeAddresses[$alias] = new NodeAddress(
            $this->contentStreamIdentifier,
            $this->dimensionSpacePoint,
            $node->getNodeAggregateIdentifier(),
            $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($this->contentStreamIdentifier)->getWorkspaceName()
        );
    }

    protected function getWorkspaceCommandHandler(): WorkspaceCommandHandler
    {
        /** @var WorkspaceCommandHandler $commandHandler */
        $commandHandler = $this->getObjectManager()->get(WorkspaceCommandHandler::class);

        return $commandHandler;
    }

    protected function getContentStreamCommandHandler(): ContentStreamCommandHandler
    {
        /** @var ContentStreamCommandHandler $commandHandler */
        $commandHandler = $this->getObjectManager()->get(ContentStreamCommandHandler::class);

        return $commandHandler;
    }

    protected function getNodeAggregateCommandHandler(): NodeAggregateCommandHandler
    {
        /** @var NodeAggregateCommandHandler $commandHandler */
        $commandHandler = $this->getObjectManager()->get(NodeAggregateCommandHandler::class);

        return $commandHandler;
    }

    protected function getNodeDuplicationCommandHandler(): NodeDuplicationCommandHandler
    {
        /** @var NodeDuplicationCommandHandler $commandHandler */
        $commandHandler = $this->getObjectManager()->get(NodeDuplicationCommandHandler::class);

        return $commandHandler;
    }

    protected function getRootNodeAggregateIdentifier(): ?NodeAggregateIdentifier
    {
        if ($this->rootNodeAggregateIdentifier) {
            return $this->rootNodeAggregateIdentifier;
        }

        $sitesNodeAggregate = $this->contentGraph->findRootNodeAggregateByType($this->contentStreamIdentifier, \Neos\ContentRepository\Domain\NodeType\NodeTypeName::fromString('Neos.Neos:Sites'));
        if ($sitesNodeAggregate) {
            return $sitesNodeAggregate->getIdentifier();
        }

        return null;
    }


    /**
     * @Then the content stream :contentStreamIdentifier has state :expectedState
     */
    public function theContentStreamHasState(string $contentStreamIdentifier, string $expectedState)
    {
        $contentStreamIdentifier = ContentStreamIdentifier::fromString($contentStreamIdentifier);
        /** @var ContentStreamFinder $contentStreamFinder */
        $contentStreamFinder = $this->getObjectManager()->get(ContentStreamFinder::class);

        $actual = $contentStreamFinder->findStateForContentStream($contentStreamIdentifier);
        Assert::assertEquals($expectedState, $actual);
    }

    /**
     * @Then the current content stream has state :expectedState
     */
    public function theCurrentContentStreamHasState(string $expectedState)
    {
        $this->theContentStreamHasState($this->contentStreamIdentifier->jsonSerialize(), $expectedState);
    }

    /**
     * @When I prune unused content streams
     */
    public function iPruneUnusedContentStreams()
    {
        /** @var ContentStreamPruner $contentStreamPruner */
        $contentStreamPruner = $this->getObjectManager()->get(ContentStreamPruner::class);
        $contentStreamPruner->prune();
        $this->lastCommandOrEventResult = $contentStreamPruner->getLastCommandResult();
    }

    /**
     * @When I prune removed content streams from the event stream
     */
    public function iPruneRemovedContentStreamsFromTheEventStream()
    {
        /** @var ContentStreamPruner $contentStreamPruner */
        $contentStreamPruner = $this->getObjectManager()->get(ContentStreamPruner::class);
        $contentStreamPruner->pruneRemovedFromEventStream();
    }
}
