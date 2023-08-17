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

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\Dto\TraceEntryType;
use Neos\ContentRepository\BehavioralTests\ProjectionRaceConditionTester\RedisInterleavingLogger;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
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
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers\ContentRepositoryInternals;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Helper\ContentGraphs;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features\ContentStreamForking;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features\NodeCopying;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features\NodeCreation;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features\NodeDisabling;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features\NodeModification;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features\NodeMove;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features\NodeReferencing;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features\NodeRemoval;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features\NodeRenaming;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features\NodeTypeChange;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features\NodeVariation;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features\WorkspaceCreation;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features\WorkspaceDiscarding;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features\WorkspacePublishing;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Exception\CheckpointException;
use PHPUnit\Framework\Assert;

/**
 * Features context
 */
trait CRTestSuiteTrait
{
    use CRTestSuiteRuntimeVariables;

    use CurrentSubgraphTrait;
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

    private ?ContentRepositoryId $contentRepositoryId = null;

    private ContentRepositoryInternals $contentRepositoryInternals;

    /**
     * @deprecated
     */
    protected function getContentRepositoryId(): ContentRepositoryId
    {
        return $this->currentContentRepository->id;
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
        return $this->currentContentRepository->getWorkspaceFinder();
    }

    protected function getAvailableContentGraphs(): ContentGraphs
    {
        return new ContentGraphs([
            'DoctrineDBAL' => $this->currentContentRepository->getContentGraph()
        ]);
    }

    protected function getActiveContentGraphs(): ContentGraphs
    {
        return new ContentGraphs([
            'DoctrineDBAL' => $this->currentContentRepository->getContentGraph()
        ]);
    }

    private bool $alwaysRunContentRepositorySetup = false;
    private bool $raceConditionTrackerEnabled = false;

    protected function setupEventSourcedTrait(bool $alwaysRunCrSetup = false)
    {
        $this->alwaysRunContentRepositorySetup = $alwaysRunCrSetup;
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
    abstract protected function initCleanContentRepository(array $adapterKeys): void;

    /**
     * @BeforeScenario @contentrepository
     * @return void
     * @throws \Exception
     */
    public function beforeEventSourcedScenarioDispatcher(BeforeScenarioScope $scope)
    {
        $this->contentDimensionsToUse = null;
        $this->contentRepositories = [];
        $this->currentContentRepository = null;
        $this->currentVisibilityConstraints = VisibilityConstraints::frontend();
        $this->currentDimensionSpacePoint = null;
        $this->currentRootNodeAggregateId = null;
        $this->currentContentStreamId = null;
        $this->currentNodeAggregates = null;
        $this->currentNodes = null;
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
                $value = match ($propertyOrMethodName) {
                    'currentNodeAggregateId' => $this->currentNodeAggregateId()->value,
                    'contentStreamId' => $this->currentContentStreamId->value,
                    default => method_exists($this, $propertyOrMethodName) ? (string)$this->$propertyOrMethodName() : (string)$this->$propertyOrMethodName,
                };
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
        $workspaceA = $this->currentContentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($rawWorkspaceNameA));
        Assert::assertInstanceOf(Workspace::class, $workspaceA, 'Workspace "' . $rawWorkspaceNameA . '" does not exist.');
        $workspaceB = $this->currentContentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($rawWorkspaceNameB));
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
        $workspace = $this->currentContentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($rawWorkspaceName));

        Assert::assertNotEquals($rawContentStreamId, $workspace->currentContentStreamId->value);
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
                ->getSubgraph($this->currentContentStreamId, $this->currentDimensionSpacePoint, $this->currentVisibilityConstraints)
                ->findSubtree($nodeAggregateId, FindSubtreeFilter::create(nodeTypeConstraints: $nodeTypeConstraints, maximumLevels: $maximumLevels));

            /** @var Subtree[] $flattenedSubtree */
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
                    'NodeAggregateId does not match in index ' . $i . ', expected: "' . $expectedNodeAggregateId->value . '", actual: "' . $actualNodeAggregateId->value . '" (adapter: ' . $adapterName . ')');
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
     * @return EventStoreInterface
     * @deprecated
     */
    protected function getEventStore(): EventStoreInterface
    {
        $reflectedContentRepository = new \ReflectionClass($this->currentContentRepository);

        return $reflectedContentRepository->getProperty('eventStore')
            ->getValue($this->currentContentRepository);
    }

    protected function getRootNodeAggregateId(): ?NodeAggregateId
    {
        if ($this->currentRootNodeAggregateId) {
            return $this->currentRootNodeAggregateId;
        }

        $contentGraphs = $this->getActiveContentGraphs()->getIterator()->getArrayCopy();
        $contentGraph = reset($contentGraphs);
        $sitesNodeAggregate = $contentGraph->findRootNodeAggregateByType($this->currentContentStreamId, NodeTypeName::fromString('Neos.Neos:Sites'));
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
        $contentStreamFinder = $this->currentContentRepository->getContentStreamFinder();

        $actual = $contentStreamFinder->findStateForContentStream($contentStreamId);
        Assert::assertEquals($expectedState, $actual);
    }

    /**
     * @Then the current content stream has state :expectedState
     */
    public function theCurrentContentStreamHasState(string $expectedState)
    {
        $this->theContentStreamHasState($this->currentContentStreamId->value, $expectedState);
    }

    /**
     * @When I prune unused content streams
     */
    public function iPruneUnusedContentStreams()
    {
        /** @var ContentStreamPruner $contentStreamPruner */
        $contentStreamPruner = $this->getContentRepositoryService($this->contentRepositoryId, new ContentStreamPrunerFactory());
        $contentStreamPruner->prune();
        $this->lastCommandOrEventResult = $contentStreamPruner->getLastCommandResult();
    }

    /**
     * @When I prune removed content streams from the event stream
     */
    public function iPruneRemovedContentStreamsFromTheEventStream()
    {
        /** @var ContentStreamPruner $contentStreamPruner */
        $contentStreamPruner = $this->getContentRepositoryService($this->currentContentRepository->id, new ContentStreamPrunerFactory());
        $contentStreamPruner->pruneRemovedFromEventStream();
    }

    /**
     * @When I replay the :projectionName projection
     */
    public function iReplayTheProjection(string $projectionName)
    {
        $this->currentContentRepository->resetProjectionState($projectionName);
        $this->currentContentRepository->catchUpProjection($projectionName);
    }

    abstract protected function getContentRepositoryService(
        ContentRepositoryId $contentRepositoryId,
        ContentRepositoryServiceFactoryInterface $factory
    ): ContentRepositoryServiceInterface;

    protected function deserializeProperties(array $properties): PropertyValuesToWrite
    {
        return PropertyValuesToWrite::fromArray($properties);
    }
}
