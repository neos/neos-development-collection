<?php
namespace Neos\Neos\Eel\FlowQueryOperations;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\ContentRepository\Domain\Model\NodeInterface;

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
     * {@inheritdoc}
     *
     * @param array (or array-like object) $context onto which this operation should be applied
     * @return boolean TRUE if the operation can be applied onto the $context, FALSE otherwise
     */
    public function canEvaluate($context)
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof NodeInterface) && ($context[0]->getContext() instanceof ContentContext));
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the arguments for this operation
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $output = array();
        $outputNodePaths = array();
        foreach ($flowQuery->getContext() as $contextNode) {
            /** @var NodeInterface $contextNode */
            $siteNode = $contextNode->getContext()->getCurrentSiteNode();
            while ($contextNode !== $siteNode && $contextNode->getParent() !== null) {
                $contextNode = $contextNode->getParent();
                if (!isset($outputNodePaths[$contextNode->getPath()])) {
                    $output[] = $contextNode;
                    $outputNodePaths[$contextNode->getPath()] = true;
                }
            }
        }

        $flowQuery->setContext($output);

        if (isset($arguments[0]) && !empty($arguments[0])) {
            $flowQuery->pushOperation('filter', $arguments);
        }
    }
}
