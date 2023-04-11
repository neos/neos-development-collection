<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/Features/ContentStreamForking.php');
require_once(__DIR__ . '/Features/NodeCopying.php');
require_once(__DIR__ . '/Features/NodeCreation.php');
require_once(__DIR__ . '/Features/NodeDisabling.php');
require_once(__DIR__ . '/Features/NodeModification.php');
require_once(__DIR__ . '/Features/NodeMove.php');
require_once(__DIR__ . '/Features/NodeReferencing.php');
require_once(__DIR__ . '/Features/NodeRemoval.php');
require_once(__DIR__ . '/Features/NodeRenaming.php');
require_once(__DIR__ . '/Features/NodeTypeChange.php');
require_once(__DIR__ . '/Features/NodeVariation.php');
require_once(__DIR__ . '/Features/WorkspaceCreation.php');
require_once(__DIR__ . '/Features/WorkspaceDiscarding.php');
require_once(__DIR__ . '/Features/WorkspacePublishing.php');

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Psr7\Uri;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\ContentHypergraph;
use Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\Dto\TraceEntryType;
use Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\RedisInterleavingLogger;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Core\Service\ContentStreamPruner;
use Neos\ContentRepository\Core\Service\ContentStreamPrunerFactory;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Features\ContentStreamForking;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Features\NodeCopying;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Features\NodeCreation;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Features\NodeDisabling;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Features\NodeModification;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Features\NodeMove;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Features\NodeReferencing;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Features\NodeRemoval;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Features\NodeRenaming;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Features\NodeTypeChange;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Features\NodeVariation;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Features\WorkspaceCreation;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Features\WorkspaceDiscarding;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Features\WorkspacePublishing;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers\ContentRepositoryInternals;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers\ContentRepositoryInternalsFactory;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers\FakeClockFactory;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers\FakeUserIdProviderFactory;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers\MutableClockFactory;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Helper\ContentGraphs;
use Neos\ContentRepository\Core\Tests\Behavior\Fixtures\DayOfWeek;
use Neos\ContentRepository\Core\Tests\Behavior\Fixtures\PostalAddress;
use Neos\ContentRepository\Security\Service\AuthorizationService;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger\CatchUpTriggerWithSynchronousOption;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Exception\CheckpointException;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Neos\FrontendRouting\NodeAddress;
use PHPUnit\Framework\Assert;

/**
 * Features context
 */
trait EventSourcedTrait
{
    use CurrentSubgraphTrait;
    use CurrentUserTrait;
    use CurrentDateTimeTrait;
    use NodeTraversalTrait;
    use ProjectedNodeAggregateTrait;
    use ProjectedNodeTrait;
    use GenericCommandExecutionAndEventPublication;

    use ContentStreamForking;

    use NodeCreation;
    use NodeCopying;
    use NodeDisabling;
    use NodeModification;
    use NodeMove;
    use NodeReferencing;
    use NodeRemoval;
    use NodeRenaming;
    use NodeTypeChange;
    use NodeVariation;

    use WorkspaceCreation;
    use WorkspaceDiscarding;
    use WorkspacePublishing;

    protected ContentGraphs $availableContentGraphs;

    protected ContentGraphs $activeContentGraphs;

    protected ?NodeAggregateId $rootNodeAggregateId;

    private ContentRepositoryId $contentRepositoryId;
    private ContentRepositoryRegistry $contentRepositoryRegistry;
    private ContentRepository $contentRepository;
    private ContentRepositoryInternals $contentRepositoryInternals;

    abstract protected function getObjectManager(): ObjectManagerInterface;


    protected function getContentRepositoryId(): ContentRepositoryId
    {
        return $this->contentRepositoryId;
    }

    protected function getContentRepositoryRegistry(): ContentRepositoryRegistry
    {
        return $this->contentRepositoryRegistry;
    }

    protected function getContentRepository(): ContentRepository
    {
        return $this->contentRepository;
    }

    /**
     * @return ContentRepositoryInternals
     * @deprecated ideally we would not need this in tests
     */
    protected function getContentRepositoryInternals(): ContentRepositoryInternals
    {
        return $this->contentRepositoryInternals;
    }

    /**
     * @return WorkspaceFinder
     * @deprecated
     */
    protected function getWorkspaceFinder(): WorkspaceFinder
    {
        return $this->getContentRepository()->getWorkspaceFinder();
    }

