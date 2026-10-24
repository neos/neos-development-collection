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

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Hook\BeforeScenario;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\GraphState;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\LocalGraphState;
use PHPUnit\Framework\Assert;

/**
 * The feature trait to test node aggregates
 * @internal
 */
/** @phpstan-ignore trait.unused (only in Testing classes) */
trait GraphStateTrait
{
    use CRTestSuiteRuntimeVariables;

    /**
     * @var array<string,GraphState> indexed by workspace
     */
    protected array $graphState_memorisedGraphStates = [];

    protected int $graphState_snapshotCountPerScenario = 0;

    protected ?string $graphState_currentFeatureFile = null;

    protected ?string $graphState_currentScenarioTitle = null;

    #[BeforeScenario]
    public function setupGraphStates(BeforeScenarioScope $scope): void
    {
        $this->graphState_snapshotCountPerScenario = 0;
        $this->graphState_memorisedGraphStates = [];
        $this->graphState_currentFeatureFile = $scope->getFeature()->getFile();
        $this->graphState_currentScenarioTitle = $scope->getScenario()->getTitle();
    }

    /**
     * @When /^I memorise the local graph state for node aggregate "([^"]*)" in workspace "([^"]*)"$/
     */
    public function iMemoriseTheLocalGraphStateForNodeAggregateInWorkspace(
        string $serializedNodeAggregateId,
        string $serializedWorkspaceName,
    ): void {
        Assert::assertArrayHasKey($serializedWorkspaceName, $this->graphState_memorisedGraphStates);
        $this->graphState_memorisedGraphStates[$serializedWorkspaceName]->registerItem(
            NodeAggregateId::fromString($serializedNodeAggregateId),
            LocalGraphState::fromNodeAggregateIdContentGraphAndDimensionSpacePointSet(
                nodeAggregateId: NodeAggregateId::fromString($serializedNodeAggregateId),
                contentGraph: $this->currentContentRepository->getContentGraph(WorkspaceName::fromString($serializedWorkspaceName)),
                dimensionSpacePointSet: $this->currentContentRepository->getVariationGraph()->getDimensionSpacePoints(),
            )
        );
    }

    /**
     * @When /^I memorise the local graph state for node aggregate "([^"]*)" in all workspaces$/
     */
    public function iMemoriseTheLocalGraphStateForNodeAggregateInAllWorkspaces(string $serializedNodeAggregateId): void
    {
        foreach ($this->currentContentRepository->findWorkspaces() as $workspace) {
            $this->iMemoriseTheLocalGraphStateForNodeAggregateInWorkspace(
                $serializedNodeAggregateId,
                $workspace->workspaceName->value,
            );
        }
    }

    /**
     * @When /^I memorise the global graph state$/
     */
    public function iMemoriseTheGlobalGraphState(): void
    {
        $graphStates = [];
        foreach ($this->currentContentRepository->findWorkspaces() as $workspace) {
            $contentGraph = $this->currentContentRepository->getContentGraph($workspace->workspaceName);
            $graphStates[$workspace->workspaceName->value] = GraphState::forNodeAggregateIdsWorkSpaceNameAndContentRepository(
                nodeAggregateIds: $this->findAllNodeAggregateIds($contentGraph, null, []),
                contentGraph: $contentGraph,
                dimensionSpacePointSet: $this->currentContentRepository->getVariationGraph()->getDimensionSpacePoints(),
            );
        }
        $this->graphState_memorisedGraphStates = $graphStates;
    }

    /**
     * @Then /^I expect the graph state for workspace "([^"]*)" to be unchanged$/
     */
    public function iExpectTheGraphStateForWorkspaceToBeUnchanged(string $serializedWorkspaceName): void
    {
        Assert::assertNotNull($this->graphState_memorisedGraphStates[$serializedWorkspaceName]);

        $actualDiff = $this->graphState_memorisedGraphStates[$serializedWorkspaceName]->diff(
            $this->fetchGraphState(WorkspaceName::fromString($serializedWorkspaceName))
        );
        Assert::assertNull($actualDiff);
    }

