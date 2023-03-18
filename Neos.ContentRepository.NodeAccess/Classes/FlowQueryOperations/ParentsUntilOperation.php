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

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;

/**
 * "parentsUntil" operation working on ContentRepository nodes. It iterates over all
 * context elements and returns the parent nodes until the matching parent is found.
 * If an optional filter expression is provided as a second argument,
 * it only returns the nodes matching the given expression.
 */
class ParentsUntilOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'parentsUntil';

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
     * @throws \Neos\Eel\Exception
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $output = [];
        $outputNodeAggregateIds = [];
        foreach ($flowQuery->getContext() as $contextNode) {
            $parentNodes = $this->getParents($contextNode);
            if (isset($arguments[0]) && !empty($arguments[0] && !$parentNodes->isEmpty())) {
                $untilQuery = new FlowQuery([$parentNodes->first()]);
                $untilQuery->pushOperation('closest', [$arguments[0]]);
                $until = $untilQuery->getContext();
            }

            if (isset($until) && is_array($until) && !empty($until) && isset($until[0])) {
                $parentNodes = $parentNodes->until($until[0]);
            }

            foreach ($parentNodes as $parentNode) {
                if ($parentNode !== null
                    && !isset($outputNodeAggregateIds[(string)$parentNode->nodeAggregateId])) {
                    $outputNodeAggregateIds[(string)$parentNode->nodeAggregateId] = true;
                    $output[] = $parentNode;
                }
            }
        }

        $flowQuery->setContext($output);

        if (isset($arguments[1]) && !empty($arguments[1])) {
            $flowQuery->pushOperation('filter', $arguments[1]);
        }
    }

    protected function getParents(Node $contextNode): Nodes
    {
        $ancestors = [];
        $node = $contextNode;
        do {
            $node = $this->contentRepositoryRegistry->subgraphForNode($node)
                ->findParentNode($node);
            if ($node === null) {
                // no parent found
                break;
            }
            $ancestors[] = $node;
        } while (true);
        return Nodes::fromArray($ancestors);
    }
}
