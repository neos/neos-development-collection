<?php

namespace Neos\TimeableNodeVisibility\Service;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\DisableNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\EnableNodeAggregate;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\OrCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueLessThanOrEqual;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\RootNodeAggregateDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\TimeableNodeVisibility\Domain\ChangedVisibilities;
use Neos\TimeableNodeVisibility\Domain\ChangedVisibility;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[Flow\Scope('singleton')]
class TimeableNodeVisibilityService
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected LoggerInterface $logger;

    public function handleExceededNodeDates(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): ChangedVisibilities
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $now = new \DateTimeImmutable();

        $nodes = $this->getNodesWithExceededDates($contentRepository, $workspaceName, $now);
        $results = [];

        /** @var Node $node */
        foreach ($nodes as $node) {
            $nodeIsDisabled = $node->tags->contain(SubtreeTag::disabled());
            if ($this->needsEnabling($node, $now) && $nodeIsDisabled) {
                $contentRepository->handle(
                    EnableNodeAggregate::create(
                        $workspaceName,
                        $node->nodeAggregateId,
                        $node->subgraphIdentity->dimensionSpacePoint,
                        NodeVariantSelectionStrategy::STRATEGY_ALL_SPECIALIZATIONS
                    )
                );

                $results[] = $result = ChangedVisibility::createForNodeWasEnabled($node);
                $this->logResult($result);

            }
            if ($this->needsDisabling($node, $now) && !$nodeIsDisabled) {
                $contentRepository->handle(
                    DisableNodeAggregate::create(
                        $workspaceName,
                        $node->nodeAggregateId,
                        $node->subgraphIdentity->dimensionSpacePoint,
                        NodeVariantSelectionStrategy::STRATEGY_ALL_SPECIALIZATIONS
                    )
                );

                $results[] = $result = ChangedVisibility::createForNodeWasDisabled($node);
                $this->logResult($result);
            }
        }
        return new ChangedVisibilities(...$results);
    }

    /**
     * @return \Generator<Node>
     */
    private function getNodesWithExceededDates(ContentRepository $contentRepository, WorkspaceName $workspaceName, \DateTimeImmutable $now): \Generator
    {
        $dimensionSpacePoints = $contentRepository->getVariationGraph()->getDimensionSpacePoints();

        foreach ($dimensionSpacePoints as $dimensionSpacePoint) {

            $contentGraph = $contentRepository->getContentGraph($workspaceName);

            // We fetch without restriction to get also all disabled nodes
            $subgraph = $contentGraph->getSubgraph(
                $dimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions()
            );

            $sitesNodeTypeName = NodeTypeName::fromString('Neos.Neos:Sites');
            $rootNode = $subgraph->findRootNodeByType($sitesNodeTypeName);
            if ($rootNode === null) {
                throw RootNodeAggregateDoesNotExist::butWasExpectedTo($sitesNodeTypeName);
            }

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
                    // The node will be enabled by node in origin dimension space-point
                    continue;
                }

                yield $node;
            }
        }
    }

    private function needsEnabling(Node $node, \DateTimeImmutable $now): bool
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

    private function needsDisabling(Node $node, \DateTimeImmutable $now): bool
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

    private function logResult(ChangedVisibility $result): void
    {
        $this->logger->info(
            sprintf('Timed node visibility: %s node [NodeAggregateId: %s, DimensionSpacePoints: %s]',
                $result->type->value,
                $result->node->nodeAggregateId->value,
                implode(',', $result->node->originDimensionSpacePoint->coordinates)
            )
        );
    }
}
