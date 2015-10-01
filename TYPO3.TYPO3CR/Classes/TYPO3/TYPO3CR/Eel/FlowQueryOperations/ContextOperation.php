<?php
namespace TYPO3\TYPO3CR\Eel\FlowQueryOperations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Eel\FlowQuery\Operations\AbstractOperation;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * "context" operation working on TYPO3CR nodes. Modifies the TYPO3CR Context of each
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
     * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * {@inheritdoc}
     *
     * @param array (or array-like object) $context onto which this operation should be applied
     * @return boolean TRUE if the operation can be applied onto the $context, FALSE otherwise
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
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        if (!isset($arguments[0]) || !is_array($arguments[0])) {
            throw new \TYPO3\Eel\FlowQuery\FlowQueryException('context() requires an array argument of context properties', 1398030427);
        }

        $output = array();
        foreach ($flowQuery->getContext() as $contextNode) {
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
