<?php

declare(strict_types=1);

namespace Neos\TimeableNodeVisibility\Service;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\DisableNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\EnableNodeAggregate;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\OrCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueLessThanOrEqual;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Neos\Domain\SubtreeTagging\NeosSubtreeTag;
use Neos\Neos\Domain\SubtreeTagging\NeosVisibilityConstraints;
use Neos\TimeableNodeVisibility\Domain\ChangedVisibilities;
use Neos\TimeableNodeVisibility\Domain\ChangedVisibility;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[Flow\Scope('singleton')]
class TimeableNodeVisibilityService
{
    /**
     * This prefilter timezone is the maximum possible timezone the datetime can have in the node property. This
     * widens the prefiltered nodes from content graph, which might get filtered exactly after converting to
     * PHPs DateTimeImmutable. A meaningful comparison of DateTimes with timezones in MySQL/MariaDB is not possible.
     */
    private const PREFILTER_TIMEZONE = '+14:00';

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected LoggerInterface $logger;

    #[Flow\Inject]
    protected ThrowableStorageInterface $throwableStorage;

    public function handleExceededNodeDates(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): ChangedVisibilities
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $now = new \DateTimeImmutable();

        $nodes = $this->getNodesWithExceededDates($contentRepository, $workspaceName, $now);
        $results = [];

        /** @var Node $node */
        foreach ($nodes as $node) {
            if ($this->needsEnabling($node, $now)) {
                try {
                    $contentRepository->handle(
                        EnableNodeAggregate::create(
                            $workspaceName,
                            $node->aggregateId,
                            $node->dimensionSpacePoint,
                            NodeVariantSelectionStrategy::STRATEGY_ALL_SPECIALIZATIONS
                        )
                    );

                    $results[] = $result = ChangedVisibility::createForNodeWasEnabled($node);
                } catch (\Exception $e) {
                    $results[] = $result = ChangedVisibility::createForError(
                        $node,
                        sprintf('Could not enable node: %s', $this->throwableStorage->logThrowable($e))
                    );
                }
                $this->logResult($result);
            }
            if ($this->needsDisabling($node, $now)) {
                try {
                    $contentRepository->handle(
                        DisableNodeAggregate::create(
                            $workspaceName,
                            $node->aggregateId,
                            $node->dimensionSpacePoint,
                            NodeVariantSelectionStrategy::STRATEGY_ALL_SPECIALIZATIONS
                        )
                    );

                    $results[] = $result = ChangedVisibility::createForNodeWasDisabled($node);
                } catch (\Exception $e) {
                    $results[] = $result = ChangedVisibility::createForError(
                        $node,
                        sprintf('Could not disable node: %s', $this->throwableStorage->logThrowable($e))
                    );
                }
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
        $upperBoundDateTimeWithPrefilterTimeZone = $now->setTimezone(new \DateTimeZone(self::PREFILTER_TIMEZONE));

        foreach ($dimensionSpacePoints as $dimensionSpacePoint) {

            $contentGraph = $contentRepository->getContentGraph($workspaceName);

            $subgraph = $contentGraph->getSubgraph(
                $dimensionSpacePoint,
                NeosVisibilityConstraints::excludeRemoved()
            );

            $sitesNodeTypeName = NodeTypeName::fromString('Neos.Neos:Sites');
            $rootNode = $subgraph->findRootNodeByType($sitesNodeTypeName);
            if ($rootNode === null) {
                throw new \RuntimeException(sprintf('No sites root node found in content repository "%s"', $contentRepository->id->value), 1719047148);
            }

            $nodes = $subgraph->findDescendantNodes(
                $rootNode->aggregateId,
                FindDescendantNodesFilter::create(
                    nodeTypes: NodeTypeCriteria::createWithAllowedNodeTypeNames(NodeTypeNames::fromStringArray(['Neos.TimeableNodeVisibility:Timeable'])),
                    propertyValue: OrCriteria::create(
                        PropertyValueLessThanOrEqual::create(PropertyName::fromString('enableAfterDateTime'), $upperBoundDateTimeWithPrefilterTimeZone->format(\DateTimeInterface::RFC3339)),
                        PropertyValueLessThanOrEqual::create(PropertyName::fromString('disableAfterDateTime'), $upperBoundDateTimeWithPrefilterTimeZone->format(\DateTimeInterface::RFC3339)),
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
        return $node->tags->withoutInherited()->contain(NeosSubtreeTag::disabled())
            && $node->hasProperty('enableAfterDateTime')
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
        return !$node->tags->withoutInherited()->contain(NeosSubtreeTag::disabled())
            && $node->hasProperty('disableAfterDateTime')
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
        if ($result->isError()) {
            $this->logger->error(
                sprintf(
                    'Timed node visibility: %s [NodeAggregateId: %s, DimensionSpacePoints: %s]',
                    $result->message,
                    $result->node->aggregateId->value,
                    implode(',', $result->node->originDimensionSpacePoint->coordinates)
                )
            );
            return;
        }

        $this->logger->info(
            sprintf(
                'Timed node visibility: %s node [NodeAggregateId: %s, DimensionSpacePoints: %s]',
                $result->type->value,
                $result->node->aggregateId->value,
                implode(',', $result->node->originDimensionSpacePoint->coordinates)
            )
        );
    }
}
