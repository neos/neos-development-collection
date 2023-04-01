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

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\ObjectAccess;

/**
 * Used to access properties of a ContentRepository Node. If the property mame is
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
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * {@inheritdoc}
     *
     * We can only handle ContentRepository Nodes.
     *
     * @param array $context $context onto which this operation should be applied (array or array-like object)
     * @return boolean
     */
    public function canEvaluate($context): bool
    {
        return (isset($context[0]) && $context[0] instanceof Node);
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery<int,mixed> $flowQuery the FlowQuery object
     * @param array<int,mixed> $arguments the arguments for this operation
     * @return mixed
     * @throws FlowQueryException
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments): mixed
    {
        if (empty($arguments[0])) {
            throw new FlowQueryException('property() does not support returning all attributes yet', 1332492263);
        }
        /** @var array<int,mixed> $context */
        $context = $flowQuery->getContext();
        $propertyName = $arguments[0];

        if (!isset($context[0])) {
            return null;
        }

        /* @var $element Node */
        $element = $context[0];
        if ($propertyName === '_path') {
            $subgraph = $this->contentRepositoryRegistry->subgraphForNode($element);
            return (string)$subgraph->retrieveNodePath($element->nodeAggregateId);
        }

        if ($propertyName[0] === '_') {
            return ObjectAccess::getPropertyPath($element, substr($propertyName, 1));
        }

        if ($element->nodeType->getPropertyType($propertyName) === 'reference') {
            $subgraph = $this->contentRepositoryRegistry->subgraphForNode($element);
            return (
                $subgraph->findReferences(
                    $element->nodeAggregateId,
                    FindReferencesFilter::referenceName($propertyName)
                )[0] ?? null
            )?->node;
        }

        if ($element->nodeType->getPropertyType($propertyName) === 'references') {
            $subgraph = $this->contentRepositoryRegistry->subgraphForNode($element);
            return $subgraph->findReferences(
                $element->nodeAggregateId,
                FindReferencesFilter::referenceName($propertyName)
            )->getNodes();
        }

        return $element->getProperty($propertyName);
    }
}