    protected function getAvailableContentGraphs(): ContentGraphs
    {
        return $this->availableContentGraphs;
    }

    protected function getActiveContentGraphs(): ContentGraphs
    {
        return $this->activeContentGraphs;
    }

    private bool $alwaysRunContentRepositorySetup = false;
    private bool $raceConditionTrackerEnabled = false;

    protected function setupEventSourcedTrait(bool $alwaysRunCrSetup = false)
    {
        $this->alwaysRunContentRepositorySetup = $alwaysRunCrSetup;
        $this->nodeAuthorizationService = $this->getObjectManager()->get(AuthorizationService::class);
        $this->contentRepositoryId = ContentRepositoryId::fromString('default');

        if (getenv('CATCHUPTRIGGER_ENABLE_SYNCHRONOUS_OPTION')) {
            CatchUpTriggerWithSynchronousOption::enableSynchonityForSpeedingUpTesting();
        }

        // prepare race tracking for debugging into the race log
        if (class_exists(RedisInterleavingLogger::class)) { // the class must exist (the package loaded)
            $raceConditionTrackerConfig = $this->getObjectManager()->get(ConfigurationManager::class)
                ->getConfiguration(
                    ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
                    'Neos.ContentRepository.BehavioralTests.raceConditionTracker');

            // if it's enabled, correctly configure the Redis connection.
            // Then, people can use {@see logToRaceConditionTracker()} for debugging.
            $this->raceConditionTrackerEnabled = boolval($raceConditionTrackerConfig['enabled']);
            if ($this->raceConditionTrackerEnabled) {
                RedisInterleavingLogger::connect(
                    $raceConditionTrackerConfig['redis']['host'],
                    $raceConditionTrackerConfig['redis']['port']
                );
            }
        }
    }

    /**
     * This function logs a message into the race condition tracker's event log,
     * which can be inspected by calling ./flow raceConditionTracker:analyzeTrace.
     *
     * It is helpful to do this during debugging; in order to figure out whether an issue is an actual bug
     * or a situation which can only happen during test runs.
     */
    public function logToRaceConditionTracker(array $payload)
    {
        if ($this->raceConditionTrackerEnabled) {
            RedisInterleavingLogger::trace(TraceEntryType::DebugLog, $payload);
        }
    }


    private static bool $wasContentRepositorySetupCalled = false;

    /**
     * @param array<string> $adapterKeys "DoctrineDBAL" if
     * @return void
     */
    private function initCleanContentRepository(array $adapterKeys): void
    {
        $this->logToRaceConditionTracker(['msg' => 'initCleanContentRepository']);

        $configurationManager = $this->getObjectManager()->get(ConfigurationManager::class);
        $registrySettings = $configurationManager->getConfiguration(
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            'Neos.ContentRepositoryRegistry'
        );

        if (!in_array('Postgres', $adapterKeys)) {
            // in case we do not have tests annotated with @adapters=Postgres, we
            // REMOVE the Postgres projection from the Registry settings. This way, we won't trigger
            // Postgres projection catchup for tests which are not yet postgres-aware.
            //
            // This is to make the testcases more stable and deterministic. We can remove this workaround
            // once the Postgres adapter is fully ready.
            unset($registrySettings['presets'][$this->contentRepositoryId->value]['projections']['Neos.ContentGraph.PostgreSQLAdapter:Hypergraph']);
        }
        $registrySettings['presets'][$this->contentRepositoryId->value]['userIdProvider']['factoryObjectName'] = FakeUserIdProviderFactory::class;
        $registrySettings['presets'][$this->contentRepositoryId->value]['clock']['factoryObjectName'] = FakeClockFactory::class;

        $this->contentRepositoryRegistry = new ContentRepositoryRegistry(
            $registrySettings,
            $this->getObjectManager()
        );


        $this->contentRepository = $this->contentRepositoryRegistry->get($this->contentRepositoryId);
        // Big performance optimization: only run the setup once - DRAMATICALLY reduces test time
        if ($this->alwaysRunContentRepositorySetup || !self::$wasContentRepositorySetupCalled) {
            $this->contentRepository->setUp();
            self::$wasContentRepositorySetupCalled = true;
        }
        $this->contentRepositoryInternals = $this->contentRepositoryRegistry->getService($this->contentRepositoryId, new ContentRepositoryInternalsFactory());

        $availableContentGraphs = [];
        $availableContentGraphs['DoctrineDBAL'] = $this->contentRepository->getContentGraph();
        // NOTE: to disable a content graph (do not run the tests for it), you can use "null" as value.
        if (in_array('Postgres', $adapterKeys)) {
            $availableContentGraphs['Postgres'] = $this->contentRepository->projectionState(ContentHypergraph::class);
        }

        if (count($availableContentGraphs) === 0) {
            throw new \RuntimeException('No content graph active during testing. Please set one in settings in activeContentGraphs');
        }
        $this->availableContentGraphs = new ContentGraphs($availableContentGraphs);

    }

