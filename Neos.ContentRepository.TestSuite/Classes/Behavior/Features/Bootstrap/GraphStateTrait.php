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
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\GraphState;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\LocalGraphState;
use PHPUnit\Framework\Assert;

/**
 * The feature trait to test node aggregates
 */
/** @phpstan-ignore trait.unused (as if) */
trait GraphStateTrait
{
    use CRTestSuiteRuntimeVariables;

    /**
     * @var array<string,GraphState> indexed by workspace
     */
    protected array $memorisedGraphStates = [];

    #[BeforeScenario]
    public function resetMemorisedGraphStates(BeforeScenarioScope $scope): void
    {
        $this->memorisedGraphStates = [];
    }

    /**
     * @When /^I memorise the local graph state for node aggregate "([^"]*)" in workspace "([^"]*)"$/
     */
    public function iMemoriseTheLocalGraphStateForNodeAggregateInWorkspace(
        string $serializedNodeAggregateId,
        string $serializedWorkspaceName,
    ): void {
        Assert::assertArrayHasKey($serializedWorkspaceName, $this->memorisedGraphStates);
        $this->memorisedGraphStates[$serializedWorkspaceName]->registerItem(
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
                nodeAggregateIds: NodeAggregateIds::fromArray($this->findAllNodeAggregateIds($contentGraph, null, [])),
                contentGraph: $contentGraph,
                dimensionSpacePointSet: $this->currentContentRepository->getVariationGraph()->getDimensionSpacePoints(),
            );
        }
        $this->memorisedGraphStates = $graphStates;
    }

    /**
     * @Then /^I expect the graph state for workspace "([^"]*)" to be unchanged$/
     */
    public function iExpectTheGraphStateForWorkspaceToBeUnchanged(string $serializedWorkspaceName): void
    {
        Assert::assertNotNull($this->memorisedGraphStates[$serializedWorkspaceName]);

        $actualDiff = $this->memorisedGraphStates[$serializedWorkspaceName]->diff(
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
        if ($this->currentFeatureFile === null) {
            throw new \RuntimeException('Current feature file is not set');
        }
        if ($this->currentScenarioTitle === null) {
            throw new \RuntimeException('Current scenario title is not set');
        }
        $snapshotFilePath = \str_replace('.feature', '', $this->currentFeatureFile);
        $snapshotFilePath .= '_' . \str_replace(' ', '_', $this->currentScenarioTitle);
        $snapshotFilePath .= '.json';

        $actualDiff = \json_encode(
            $this->memorisedGraphStates[$serializedWorkspaceName]->diff(
                $this->fetchGraphState(WorkspaceName::fromString($serializedWorkspaceName)),
            ),
            JSON_PRETTY_PRINT,
        );
        if (!file_exists($snapshotFilePath)) {
            file_put_contents($snapshotFilePath, $actualDiff);
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
            nodeAggregateIds: NodeAggregateIds::fromArray(
                $this->findAllNodeAggregateIds($contentGraph, null, []),
            ),
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
