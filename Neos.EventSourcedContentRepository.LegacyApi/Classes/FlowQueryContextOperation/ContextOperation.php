<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\LegacyApi\FlowQueryContextOperation;

use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModelInterface;
use Neos\ContentRepository\Intermediary\Domain\ReadModelFactory;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedContentRepository\LegacyApi\Logging\LegacyLoggerInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Utility\ObjectAccess;

/**
 */
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
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * @Flow\Inject
     * @var LegacyLoggerInterface
     */
    protected $legacyLogger;

    /**
     * @Flow\Inject
     * @var ReadModelFactory
     */
    protected $readModelFactory;

    private const SUPPORTED_ADJUSTMENTS = ['invisibleContentShown', 'workspaceName'];

    public function canEvaluate($context)
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof NodeBasedReadModelInterface));
    }

    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        if (!isset($arguments[0]) || !is_array($arguments[0])) {
            throw new FlowQueryException('context() requires an array argument of context properties', 1398030427);
        }

        $this->legacyLogger->info('FlowQuery context(' . json_encode($arguments[0]) . ' called', LogEnvironment::fromMethodName(__METHOD__));

        $targetContext = $arguments[0];

        $forbiddenTargetContextAdjustments = array_diff(array_keys($targetContext), self::SUPPORTED_ADJUSTMENTS);
        if (count($forbiddenTargetContextAdjustments) > 0) {
            // TODO: maybe log this instead of throwing exception??
            throw new FlowQueryException('LEGACY LAYER: we only support the following context adjustments :' . implode(', ', self::SUPPORTED_ADJUSTMENTS) . ' - you tried ' . implode(', ', $forbiddenTargetContextAdjustments));
        }

        $output = [];
        foreach ($flowQuery->getContext() as $contextNode) {
            /** @var NodeBasedReadModelInterface $contextNode */

            // we start modifying the subgraph step-by-step.
            $subgraph = self::getSubgraphFromNode($contextNode);

            $visibilityConstraints = VisibilityConstraints::frontend();
            if (array_key_exists('invisibleContentShown', $targetContext)) {
                $invisibleContentShown = boolval($targetContext['invisibleContentShown']);

                $visibilityConstraints = ($invisibleContentShown ? VisibilityConstraints::withoutRestrictions() : VisibilityConstraints::frontend());
                $subgraph = $this->contentGraph->getSubgraphByIdentifier($subgraph->getContentStreamIdentifier(), $subgraph->getDimensionSpacePoint(), $visibilityConstraints);
            }

            if (array_key_exists('workspaceName', $targetContext)) {
                $workspaceName = new WorkspaceName($targetContext['workspaceName']);
                $workspace = $this->workspaceFinder->findOneByName($workspaceName);

                $subgraph = $this->contentGraph->getSubgraphByIdentifier($workspace->getCurrentContentStreamIdentifier(), $subgraph->getDimensionSpacePoint(), $visibilityConstraints);
            }

            $nodeInModifiedSubgraph = $subgraph->findNodeByNodeAggregateIdentifier($contextNode->getNodeAggregateIdentifier());
            if ($nodeInModifiedSubgraph !== null) {
                $output[$nodeInModifiedSubgraph->getNodeAggregateIdentifier()->jsonSerialize()] = $this->readModelFactory->createReadModel($nodeInModifiedSubgraph, $subgraph);
            }
        }

        $flowQuery->setContext(array_values($output));
    }

    private static function getSubgraphFromNode(NodeBasedReadModelInterface $contextNode): ContentSubgraphInterface
    {
        return ObjectAccess::getProperty($contextNode, 'subgraph', true);
    }
}