    /**
     * @BeforeScenario @contentrepository
     * @return void
     * @throws \Exception
     */
    public function beforeEventSourcedScenarioDispatcher(BeforeScenarioScope $scope)
    {
        $adapterTagPrefix = 'adapters=';
        $adapterTagPrefixLength = \mb_strlen($adapterTagPrefix);
        /** @var array<int,string> $adapterKeys */
        $adapterKeys = [];
        foreach ($scope->getFeature()->getTags() as $tagName) {
            if (\str_starts_with($tagName, $adapterTagPrefix)) {
                $adapterKeys = explode(',', \mb_substr($tagName, $adapterTagPrefixLength));
                break;
            }
        }

        $this->initCleanContentRepository($adapterKeys);

        $this->activeContentGraphs = count($adapterKeys) === 0
            ? $this->availableContentGraphs
            : $this->availableContentGraphs->reduceTo($adapterKeys);


        $this->visibilityConstraints = VisibilityConstraints::frontend();
        $this->dimensionSpacePoint = null;
        $this->rootNodeAggregateId = null;
        $this->contentStreamId = null;
        $this->currentNodeAggregates = null;
        $this->currentUserId = null;
        $this->currentNodes = null;

        $connection = $this->objectManager->get(DbalClientInterface::class)->getConnection();
        // copied from DoctrineEventStoreFactory

        /**
         * Reset projections and events
         * ============================
         *
         * PITFALL: for a long time, the code below was a two-liner (it is not anymore, for reasons explained here):
         * - reset projections (truncate table contents)
         * - truncate events table.
         *
         * This code has SERIOUS Race Condition and Bug Potential.
         * tl;dr: It is CRUCIAL that *FIRST* the event store is emptied, and *then* the projection state is reset;
         * so the OPPOSITE order as described above.
         *
         * If doing it in the way described initially, the following can happen (time flows from top to bottom):
         *
         * ```
         * Main Behat Process                        Dangling Projection catch up worker
         * ==================                        ===================================
         *
         *                                           (hasn't started working yet, simply sleeping)
         *
         * 1) Projection State reset
         *                                           "oh, I have some work to do to catch up EVERYTHING"
         *                                           "query the events table"
         *
         * 2) Event Table Reset
         *                                           (events table is already loaded into memory) -> replay WIP
         *
         * (new commands/events start happening,
         * in the new testcase)
         *                                           ==> ERRORS because the projection now contains the result of both
         *                                               old AND new events (of the two different testcases) <==
         * ```
         *
         * This was an actual bug which bit us and made our tests unstable :D :D
         *
         * How did we find this? By the virtue of our Race Tracker (Docs: see {@see RaceTrackerCatchUpHook}), which
         * checks for events being applied multiple times to a projection.
         * ... and additionally by using {@see logToRaceConditionTracker()} to find the interleavings between the
         * Catch Up process and the testcase reset.
         */

        $eventTableName = sprintf('cr_%s_events', $this->contentRepositoryId);
        $connection->executeStatement('TRUNCATE ' . $eventTableName);

        // TODO: WORKAROUND: UGLY AS HELL CODE: Projection Reset may fail because the lock cannot be acquired, so we
        //       try again.
        //
        // TODO: inside the projections, the code to reset looks like this:
        //        $this->truncateDatabaseTables();
        //        $this->checkpointStorage->acquireLock();
        //        $this->checkpointStorage->updateAndReleaseLock(SequenceNumber::none());
        //  -> it'd say we need to change this; to:
        //        $this->checkpointStorage->acquireLock();
        //        $this->truncateDatabaseTables();
        //        $this->checkpointStorage->updateAndReleaseLock(SequenceNumber::none());
        // TODO: Rename to "tryToAcquireLock"?
        $projectionsWereReset = false;
        $retryCount = 0;
        do {
            try {
                $retryCount++;
                $this->contentRepository->resetProjectionStates();

                // if we end up here without exception, we know the projection states were properly reset.
                $projectionsWereReset = true;
            } catch (CheckpointException $checkpointException) {
                // TODO: HACK: UGLY CODE!!!
                if ($checkpointException->getCode() === 1652279016 && $retryCount < 20) { // we wait for 10 seconds max.
                    // another process is in the critical section; a.k.a.
                    // the lock is acquired already by another process.
                    //
                    // -> we sleep for 0.5s and retry
                    usleep(500000);
                } else {
                    // some error error - we re-throw
                    throw $checkpointException;
                }
            }
        } while ($projectionsWereReset !== true);
    }

