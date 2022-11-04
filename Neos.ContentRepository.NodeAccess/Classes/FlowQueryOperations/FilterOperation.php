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
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Utility\ObjectAccess;

/**
 * This filter implementation contains specific behavior for use on ContentRepository
 * nodes. It will not evaluate any elements that are not instances of the
 * `Node`.
 *
 * The implementation changes the behavior of the `instanceof` operator to
 * work on node types instead of PHP object types, so that::
 *
 *  [instanceof Neos.NodeTypes:Page]
 *
 * will in fact use `isOfType()` on the `NodeType` of context elements to
 * filter. This filter allow also to filter the current context by a given
 * node. Anything else remains unchanged.
 */
class FilterOperation extends \Neos\Eel\FlowQuery\Operations\Object\FilterOperation
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
     * @param array $context $context onto which this operation should be applied (array or array-like object)
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     */
    public function canEvaluate($context)
    {
        return (isset($context[0]) && ($context[0] instanceof Node));
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

        if ($arguments[0] instanceof Node) {
            $filteredContext = [];
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
     * @return boolean true if the property name filter matches
     */
    protected function matchesPropertyNameFilter($element, $propertyNameFilter)
    {
        /* @var Node $element */
        try {
            return ((string)$element->nodeName === $propertyNameFilter);
        } catch (\InvalidArgumentException $e) {
            // in case the Element has no valid node name, we do not match!
            return false;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param Node $element
     * @param string $identifier
     * @return boolean
     */
    protected function matchesIdentifierFilter($element, $identifier)
    {
        return (strtolower((string)$element->nodeAggregateId) === strtolower($identifier));
    }

    /**
     * {@inheritdoc}
     *
     * @param Node $element
     * @param string $propertyPath
     * @return mixed
     */
    protected function getPropertyPath($element, $propertyPath)
    {
        if ($propertyPath[0] === '_' && $propertyPath !== '_hiddenInIndex') {
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
        if ($operator === 'instanceof' && $value instanceof Node) {
            if ($this->operandIsSimpleType($operand)) {
                return $this->handleSimpleTypeOperand($operand, $value);
            } elseif ($operand === Node::class) {
                return true;
            } else {
                $isOfType = $value->nodeType->isOfType($operand[0] === '!' ? substr($operand, 1) : $operand);
                return $operand[0] === '!' ? $isOfType === false : $isOfType;
            }
        } elseif ($operator === '!instanceof' && $value instanceof Node) {
            return !$this->evaluateOperator($value, 'instanceof', $operand);
        }
        return parent::evaluateOperator($value, $operator, $operand);
    }

}
