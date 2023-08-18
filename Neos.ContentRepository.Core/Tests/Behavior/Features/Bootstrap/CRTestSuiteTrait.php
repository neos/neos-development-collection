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
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\Service\ContentStreamPruner;
use Neos\ContentRepository\Core\SharedModel\Exception\RootNodeAggregateDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features\ContentStreamForking;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CurrentSubgraphTrait;
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

    private bool $alwaysRunContentRepositorySetup = false;
    private bool $raceConditionTrackerEnabled = false;

    protected function setupEventSourcedTrait(bool $alwaysRunCrSetup = false): void
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
    public function logToRaceConditionTracker(array $payload): void
    {
        if ($this->raceConditionTrackerEnabled) {
            RedisInterleavingLogger::trace(TraceEntryType::DebugLog, $payload);
        }
    }


    private static bool $wasContentRepositorySetupCalled = false;

    /**
     * @BeforeScenario @contentrepository
     * @return void
     * @throws \Exception
     */
    public function beforeEventSourcedScenarioDispatcher(BeforeScenarioScope $scope): void
    {
        $this->contentDimensionsToUse = null;
        $this->contentRepositories = [];
        $this->currentContentRepository = null;
        $this->currentVisibilityConstraints = VisibilityConstraints::frontend();
        $this->currentDimensionSpacePoint = null;
        $this->currentRootNodeAggregateId = null;
        $this->currentContentStreamId = null;
        $this->currentNodeAggregate = null;
        $this->currentNode = null;
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
                    'currentNodeAggregateId' => $this->getCurrentNodeAggregateId()->value,
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
     * @When /^the graph projection is fully up to date$/
     */
    public function theGraphProjectionIsFullyUpToDate(): void
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
    public function iExpectTheGraphProjectionToConsistOfExactlyNodes(int $expectedNumberOfNodes): void
    {
        $actualNumberOfNodes = $this->currentContentRepository->getContentGraph()->countNodes();
        Assert::assertSame($expectedNumberOfNodes, $actualNumberOfNodes, 'Content graph consists of ' . $actualNumberOfNodes . ' nodes, expected were ' . $expectedNumberOfNodes . '.');
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
        $expectedRows = $table->getHash();

        $subtree = $this->getCurrentSubgraph()
            ->findSubtree($nodeAggregateId, FindSubtreeFilter::create(nodeTypeConstraints: $nodeTypeConstraints, maximumLevels: $maximumLevels));

        /** @var Subtree[] $flattenedSubtree */
        $flattenedSubtree = [];
        if ($subtree !== null) {
            self::flattenSubtreeForComparison($subtree, $flattenedSubtree);
        }

        Assert::assertEquals(count($expectedRows), count($flattenedSubtree), 'number of expected subtrees do not match');

        foreach ($expectedRows as $i => $expectedRow) {
            $expectedLevel = (int)$expectedRow['Level'];
            $actualLevel = $flattenedSubtree[$i]->level;
            Assert::assertSame($expectedLevel, $actualLevel, 'Level does not match in index ' . $i . ', expected: ' . $expectedLevel . ', actual: ' . $actualLevel);
            $expectedNodeAggregateId = NodeAggregateId::fromString($expectedRow['nodeAggregateId']);
            $actualNodeAggregateId = $flattenedSubtree[$i]->node->nodeAggregateId;
            Assert::assertTrue($expectedNodeAggregateId->equals($actualNodeAggregateId),
                'NodeAggregateId does not match in index ' . $i . ', expected: "' . $expectedNodeAggregateId->value . '", actual: "' . $actualNodeAggregateId->value . '"');
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

        try {
            return $this->currentContentRepository->getContentGraph()->findRootNodeAggregateByType(
                $this->currentContentStreamId,
                NodeTypeName::fromString('Neos.Neos:Sites')
            )->nodeAggregateId;
        } catch (RootNodeAggregateDoesNotExist) {
            return null;
        }
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
    public function iPruneUnusedContentStreams(): void
    {
        $contentStreamPruner = $this->getContentStreamPruner();
        $contentStreamPruner->prune();
        $this->lastCommandOrEventResult = $contentStreamPruner->getLastCommandResult();
    }

    /**
     * @When I prune removed content streams from the event stream
     */
    public function iPruneRemovedContentStreamsFromTheEventStream(): void
    {
        $this->getContentStreamPruner()->pruneRemovedFromEventStream();
    }

    abstract protected function getContentStreamPruner(): ContentStreamPruner;

    /**
     * @When I replay the :projectionName projection
     */
    public function iReplayTheProjection(string $projectionName)
    {
        $this->currentContentRepository->resetProjectionState($projectionName);
        $this->currentContentRepository->catchUpProjection($projectionName);
    }

    protected function deserializeProperties(array $properties): PropertyValuesToWrite
    {
        return PropertyValuesToWrite::fromArray($properties);
    }
}
