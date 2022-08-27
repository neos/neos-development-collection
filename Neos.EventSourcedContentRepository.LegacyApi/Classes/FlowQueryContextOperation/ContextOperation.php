<?php

declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\LegacyApi\FlowQueryContextOperation;

use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\EventSourcedContentRepository\LegacyApi\Logging\LegacyLoggerInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;

class ContextOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'context';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 10;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * @Flow\Inject
     * @var LegacyLoggerInterface
     */
    protected $legacyLogger;

    private const SUPPORTED_ADJUSTMENTS = ['invisibleContentShown', 'workspaceName'];

    /**
     * @param array<int,mixed> $context
     */
    public function canEvaluate($context): bool
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof Node));
    }

    /**
     * @param FlowQuery<int,mixed> $flowQuery
     * @param array<int,mixed> $arguments
     * @throws FlowQueryException
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments): void
    {
        if (!isset($arguments[0]) || !is_array($arguments[0])) {
            throw new FlowQueryException('context() requires an array argument of context properties', 1398030427);
        }

        $this->legacyLogger->info(
            'FlowQuery context(' . json_encode($arguments[0]) . ' called',
            LogEnvironment::fromMethodName(__METHOD__)
        );

        $targetContext = $arguments[0];

        $forbiddenTargetContextAdjustments = array_diff(array_keys($targetContext), self::SUPPORTED_ADJUSTMENTS);
        if (count($forbiddenTargetContextAdjustments) > 0) {
            // TODO: maybe log this instead of throwing exception??
            throw new FlowQueryException(
                'LEGACY LAYER: we only support the following context adjustments :'
                    . implode(', ', self::SUPPORTED_ADJUSTMENTS)
                    . ' - you tried ' . implode(', ', $forbiddenTargetContextAdjustments)
            );
        }

        $output = [];
        foreach ($flowQuery->getContext() as $contextNode) {
            /** @var Node $contextNode */

            $contentRepository = $this->contentRepositoryRegistry
                ->get($contextNode->subgraphIdentity->contentRepositoryIdentifier);
            $subgraphIdentity = $contextNode->subgraphIdentity;
            if (array_key_exists('invisibleContentShown', $targetContext)) {
                $invisibleContentShown = boolval($targetContext['invisibleContentShown']);

                $subgraphIdentity = $subgraphIdentity->withVisibilityConstraints(
                    $invisibleContentShown
                        ? VisibilityConstraints::withoutRestrictions()
                        : VisibilityConstraints::frontend()
                );
            }

            if (array_key_exists('workspaceName', $targetContext)) {
                $workspaceName = WorkspaceName::fromString($targetContext['workspaceName']);
                $contentRepository = $this->contentRepositoryRegistry->get(
                    $contextNode->subgraphIdentity->contentRepositoryIdentifier
                );

                $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName);
                if (!is_null($workspace)) {
                    $subgraphIdentity = $subgraphIdentity->withContentStreamIdentifier(
                        $workspace->currentContentStreamIdentifier
                    );
                }
            }

            $nodeInModifiedSubgraph = $contentRepository->getContentGraph()
                ->getSubgraph(
                    $subgraphIdentity->contentStreamIdentifier,
                    $subgraphIdentity->dimensionSpacePoint,
                    $subgraphIdentity->visibilityConstraints
                )
                ->findNodeByNodeAggregateIdentifier($contextNode->nodeAggregateIdentifier);
            if ($nodeInModifiedSubgraph !== null) {
                $output[$nodeInModifiedSubgraph->nodeAggregateIdentifier->__toString()] = $nodeInModifiedSubgraph;
            }
        }

        $flowQuery->setContext(array_values($output));
    }
}
