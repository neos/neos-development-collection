<?php
namespace Neos\ContentRepository\NodeAccess\FlowQueryOperations;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

/**
 * "parents" operation working on ContentRepository nodes. It iterates over all
 * context elements and returns the parent nodes or only those matching
 * the filter expression specified as optional argument.
 */
class ParentsOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'parents';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 100;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * {@inheritdoc}
     *
     * @param array<int,mixed> $context (or array-like object) onto which this operation should be applied
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     */
    public function canEvaluate($context)
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof Node));
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery<int,mixed> $flowQuery the FlowQuery object
     * @param array<int,mixed> $arguments the arguments for this operation
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $parents = Nodes::createEmpty();
        $findAncestorNodesFilter = FindAncestorNodesFilter::create(
            NodeTypeCriteria::createWithDisallowedNodeTypeNames(
                NodeTypeNames::fromStringArray(['Neos.ContentRepository:Root'])
            )
        );

        /* @var Node $contextNode */
        foreach ($flowQuery->getContext() as $contextNode) {
            $ancestorNodes = $this->contentRepositoryRegistry
                ->subgraphForNode($contextNode)
                ->findAncestorNodes(
                    $contextNode->nodeAggregateId,
                    $findAncestorNodesFilter
                );
            $parents = $parents->merge($ancestorNodes);
        }

        $flowQuery->setContext(iterator_to_array($parents));

        if (isset($arguments[0]) && !empty($arguments[0])) {
            $flowQuery->pushOperation('filter', $arguments);
        }
    }
}
