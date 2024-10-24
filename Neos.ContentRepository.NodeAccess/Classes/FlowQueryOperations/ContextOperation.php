<?php
namespace Neos\ContentRepository\NodeAccess\FlowQueryOperations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\NodeAccess\FlowQueryOperations\CreateNodeHashTrait;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;

/**
 * "context" operation working on ContentRepository nodes. Modifies the ContentRepository Context of each
 * node in the current FlowQuery context by the given properties and returns the same
 * nodes by identifier if they can be accessed in the new Context (otherwise they
 * will be skipped).
 *
 * Example:
 *
 * 	q(node).context({'invisibleContentShown': true}).children()
 *
 * Supported options:
 * - workspaceName
 * - dimensions
 * - invisibleContentShown
 *
 * Unsupported options:
 * - currentDateTime
 * - targetDimensions
 * - removedContentShown
 * - inaccessibleContentShown
 *
 */
class ContextOperation extends AbstractOperation
{
    use CreateNodeHashTrait;

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
    protected static $priority = 1;

    #[Flow\Inject()]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * {@inheritdoc}
     *
     * @param array $context $context onto which this operation should be applied (array or array-like object)
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     */
    public function canEvaluate($context)
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof Node));
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery The FlowQuery object
     * @param array $arguments The arguments for this operation
     * @return void
     * @throws FlowQueryException
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        if (!isset($arguments[0]) || !is_array($arguments[0])) {
            throw new FlowQueryException('context() requires an array argument of context properties', 1398030427);
        }

        $newContextProperties = $arguments[0];
        $newWorkspaceName = isset($newContextProperties['workspaceName']) ? WorkspaceName::fromString($newContextProperties['workspaceName']) : null;
        $newDimensions = isset($newContextProperties['dimensions']) ? DimensionSpacePoint::fromLegacyDimensionArray($newContextProperties['dimensions']) : null;
        $newInvisibleContentShown = isset($newContextProperties['invisibleContentShown']) ? (
            $newContextProperties['invisibleContentShown']
                ? VisibilityConstraints::withoutRestrictions()
                : VisibilityConstraints::frontend()
        ) : null;

        unset($newContextProperties['workspaceName']);
        unset($newContextProperties['dimensions']);
        unset($newContextProperties['invisibleContentShown']);

        if (!empty($newContextProperties)) {
            throw new FlowQueryException('context() doesnt support the legacy properties: ' . join(', ', $newContextProperties), 1717592463);
        }

        $output = [];
        /** @var Node $contextNode */
        foreach ($flowQuery->getContext() as $contextNode) {
            $contentRepository = $this->contentRepositoryRegistry->get($contextNode->contentRepositoryId);
            $newSubgraph = $contentRepository->getContentGraph(
                $newWorkspaceName ?? $contextNode->workspaceName
            )->getSubgraph(
                $newDimensions ?? $contextNode->dimensionSpacePoint,
                $newInvisibleContentShown ?? $contextNode->visibilityConstraints
            );

            $nodeInModifiedContext = $newSubgraph->findNodeById($contextNode->aggregateId);
            if ($nodeInModifiedContext !== null) {
                $output[$this->createNodeHash($nodeInModifiedContext)] = $nodeInModifiedContext;
            }
        }

        $flowQuery->setContext(array_values($output));
    }
}
