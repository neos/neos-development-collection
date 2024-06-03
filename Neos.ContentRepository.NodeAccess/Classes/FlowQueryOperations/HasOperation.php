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
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\FlowQuery\FizzleException;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;

/**
 * "has" operation working on Node. Reduce the set of matched elements
 * to those that have a child node that matches the selector or given subject.
 *
 * Accepts a selector, an array, an object, a traversable object & a FlowQuery
 * object as argument.
 */
class HasOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'has';

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
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof Node));
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery<int,mixed> $flowQuery
     * @param array<int,mixed> $arguments
     * @return void
     * @throws FizzleException
     * @throws \Neos\Eel\Exception
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $subject = $arguments[0];
        if (!isset($subject) || empty($subject)) {
            $flowQuery->setContext([]);
            return;
        }

        $filteredContext = [];
        $context = $flowQuery->getContext();
        if (is_string($subject)) {
            foreach ($context as $contextElement) {
                $contextElementQuery = new FlowQuery([$contextElement]);
                $contextElementQuery->pushOperation('children', $arguments);
                if ($contextElementQuery->count() > 0) {
                    $filteredContext[] = $contextElement;
                }
            }
        } else {
            if ($subject instanceof FlowQuery) {
                $elements = $subject->getContext();
            } elseif ($subject instanceof \Traversable) {
                $elements = iterator_to_array($subject);
            } elseif (is_object($subject)) {
                $elements = [$subject];
            } elseif (is_array($subject)) {
                $elements = $subject;
            } else {
                throw new FizzleException('supplied argument for has operation not supported', 1332489625);
            }
            foreach ($elements as $element) {
                if ($element instanceof Node) {
                    $parent = $this->contentRepositoryRegistry->subgraphForNode($element)
                        ->findParentNode($element->aggregateId);
                    if (!is_null($parent)) {
                        foreach ($context as $contextElement) {
                            /** @var Node $contextElement */
                            if ($contextElement === $parent) {
                                $filteredContext[] = $contextElement;
                            }
                        }
                    }
                }
            }
            $filteredContext = array_unique($filteredContext);
        }

        $flowQuery->setContext($filteredContext);
    }
}
