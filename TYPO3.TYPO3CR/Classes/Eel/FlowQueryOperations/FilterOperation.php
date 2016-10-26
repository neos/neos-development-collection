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
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * This filter implementation contains specific behavior for use on TYPO3CR
 * nodes. It will not evaluate any elements that are not instances of the
 * `NodeInterface`.
 *
 * The implementation changes the behavior of the `instanceof` operator to
 * work on node types instead of PHP object types, so that::
 *
 * 	[instanceof TYPO3.Neos.NodeTypes:Page]
 *
 * will in fact use `isOfType()` on the `NodeType` of context elements to
 * filter. This filter allow also to filter the current context by a given
 * node. Anything else remains unchanged.
 */
class FilterOperation extends \TYPO3\Eel\FlowQuery\Operations\Object\FilterOperation
{
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
        return (isset($context[0]) && ($context[0] instanceof NodeInterface));
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery
     * @param array $arguments
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        if (!isset($arguments[0]) || empty($arguments[0])) {
            return;
        }

        if ($arguments[0] instanceof NodeInterface) {
            $filteredContext = array();
            $context = $flowQuery->getContext();
            foreach ($context as $element) {
                if ($element === $arguments[0]) {
                    $filteredContext[] = $element;
                    break;
                }
            }
            $flowQuery->setContext($filteredContext);
        } else {
            parent::evaluate($flowQuery, $arguments);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param object $element
     * @param string $propertyNameFilter
     * @return boolean TRUE if the property name filter matches
     */
    protected function matchesPropertyNameFilter($element, $propertyNameFilter)
    {
        return ($element->getName() === $propertyNameFilter);
    }

    /**
     * {@inheritdoc}
     *
     * @param object $element
     * @param string $identifier
     * @return boolean
     */
    protected function matchesIdentifierFilter($element, $identifier)
    {
        return (strtolower($element->getIdentifier()) === strtolower($identifier));
    }

    /**
     * {@inheritdoc}
     *
     * @param object $element
     * @param string $propertyPath
     * @return mixed
     */
    protected function getPropertyPath($element, $propertyPath)
    {
        if ($propertyPath[0] === '_') {
            return ObjectAccess::getPropertyPath($element, substr($propertyPath, 1));
        } else {
            return $element->getProperty($propertyPath);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @param string $operator
     * @param mixed $operand
     * @return boolean
     */
    protected function evaluateOperator($value, $operator, $operand)
    {
        if ($operator === 'instanceof' && $value instanceof NodeInterface) {
            if ($this->operandIsSimpleType($operand)) {
                return $this->handleSimpleTypeOperand($operand, $value);
            } elseif ($operand === NodeInterface::class || $operand === Node::class) {
                return true;
            } else {
                $isOfType = $value->getNodeType()->isOfType($operand[0] === '!' ? substr($operand, 1) : $operand);
                return $operand[0] === '!' ? $isOfType === false : $isOfType;
            }
        } elseif ($operator === '!instanceof' && $value instanceof NodeInterface) {
            return !$this->evaluateOperator($value, 'instanceof', $operand);
        }
        return parent::evaluateOperator($value, $operator, $operand);
    }
}