    /**
     * @param TableNode $payloadTable
     * @return array
     * @throws \Exception
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
                    $value = (string)$this->$propertyOrMethodName();
                } else {
                    // is property
                    $value = (string)$this->$propertyOrMethodName;
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
     * called by {@see readPayloadTable()} above, from RebasingAutoCreatedChildNodesWorks.feature
     * @return NodeAggregateId
     */
    protected function currentNodeAggregateId(): NodeAggregateId
    {
        $currentNodes = $this->currentNodes->getIterator()->getArrayCopy();
        $firstNode = reset($currentNodes);
        assert($firstNode instanceof Node);
        return $firstNode->nodeAggregateId;
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
                if (\str_starts_with($propertyValue, 'DayOfWeek:')) {
                    $propertyValue = DayOfWeek::from(\mb_substr($propertyValue, 10));
                } elseif (\str_starts_with($propertyValue, 'Date:')) {
                    $propertyValue = \DateTimeImmutable::createFromFormat(\DateTimeInterface::W3C, \mb_substr($propertyValue, 5));
                } elseif (\str_starts_with($propertyValue, 'URI:')) {
                    $propertyValue = new Uri(\mb_substr($propertyValue, 4));
                } else {
                    try {
                        $propertyValue = \json_decode($propertyValue, true, 512, JSON_THROW_ON_ERROR);
                    } catch (\JsonException) {
                        // then don't, just keep the value
                    }
                }
            }
        }

        return PropertyValuesToWrite::fromArray($properties);
    }

    /**
     * @When /^the graph projection is fully up to date$/
     */
    public function theGraphProjectionIsFullyUpToDate()
    {
        if ($this->lastCommandOrEventResult === null) {
            throw new \RuntimeException('lastCommandOrEventResult not filled; so I cannot block!');
        }
        $this->lastCommandOrEventResult->block();
        $this->lastCommandOrEventResult = null;
    }

    /**
     * @Then /^workspace "([^"]*)" points to another content stream than workspace "([^"]*)"$/
     */
    public function workspacesPointToDifferentContentStreams(string $rawWorkspaceNameA, string $rawWorkspaceNameB): void
    {
        $workspaceA = $this->contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($rawWorkspaceNameA));
        Assert::assertInstanceOf(Workspace::class, $workspaceA, 'Workspace "' . $rawWorkspaceNameA . '" does not exist.');
        $workspaceB = $this->contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($rawWorkspaceNameB));
        Assert::assertInstanceOf(Workspace::class, $workspaceB, 'Workspace "' . $rawWorkspaceNameB . '" does not exist.');
        if ($workspaceA && $workspaceB) {
            Assert::assertNotEquals(
                $workspaceA->currentContentStreamId->value,
                $workspaceB->currentContentStreamId->value,
                'Workspace "' . $rawWorkspaceNameA . '" points to the same content stream as "' . $rawWorkspaceNameB . '"'
            );
        }
    }

    /**
     * @Then /^workspace "([^"]*)" does not point to content stream "([^"]*)"$/
     */
    public function workspaceDoesNotPointToContentStream(string $rawWorkspaceName, string $rawContentStreamId)
    {
        $workspace = $this->contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($rawWorkspaceName));

        Assert::assertNotEquals($rawContentStreamId, (string)$workspace->currentContentStreamId);
    }

    /**
     * @Then /^I expect the graph projection to consist of exactly (\d+) node(?:s)?$/
     * @param int $expectedNumberOfNodes
     */
    public function iExpectTheGraphProjectionToConsistOfExactlyNodes(int $expectedNumberOfNodes)
    {
        foreach ($this->getActiveContentGraphs() as $adapterName => $contentGraph) {
            $actualNumberOfNodes = $contentGraph->countNodes();
            Assert::assertSame($expectedNumberOfNodes, $actualNumberOfNodes, 'Content graph in adapter "' . $adapterName . '" consists of ' . $actualNumberOfNodes . ' nodes, expected were ' . $expectedNumberOfNodes . '.');
        }
    }

    /**
     * @Then /^the subtree for node aggregate "([^"]*)" with node types "([^"]*)" and (\d+) levels deep should be:$/
     */
    public function theSubtreeForNodeAggregateWithNodeTypesAndLevelsDeepShouldBe(
        string $serializedNodeAggregateId,
        string $serializedNodeTypeConstraints,
        int $maximumLevels,
        TableNode $table
    ): void {
        $nodeAggregateId = NodeAggregateId::fromString($serializedNodeAggregateId);
        $nodeTypeConstraints = NodeTypeConstraints::fromFilterString($serializedNodeTypeConstraints);
        foreach ($this->getActiveContentGraphs() as $adapterName => $contentGraph) {
            assert($contentGraph instanceof ContentGraphInterface);
            $expectedRows = $table->getHash();

            $subtree = $contentGraph
                ->getSubgraph($this->contentStreamId, $this->dimensionSpacePoint, $this->visibilityConstraints)
                ->findSubtree($nodeAggregateId,
                    FindSubtreeFilter::nodeTypeConstraints($nodeTypeConstraints)->withMaximumLevels($maximumLevels));

            /** @var \Neos\ContentRepository\Core\Projection\ContentGraph\Subtree[] $flattenedSubtree */
            $flattenedSubtree = [];
            if ($subtree !== null) {
                self::flattenSubtreeForComparison($subtree, $flattenedSubtree);
            }

            Assert::assertEquals(count($expectedRows), count($flattenedSubtree), 'number of expected subtrees do not match (adapter: ' . $adapterName . ')');

            foreach ($expectedRows as $i => $expectedRow) {
                $expectedLevel = (int)$expectedRow['Level'];
                $actualLevel = $flattenedSubtree[$i]->level;
                Assert::assertSame($expectedLevel, $actualLevel, 'Level does not match in index ' . $i . ', expected: ' . $expectedLevel . ', actual: ' . $actualLevel . ' (adapter: ' . $adapterName . ')');
                $expectedNodeAggregateId = NodeAggregateId::fromString($expectedRow['nodeAggregateId']);
                $actualNodeAggregateId = $flattenedSubtree[$i]->node->nodeAggregateId;
                Assert::assertTrue($expectedNodeAggregateId->equals($actualNodeAggregateId),
                    'NodeAggregateId does not match in index ' . $i . ', expected: "' . $expectedNodeAggregateId . '", actual: "' . $actualNodeAggregateId . '" (adapter: ' . $adapterName . ')');
            }
        }
    }

    private static function flattenSubtreeForComparison(Subtree $subtree, array &$result): void
    {
        $result[] = $subtree;
        foreach ($subtree->children as $childSubtree) {
            self::flattenSubtreeForComparison($childSubtree, $result);
        }
    }

    /**
     * @var NodeAddress[]
     */
    private $currentNodeAddresses;

    /**
     * @param string|null $alias
     * @return \Neos\Neos\FrontendRouting\NodeAddress
     */
    protected function getCurrentNodeAddress(string $alias = null): NodeAddress
    {
        if ($alias === null) {
            $alias = 'DEFAULT';
        }
        return $this->currentNodeAddresses[$alias];
    }

    /**
     * @return \Neos\Neos\FrontendRouting\NodeAddress[]
     */
    public function getCurrentNodeAddresses(): array
    {
        return $this->currentNodeAddresses;
    }

    /**
     * @Given /^I get the node address for node aggregate "([^"]*)"(?:, remembering it as "([^"]*)")?$/
     * @param string $rawNodeAggregateId
     * @param string $alias
     */
    public function iGetTheNodeAddressForNodeAggregate(string $rawNodeAggregateId, $alias = 'DEFAULT')
    {
        $subgraph = $this->contentGraph->getSubgraph($this->contentStreamId, $this->dimensionSpacePoint, $this->visibilityConstraints);
        $nodeAggregateId = NodeAggregateId::fromString($rawNodeAggregateId);
        $node = $subgraph->findNodeById($nodeAggregateId);
        Assert::assertNotNull($node, 'Did not find a node with aggregate id "' . $nodeAggregateId . '"');

        $this->currentNodeAddresses[$alias] = new NodeAddress(
            $this->contentStreamId,
            $this->dimensionSpacePoint,
            $nodeAggregateId,
            $this->contentRepository->getWorkspaceFinder()
                ->findOneByCurrentContentStreamId($this->contentStreamId)
                ->workspaceName
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
        $subgraph = $this->contentGraph->getSubgraph($this->contentStreamId, $this->dimensionSpacePoint, $this->visibilityConstraints);
        if (!$this->getRootNodeAggregateId()) {
            throw new \Exception('ERROR: rootNodeAggregateId needed for running this step. You need to use "the event RootNodeAggregateWithNodeWasCreated was published with payload" to create a root node..');
        }
        $node = $subgraph->findNodeByPath(NodePath::fromString($serializedNodePath), $this->getRootNodeAggregateId());
        Assert::assertNotNull($node, 'Did not find a node at path "' . $serializedNodePath . '"');

        $this->currentNodeAddresses[$alias] = new NodeAddress(
            $this->contentStreamId,
            $this->dimensionSpacePoint,
            $node->nodeAggregateId,
            $this->contentRepository->getWorkspaceFinder()
                ->findOneByCurrentContentStreamId($this->contentStreamId)
                ->workspaceName
        );
    }

    /**
     * @return EventStoreInterface
     * @deprecated
     */
    protected function getEventStore(): EventStoreInterface
    {
        return $this->getContentRepositoryInternals()->eventStore;
    }

    protected function getRootNodeAggregateId(): ?NodeAggregateId
    {
        if ($this->rootNodeAggregateId) {
            return $this->rootNodeAggregateId;
        }

        $contentGraphs = $this->getActiveContentGraphs()->getIterator()->getArrayCopy();
        $contentGraph = reset($contentGraphs);
        $sitesNodeAggregate = $contentGraph->findRootNodeAggregateByType($this->contentStreamId, \Neos\ContentRepository\Core\NodeType\NodeTypeName::fromString('Neos.Neos:Sites'));
        if ($sitesNodeAggregate) {
            assert($sitesNodeAggregate instanceof NodeAggregate);
            return $sitesNodeAggregate->nodeAggregateId;
        }

        return null;
    }

    /**
     * @Then the content stream :contentStreamId has state :expectedState
     */
    public function theContentStreamHasState(string $contentStreamId, string $expectedState)
    {
        $contentStreamId = ContentStreamId::fromString($contentStreamId);
        $contentStreamFinder = $this->getContentRepository()->getContentStreamFinder();

        $actual = $contentStreamFinder->findStateForContentStream($contentStreamId);
        Assert::assertEquals($expectedState, $actual);
    }

    /**
     * @Then the current content stream has state :expectedState
     */
    public function theCurrentContentStreamHasState(string $expectedState)
    {
        $this->theContentStreamHasState($this->contentStreamId->jsonSerialize(), $expectedState);
    }

    /**
     * @When I prune unused content streams
     */
    public function iPruneUnusedContentStreams()
    {
        /** @var ContentStreamPruner $contentStreamPruner */
        $contentStreamPruner = $this->getContentRepositoryRegistry()->getService($this->getContentRepositoryId(), new ContentStreamPrunerFactory());
        $contentStreamPruner->prune();
        $this->lastCommandOrEventResult = $contentStreamPruner->getLastCommandResult();
    }

    /**
     * @When I prune removed content streams from the event stream
     */
    public function iPruneRemovedContentStreamsFromTheEventStream()
    {
        /** @var ContentStreamPruner $contentStreamPruner */
        $contentStreamPruner = $this->getContentRepositoryRegistry()->getService($this->getContentRepositoryId(), new ContentStreamPrunerFactory());
        $contentStreamPruner->pruneRemovedFromEventStream();
    }
}
