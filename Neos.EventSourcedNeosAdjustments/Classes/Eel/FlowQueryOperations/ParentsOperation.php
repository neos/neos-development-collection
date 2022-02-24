<?php
namespace Neos\EventSourcedNeosAdjustments\Eel\FlowQueryOperations;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorManager;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;

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
    protected static $priority = 110;

    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    /**
     * {@inheritdoc}
     *
     * @param array<int,mixed> $context (or array-like object) onto which this operation should be applied
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     */
    public function canEvaluate($context)
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof NodeInterface));
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery<int,mixed> $flowQuery the FlowQuery object
     * @param array<int,mixed> $arguments the arguments for this operation
     * @todo Compare to node type Neos.Neos:Site instead of path once it is available
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $parents = [];
        /* @var NodeInterface $contextNode */
        foreach ($flowQuery->getContext() as $contextNode) {
            $node = $contextNode;
            do {
                $node = $this->nodeAccessorManager->accessorFor(
                    $node->getContentStreamIdentifier(),
                    $node->getDimensionSpacePoint(),
                    $node->getVisibilityConstraints()
                )->findParentNode($node);
                if ($node === null) {
                    // no parent found
                    break;
                }
                // stop at sites
                if ($node->getNodeTypeName() === NodeTypeName::fromString('Neos.Neos:Sites')) {
                    break;
                }
                $parents[] = $node;
            } while (true);
        }

        $flowQuery->setContext($parents);

        if (isset($arguments[0]) && !empty($arguments[0])) {
            $flowQuery->pushOperation('filter', $arguments);
        }
    }
}
