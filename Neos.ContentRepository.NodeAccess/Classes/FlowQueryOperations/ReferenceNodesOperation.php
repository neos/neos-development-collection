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
use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\OperationInterface;
use Neos\Flow\Annotations as Flow;

/**
 * "referenceNodes" operation working on Nodes
 *
 * This operation can be used to find the nodes that are referenced from a given node:
 *
 *     ${q(node).referenceNodes().get()}
 *
 * If a referenceName is given as argument only the references for this name are returned
 *
 *     ${q(node).referenceNodes("someReferenceName").}
 *
 * @see BackReferenceNodesOperation
 * @api To be used in Fusion, for PHP code {@see ContentSubgraphInterface::findReferences()} should be used instead
 */
final class ReferenceNodesOperation implements OperationInterface
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'referenceNodes';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 0;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /** @param array<int, mixed> $context */
    public function canEvaluate($context): bool
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof Node));
    }

    /** @param array<int, mixed> $arguments */
    public function evaluate(FlowQuery $flowQuery, array $arguments): void
    {
        $output = [];
        $filter = FindReferencesFilter::create();
        if (isset($arguments[0])) {
            $filter = $filter->with(referenceName: $arguments[0]);
        }
        /** @var Node $contextNode */
        foreach ($flowQuery->getContext() as $contextNode) {
            $subgraph = $this->contentRepositoryRegistry->subgraphForNode($contextNode);
            $output[] = iterator_to_array($subgraph->findReferences($contextNode->aggregateId, $filter));
        }
        $flowQuery->setContext(array_map(fn(Reference $reference) => $reference->node, array_merge(...$output)));
    }

    public static function getShortName(): string
    {
        return 'referenceNodes';
    }

    public static function getPriority(): int
    {
        return 100;
    }

    public static function isFinal(): bool
    {
        return false;
    }
}
