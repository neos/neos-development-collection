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

use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Exception\NodeException;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;

/**
 * "siblings" operation working on ContentRepository nodes. It iterates over all
 * context elements and returns all sibling nodes or only those matching
 * the filter expression specified as optional argument.
 */
class SiblingsOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'siblings';

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
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     */
    public function canEvaluate($context)
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof TraversableNodeInterface));
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
        $output = [];
        $outputNodeAggregateIdentifiers = [];
        foreach ($flowQuery->getContext() as $contextNode) {
            /** @var TraversableNodeInterface $contextNode */
            $outputNodeAggregateIdentifiers[(string)$contextNode->getNodeAggregateIdentifier()] = true;
        }

        foreach ($flowQuery->getContext() as $contextNode) {
            try {
                $parentNode = $contextNode->findParentNode();
            } catch (NodeException $e) {
                continue;
            }

            foreach ($parentNode->findChildNodes() as $childNode) {
                if (!isset($outputNodeAggregateIdentifiers[(string)$childNode->getNodeAggregateIdentifier()])) {
                    $output[] = $childNode;
                    $outputNodeAggregateIdentifiers[(string)$childNode->getNodeAggregateIdentifier()] = true;
                }
            }
        }
        $flowQuery->setContext($output);

        if (isset($arguments[0]) && !empty($arguments[0])) {
            $flowQuery->pushOperation('filter', $arguments);
        }
    }
}
