<?php
namespace TYPO3\TYPO3CR\Eel\FlowQueryOperations;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Eel\FlowQuery\Operations\AbstractOperation;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Now;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * "cacheLifetime" operation working on TYPO3CR nodes. Will get the minimum of all allowed cache lifetimes for the
 * nodes in the current FlowQuery context. This means it will evaluate to the nearest future value of the
 * hiddenBeforeDateTime or hiddenAfterDateTime properties of all nodes in the context. If none are set or all values
 * are in the past it will evaluate to NULL.
 *
 * To include already hidden nodes (with a hiddenBeforeDateTime value in the future) in the result, also invisible nodes
 * have to be included in the context. This can be achieved using the "context" operation before fetching child nodes.
 *
 * Example:
 *
 * 	q(node).context({'invisibleContentShown': true}).children().cacheLifetime()
 *
 */
class CacheLifetimeOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'cacheLifetime';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 1;

    /**
     * {@inheritdoc}
     *
     * @var boolean
     */
    protected static $final = true;

    /**
     * @Flow\Inject(lazy=false)
     * @var Now
     */
    protected $now;

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
     * @param array $arguments None
     * @return integer The cache lifetime in seconds or NULL if either no content collection was given or no child node had a "hiddenBeforeDateTime" or "hiddenAfterDateTime" property set
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $minimumDateTime = null;
        foreach ($flowQuery->getContext() as $contextNode) {
            $hiddenBeforeDateTime = $contextNode->getHiddenBeforeDateTime();
            if ($hiddenBeforeDateTime !== null && $hiddenBeforeDateTime > $this->now && ($minimumDateTime === null || $hiddenBeforeDateTime < $minimumDateTime)) {
                $minimumDateTime = $hiddenBeforeDateTime;
            }
            $hiddenAfterDateTime = $contextNode->getHiddenAfterDateTime();
            if ($hiddenAfterDateTime !== null && $hiddenAfterDateTime > $this->now && ($minimumDateTime === null || $hiddenAfterDateTime < $minimumDateTime)) {
                $minimumDateTime = $hiddenAfterDateTime;
            }
        }

        if ($minimumDateTime !== null) {
            $maximumLifetime = $minimumDateTime->getTimestamp() - $this->now->getTimestamp();
            if ($maximumLifetime > 0) {
                return $maximumLifetime;
            }
        }
        return null;
    }
}