    /**
     * @Then /^I expect the graph state for workspace "([^"]*)" to equal that of workspace "([^"]*)"$/
     */
    public function iExpectTheGraphStateForWorkspaceToEqualThatOfWorkspace(string $serializedWorkspaceName, string $serializedOtherWorkspaceName): void
    {
        Assert::assertNull(
            $this->fetchGraphState(WorkspaceName::fromString($serializedWorkspaceName))
                ->diff(
                    $this->fetchGraphState(WorkspaceName::fromString($serializedOtherWorkspaceName)),
                    WorkspaceName::fromString($serializedOtherWorkspaceName)
                ),
        );
    }

    /**
     * @Then /^I expect the graph state for workspace "([^"]*)" to have changed as declared in the snapshot$/
     */
    public function iExpectTheGraphStateForWorkspaceToHaveChangedAsDeclaredInTheSnapshot(string $serializedWorkspaceName): void
    {
        if ($this->graphState_snapshotCountPerScenario > 0) {
            throw new \RuntimeException('Currently only one snapshot per scenario is supported.', 1792776491);
        }
        if ($this->graphState_currentFeatureFile === null) {
            throw new \RuntimeException('Current feature file is not set');
        }
        if ($this->graphState_currentScenarioTitle === null) {
            throw new \RuntimeException('Current scenario title is not set');
        }
        ++$this->graphState_snapshotCountPerScenario;
        $snapshotDirectory = \str_replace('.feature', '', $this->graphState_currentFeatureFile);
        $snapshotFilePath = $snapshotDirectory . DIRECTORY_SEPARATOR . \str_replace(' ', '_', $this->graphState_currentScenarioTitle) . '.json';

        $graphDiff = $this->graphState_memorisedGraphStates[$serializedWorkspaceName]->diff(
            $this->fetchGraphState(workspaceName: WorkspaceName::fromString($serializedWorkspaceName)),
        );

        $graphDiffWithMetaData = [
            '@feature' => basename($this->graphState_currentFeatureFile),
            '@scenario' => $this->graphState_currentScenarioTitle,
            ...$graphDiff->toArray()
        ];

        $actualDiff = \json_encode(
            $graphDiffWithMetaData,
            JSON_PRETTY_PRINT,
        );
        if (!file_exists($snapshotFilePath)) {
            if (getenv('BEHAT_CREATE_SNAPSHOT_PATCH')) {
                if (!is_dir($snapshotDirectory)) {
                    mkdir($snapshotDirectory);
                }
                file_put_contents($snapshotFilePath, $actualDiff);
            } else {
                Assert::fail(sprintf('Snapshot path %s does not exist. Use BEHAT_CREATE_SNAPSHOT_PATCH=1 to write.', $snapshotFilePath));
            }
        }

        Assert::assertSame(
            trim(file_get_contents($snapshotFilePath)),
            $actualDiff,
        );
    }

    private function fetchGraphState(WorkspaceName $workspaceName): GraphState
    {
        $contentGraph = $this->currentContentRepository->getContentGraph($workspaceName);

        return GraphState::forNodeAggregateIdsWorkSpaceNameAndContentRepository(
            nodeAggregateIds: $this->findAllNodeAggregateIds($contentGraph, null, []),
            contentGraph: $contentGraph,
            dimensionSpacePointSet: $this->currentContentRepository->getVariationGraph()->getDimensionSpacePoints(),
        );
    }

    /**
     * @param array<string,NodeAggregateId> $nodeAggregateIdsSoFar
     * @return array<string,NodeAggregateId>
     */
    private function findAllNodeAggregateIds(
        ContentGraphInterface $contentGraph,
        ?NodeAggregateId $parentNodeAggregateId,
        array $nodeAggregateIdsSoFar,
    ): array {
        foreach (
            $parentNodeAggregateId
                ? $contentGraph->findChildNodeAggregates($parentNodeAggregateId)
                : $contentGraph->findRootNodeAggregates(FindRootNodeAggregatesFilter::create()) as $childNodeAggregate
        ) {
            $nodeAggregateIdsSoFar[$childNodeAggregate->nodeAggregateId->value] = $childNodeAggregate->nodeAggregateId;
            $nodeAggregateIdsSoFar = $this->findAllNodeAggregateIds(
                contentGraph: $contentGraph,
                parentNodeAggregateId: $childNodeAggregate->nodeAggregateId,
                nodeAggregateIdsSoFar: $nodeAggregateIdsSoFar,
            );
        }

        return $nodeAggregateIdsSoFar;
    }
}
