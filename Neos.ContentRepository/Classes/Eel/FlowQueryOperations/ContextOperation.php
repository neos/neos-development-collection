<?php
namespace Neos\ContentRepository\Eel\FlowQueryOperations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;

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
    protected static $priority = 1;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * {@inheritdoc}
     *
     * @param array (or array-like object) $context onto which this operation should be applied
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     */
    public function canEvaluate($context)
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof NodeInterface));
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery The FlowQuery object
     * @param array $arguments The arguments for this operation
     * @todo reimplement using TraversableNodeInterface / new NodeInterface once subgraphs are available
     * @return void
     * @throws FlowQueryException
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        if (!isset($arguments[0]) || !is_array($arguments[0])) {
            throw new FlowQueryException('context() requires an array argument of context properties', 1398030427);
        }

        $output = [];
        foreach ($flowQuery->getContext() as $contextNode) {
            /** @var NodeInterface $contextNode */
            $contextProperties = $contextNode->getContext()->getProperties();
            $modifiedContext = $this->contextFactory->create(array_merge($contextProperties, $arguments[0]));

            $nodeInModifiedContext = $modifiedContext->getNodeByIdentifier($contextNode->getIdentifier());
            if ($nodeInModifiedContext !== null) {
                $output[$nodeInModifiedContext->getPath()] = $nodeInModifiedContext;
            }
        }

        $flowQuery->setContext(array_values($output));
    }
}
