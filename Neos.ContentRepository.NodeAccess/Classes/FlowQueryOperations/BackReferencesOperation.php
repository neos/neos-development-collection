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

use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\OperationInterface;
use Neos\Flow\Annotations as Flow;

/**
 * "backReferences" operation working on Nodes
 *
 * This operation can be used to find incoming references of a given node:
 *
 *     ${q(node).backReferences().get()}
 *
 * The result is an array of {@see Reference} instances.
 *
 * To render the reference name of the first match:
 *
 *     $q{node).backReferences().get(0).name.value}
 *
 * The {@see ReferencePropertyOperation} can be used to access any property on the reference relation:
 *
 *     ${q(node).backReferences("someReferenceName").property("somePropertyName")}
 *
 * @see ReferencesOperation
 * @api To be used in Fusion, for PHP code {@see ContentSubgraphInterface::findBackReferences()} should be used instead
 */
final class BackReferencesOperation implements OperationInterface
{

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    public function canEvaluate($context): bool
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof Node));
    }

    public function evaluate(FlowQuery $flowQuery, array $arguments): void
    {
        $output = [];
        $filter = FindBackReferencesFilter::create();
        if (isset($arguments[0])) {
            $filter = $filter->with(referenceName: $arguments[0]);
        }
        /** @var Node $contextNode */
        foreach ($flowQuery->getContext() as $contextNode) {
            $subgraph = $this->contentRepositoryRegistry->subgraphForNode($contextNode);
            $output[] = iterator_to_array($subgraph->findBackReferences($contextNode->nodeAggregateId, $filter));
        }
        $flowQuery->setContext(array_merge(...$output));
    }

    public static function getShortName(): string
    {
        return 'backReferences';
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
