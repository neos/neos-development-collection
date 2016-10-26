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
use TYPO3\Eel\FlowQuery\FlowQueryException;
use TYPO3\Eel\FlowQuery\Operations\AbstractOperation;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Used to access properties of a TYPO3CR Node. If the property mame is
 * prefixed with _, internal node properties like start time, end time,
 * hidden are accessed.
 */
class PropertyOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'property';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 100;

    /**
     * {@inheritdoc}
     *
     * @var boolean
     */
    protected static $final = true;

    /**
     * {@inheritdoc}
     *
     * We can only handle TYPO3CR Nodes.
     *
     * @param mixed $context
     * @return boolean
     */
    public function canEvaluate($context)
    {
        return (isset($context[0]) && ($context[0] instanceof NodeInterface));
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the arguments for this operation
     * @return mixed
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        if (!isset($arguments[0]) || empty($arguments[0])) {
            throw new FlowQueryException('property() does not support returning all attributes yet', 1332492263);
        } else {
            $context = $flowQuery->getContext();
            $propertyPath = $arguments[0];

            if (!isset($context[0])) {
                return null;
            }

            $element = $context[0];
            if ($propertyPath[0] === '_') {
                return ObjectAccess::getPropertyPath($element, substr($propertyPath, 1));
            } else {
                return $element->getProperty($propertyPath);
            }
        }
    }
}
