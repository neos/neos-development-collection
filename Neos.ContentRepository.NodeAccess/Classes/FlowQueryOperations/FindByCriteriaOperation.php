<?php

declare(strict_types=1);

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

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\PropertyValueCriteriaParser;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;

/**
 * "findByCriteria" operation working on ContentRepository nodes. This operation allows for retrieval of descendant nodes
 *
 *  Argument 1 (string|null): nodeTypeFilter, A list of NodeType Names seperated by ",", disallowed NodeTypes are prefixed with "!"
 *
 *  Argument 2 (string|null): propertyValueCriteria A property criteria in the form
 *
 *  property criteria are specified as "<property> <operator> <value>". Multiple criteria can be combined using "AND", "OR", "NOT" and "()"
 *
 *
 *  property criteria support the following comparison operators:
 *
 *  =~  : Strict equality of case-insensitive value and operand
 *  =   : Strict equality of value and operand
 *  !=~ : Strict inequality of case-insensitive value and operand
 *  !=  : Strict inequality of value and operand
 *  <   : Value is less than operand
 *  <=  : Value is less than or equal to operand
 *  >   : Value is greater than operand
 *  >=  : Value is greater than or equal to operand
 *  $=~ : Value ends with operand (string-based) or case-insensitive value's last element is equal to operand (array-based)
 *  $=  : Value ends with operand (string-based) or value's last element is equal to operand (array-based)
 *  ^=~ : Value starts with operand (string-based) or case-insensitive value's first element is equal to operand (array-based)
 *  ^=  : Value starts with operand (string-based) or value's first element is equal to operand (array-based)
 *  *=~ : Value contains operand (string-based) or case-insensitive value contains an element that is equal to operand (array based)
 *  *=  : Value contains operand (string-based) or value contains an element that is equal to operand (array based)
 *
 * criteria can be combined using "AND" and "OR":
 *
 *   "prop1 ^= 'foo' AND (prop2 = 'bar' OR prop3 = 'baz')"
 *
 *  furthermore "NOT" can be used to negate a whole sub query
 *
 *   "prop1 ^= 'foo' AND NOT (prop2 = 'bar' OR prop3 = 'baz')"
 *
 * Argument 3 ({limit?:int, offset?:int}}): Pagination of the date
 *
 *
 * Example (node type):
 *
 *  q(node).findByCriteria('Neos.NodeTypes:Text')
 *
 * Example (multiple node types):
 *
 *  q(node).findByCriteria('Neos.NodeTypes:Text,Neos.NodeTypes:Image')
 *
 * Example (node type with property filter):
 *
 *  q(node).findByCriteria('Neos.NodeTypes:Text', 'text*="Neos"')
 *
 *  Example (node type with property filter and pagination):
 *
 *   q(node).findByCriteria('Neos.NodeTypes:Document', 'title*="Flow"', {limit:10, offset:2})
 */
class FindByCriteriaOperation extends AbstractOperation
{
    use CreateNodeHashTrait;

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'findByCriteria';

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
        foreach ($context as $contextNode) {
            if (!$contextNode instanceof Node) {
                return false;
            }
        }

        return true;
    }
    /**
     * This operation operates rather on the given Context object than on the given node
     * and thus may work with the legacy node interface until subgraphs are available
     * {@inheritdoc}
     *
     * @param FlowQuery<int,mixed> $flowQuery the FlowQuery object
     * @param array<int,mixed> $arguments the arguments for this operation
     * @throws FlowQueryException
     * @throws \Neos\Eel\Exception
     * @throws \Neos\Eel\FlowQuery\FizzleException
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments): void
    {
        /** @var array<int,Node> $contextNodes */
        $contextNodes = $flowQuery->getContext();
        if (count($contextNodes) === 0) {
            return;
        }

        $firstContextNode = reset($contextNodes);
        assert($firstContextNode instanceof Node);

        $nodeTypeFilter = $arguments[0] ?? null;
        $propertyValueFilter = $arguments[1] ?? null;
        $pagination =  $arguments[2] ?? null;

        assert($nodeTypeFilter === null || is_string($nodeTypeFilter));
        assert($propertyValueFilter === null || is_string($propertyValueFilter));
        assert($pagination === null || is_array($pagination));

        /** @var Node[] $result */
        $result = [];
        $findDescendentNodesFilter = FindDescendantNodesFilter::create(
            nodeTypes: $nodeTypeFilter ? NodeTypeCriteria::fromFilterString($nodeTypeFilter) : null,
            propertyValue: $propertyValueFilter ? PropertyValueCriteriaParser::parse($propertyValueFilter) : null,
            pagination: $pagination ? Pagination::fromArray($pagination) : null
        );

        /** @var Node $contextNode */
        foreach ($flowQuery->getContext() as $contextNode) {
            $subgraph = $this->contentRepositoryRegistry->subgraphForNode($contextNode);
            foreach ($subgraph->findDescendantNodes($contextNode->aggregateId, $findDescendentNodesFilter) as $descendant) {
                $result[] = $descendant;
            }
        }

        $flowQuery->setContext($result);
    }
}
