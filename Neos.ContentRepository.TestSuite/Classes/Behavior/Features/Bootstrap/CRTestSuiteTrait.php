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
use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\CatchUpOptions;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\Service\ContentStreamPruner;
use Neos\ContentRepository\Core\Service\ContentStreamPrunerFactory;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamStatus;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features\ContentStreamClosing;
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
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features\SubtreeTagging;
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
    use ContentStreamClosing;

    use NodeCreation;
    use NodeCopying;
    use NodeDisabling;
    use SubtreeTagging;
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

    /**
     * @BeforeScenario
     * @throws \Exception
     */
    public function beforeEventSourcedScenarioDispatcher(BeforeScenarioScope $scope): void
    {
        if (isset($this->contentRepositories)) {
            $this->contentRepositories = [];
        }
        $this->currentContentRepository = null;
        $this->currentVisibilityConstraints = VisibilityConstraints::frontend();
        $this->currentDimensionSpacePoint = null;
        $this->currentRootNodeAggregateId = null;
        $this->currentWorkspaceName = null;
        $this->currentNodeAggregate = null;
        $this->currentNode = null;
    }

    /**
     * @return array<string,mixed>
     * @throws \Exception
     */
    protected function readPayloadTable(TableNode $payloadTable): array
    {
        $eventPayload = [];
        foreach ($payloadTable->getHash() as $line) {
            if (\str_starts_with($line['Value'], '$this->')) {
                // Special case: Referencing stuff from the context here
                $propertyOrMethodName = \mb_substr($line['Value'], \mb_strlen('$this->'));
                $value = match ($propertyOrMethodName) {
                    'currentNodeAggregateId' => $this->getCurrentNodeAggregateId()->value,
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
     * @Then /^I expect the content stream "([^"]*)" to not exist$/
     */
    public function iExpectTheContentStreamToNotExist(string $rawContentStreamId): void
    {
        $contentStream = $this->currentContentRepository->findContentStreamById(ContentStreamId::fromString($rawContentStreamId));
        Assert::assertNull($contentStream, sprintf('Content stream "%s" was not expected to exist, but it does', $rawContentStreamId));
    }

    /**
     * @Then /^workspace ([^"]*) has status ([^"]*)$/
     */
    public function workspaceHasStatus(string $rawWorkspaceName, string $status): void
    {
        $workspace = $this->currentContentRepository->findWorkspaceByName(WorkspaceName::fromString($rawWorkspaceName));

        Assert::assertSame($status, $workspace?->status->value);
    }

    /**
     * @Then /^I expect the graph projection to consist of exactly (\d+) node(?:s)?$/
     * @param int $expectedNumberOfNodes
     */
    public function iExpectTheGraphProjectionToConsistOfExactlyNodes(int $expectedNumberOfNodes): void
    {
        // HACK to access
        $contentGraphReadModelAccess = new class implements ContentRepositoryServiceFactoryInterface {
            public ContentGraphReadModelInterface|null $instance;
            public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentRepositoryServiceInterface
            {
                $this->instance = $serviceFactoryDependencies->projectionsAndCatchUpHooks->contentGraphProjection->getState();
                return new class implements ContentRepositoryServiceInterface
                {
                };
            }
        };
        $this->getContentRepositoryService($contentGraphReadModelAccess);

        $actualNumberOfNodes = $contentGraphReadModelAccess->instance->countNodes();
        Assert::assertSame($expectedNumberOfNodes, $actualNumberOfNodes, 'Content graph consists of ' . $actualNumberOfNodes . ' nodes, expected were ' . $expectedNumberOfNodes . '.');
    }

    /**
     * @Then /^the subtree for node aggregate "([^"]*)" with node types "([^"]*)" and (\d+) levels deep should be:$/
     */
    public function theSubtreeForNodeAggregateWithNodeTypesAndLevelsDeepShouldBe(
        string $serializedNodeAggregateId,
        string $serializedNodeTypeCriteria,
        int $maximumLevels,
        TableNode $table
    ): void {
        $nodeAggregateId = NodeAggregateId::fromString($serializedNodeAggregateId);
        $nodeTypeCriteria = NodeTypeCriteria::fromFilterString($serializedNodeTypeCriteria);
        $expectedRows = $table->getHash();

        $subtree = $this->getCurrentSubgraph()
            ->findSubtree($nodeAggregateId, FindSubtreeFilter::create(nodeTypes: $nodeTypeCriteria, maximumLevels: $maximumLevels));

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
            $actualNodeAggregateId = $flattenedSubtree[$i]->node->aggregateId;
            Assert::assertTrue(
                $expectedNodeAggregateId->equals($actualNodeAggregateId),
                'NodeAggregateId does not match in index ' . $i . ', expected: "' . $expectedNodeAggregateId->value . '", actual: "' . $actualNodeAggregateId->value . '"'
            );
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

        return $this->currentContentRepository->getContentGraph($this->currentWorkspaceName)->findRootNodeAggregateByType(
            NodeTypeName::fromString('Neos.Neos:Sites')
        )->nodeAggregateId;
    }

    /**
     * @Then the content stream :contentStreamId has status :expectedState
     */
    public function theContentStreamHasStatus(string $contentStreamId, string $expectedStatus): void
    {
        $contentStream = $this->currentContentRepository->findContentStreamById(ContentStreamId::fromString($contentStreamId));
        if ($contentStream === null) {
            Assert::fail(sprintf('Expected content stream "%s" to have status "%s" but it does not exist', $contentStreamId, $expectedStatus));
        }
        Assert::assertSame(ContentStreamStatus::tryFrom($expectedStatus), $contentStream->status);
    }

    /**
     * @Then the current content stream has status :expectedStatus
     */
    public function theCurrentContentStreamHasStatus(string $expectedStatus): void
    {
        $this->theContentStreamHasStatus(
            $this->currentContentRepository
                ->findWorkspaceByName($this->currentWorkspaceName)
                ->currentContentStreamId->value,
            $expectedStatus
        );
    }

    /**
     * @When I prune unused content streams
     */
    public function iPruneUnusedContentStreams(): void
    {
        /** @var ContentStreamPruner $contentStreamPruner */
        $contentStreamPruner = $this->getContentRepositoryService(new ContentStreamPrunerFactory());
        $contentStreamPruner->prune(false, fn () => null);
    }

    /**
     * @When I prune removed content streams from the event stream
     */
    public function iPruneRemovedContentStreamsFromTheEventStream(): void
    {
        $this->getContentRepositoryService(new ContentStreamPrunerFactory())->pruneRemovedFromEventStream();
    }

    abstract protected function getContentRepositoryService(
        ContentRepositoryServiceFactoryInterface $factory
    ): ContentRepositoryServiceInterface;

    /**
     * @When I replay the :projectionName projection
     */
    public function iReplayTheProjection(string $projectionName): void
    {
        $this->currentContentRepository->resetProjectionState($projectionName);
        $this->currentContentRepository->catchUpProjection($projectionName, CatchUpOptions::create());
    }

    protected function deserializeProperties(array $properties): PropertyValuesToWrite
    {
        return PropertyValuesToWrite::fromArray(
            array_map(
                static fn (mixed $value) => is_array($value) && isset($value['__type']) ? new $value['__type']($value['value']) : $value,
                $properties
            )
        );
    }
}
