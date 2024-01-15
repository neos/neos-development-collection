<?php

namespace Neos\TimeableNodeVisibility\Service;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepository\Core\Projection\NodeHiddenState\NodeHiddenStateFinder;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueLessThanOrEqual;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\EnableNodeAggregate;
use Psr\Log\LoggerInterface;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\DisableNodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\OrCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\TimeableNodeVisibility\Domain\HandlingResult;
use Neos\TimeableNodeVisibility\Domain\HandlingResultSet;

#[Flow\Scope('singleton')]
class TimeableNodeVisibilityService
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected LoggerInterface $logger;

    public function handleExceededNodeDates(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): HandlingResultSet
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $liveWorkspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName);
        $nodeHiddenStateFinder = $contentRepository->projectionState(NodeHiddenStateFinder::class);

        $now = new \DateTimeImmutable();

        $nodes = $this->getNodesWithExceededDates($contentRepository, $liveWorkspace, $now);
        $handledNodeResults = new HandlingResultSet();

        foreach ($nodes as $node) {
            if ($this->needsEnabling($node, $now) && $this->isHidden($node, $nodeHiddenStateFinder)) {
                $contentRepository->handle(
                    EnableNodeAggregate::create(
                        $node->subgraphIdentity->contentStreamId,
                        $node->nodeAggregateId,
                        $node->subgraphIdentity->dimensionSpacePoint,
                        NodeVariantSelectionStrategy::STRATEGY_ALL_SPECIALIZATIONS
                    )
                );

                $result = HandlingResult::createWithEnabled($node);
                $handledNodeResults->add($result);
                $this->logResult($result);

            } elseif ($this->needsDisabling($node, $now) && !$this->isHidden($node, $nodeHiddenStateFinder)) {
                $contentRepository->handle(
                    DisableNodeAggregate::create(
                        $node->subgraphIdentity->contentStreamId,
                        $node->nodeAggregateId,
                        $node->subgraphIdentity->dimensionSpacePoint,
                        NodeVariantSelectionStrategy::STRATEGY_ALL_SPECIALIZATIONS
                    )
                );

                $result = HandlingResult::createWithDisabled($node);
                $handledNodeResults->add($result);
                $this->logResult($result);
            }
        }
        return $handledNodeResults;
    }

    /**
     * @return \Generator<Node>
     */
    private function getNodesWithExceededDates(ContentRepository $contentRepository, Workspace $liveWorkspace, \DateTimeImmutable $now): \Generator
    {
        $dimensionSpacePoints = $contentRepository->getVariationGraph()->getDimensionSpacePoints();

        foreach ($dimensionSpacePoints as $dimensionSpacePoint) {

            $contentGraph = $contentRepository->getContentGraph();

            // We fetch without restriction to get also all disabled nodes
            $subgraph = $contentGraph->getSubgraph(
                $liveWorkspace->currentContentStreamId,
                $dimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions()
            );

            $rootNode = $subgraph->findRootNodeByType(NodeTypeName::fromString('Neos.Neos:Sites'));

            $nodes = $subgraph->findDescendantNodes(
                $rootNode->nodeAggregateId,
                FindDescendantNodesFilter::create(
                    nodeTypes: NodeTypeCriteria::createWithAllowedNodeTypeNames(NodeTypeNames::fromStringArray(['Neos.TimeableNodeVisibility:Timeable'])),
                    propertyValue: OrCriteria::create(
                        PropertyValueLessThanOrEqual::create(PropertyName::fromString('enableAfterDateTime'), $now->format(\DateTime::RFC3339)),
                        PropertyValueLessThanOrEqual::create(PropertyName::fromString('disableAfterDateTime'), $now->format(\DateTime::RFC3339)),
                    )

                )
            );

            foreach ($nodes as $node) {

                if (!$node->originDimensionSpacePoint->equals($dimensionSpacePoint)) {
                    // The node will be enabled by node in origin dimension spacepoint
                    continue;
                }

                yield $node;
            }
        }
    }

    private function isHidden(Node $node, ProjectionStateInterface $nodeHiddenStateFinder): bool
    {
        return $nodeHiddenStateFinder->findHiddenState(
            $node->subgraphIdentity->contentStreamId,
            $node->subgraphIdentity->dimensionSpacePoint,
            $node->nodeAggregateId
        )->isHidden;
    }

    private function needsEnabling(Node $node, \DateTimeImmutable $now)
    {
        return $node->hasProperty('enableAfterDateTime')
            && $node->getProperty('enableAfterDateTime') != null
            && $node->getProperty('enableAfterDateTime') < $now
            && (
                !$node->hasProperty('disableAfterDateTime')
                || $node->getProperty('disableAfterDateTime') == null
                || $node->getProperty('disableAfterDateTime') > $now
                || $node->getProperty('disableAfterDateTime') < $node->getProperty('enableAfterDateTime')
            );
    }

    private function needsDisabling(mixed $node, \DateTimeImmutable $now): bool
    {
        return $node->hasProperty('disableAfterDateTime')
            && $node->getProperty('disableAfterDateTime') != null
            && $node->getProperty('disableAfterDateTime') < $now
            && (
                !$node->hasProperty('enableAfterDateTime')
                || $node->getProperty('enableAfterDateTime') == null
                || $node->getProperty('enableAfterDateTime') > $now
                || $node->getProperty('enableAfterDateTime') <= $node->getProperty('disableAfterDateTime')
            );
    }

    private function logResult(HandlingResult $result): void
    {
        $this->logger->info(
            sprintf('Timed node visibility: %s node [NodeAggregateId: %s, DimensionSpacePoints: %s]: %s',
                $result->result,
                $result->node->nodeAggregateId->value,
                join(',', $result->node->originDimensionSpacePoint->coordinates),
                $result->node->getLabel())
        );
    }
}
