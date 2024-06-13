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
use Neos\Eel\FlowQuery\OperationInterface;

/**
 * "unique" operation working on Nodes
 *
 * This operation can be used to ensure that nodes are only once in the flow query context
 *
 *     ${q(node).backReferences().nodes().unique()get()}
 *
 * The result is an array of {@see Node} instances.
 *
 * !!! This is a Node specific implementation of the generic `unique` operation!!!
 *
 * @api To be used in Fusion, in php this should be implemented directly
 */
final class UniqueOperation implements OperationInterface
{
    use CreateNodeHashTrait;

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'unique';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 100;

    /** @param array<int, mixed> $context */
    public function canEvaluate($context): bool
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof Node));
    }

    /** @param array<int, mixed> $arguments */
    public function evaluate(FlowQuery $flowQuery, array $arguments): void
    {
        $nodesByHash = [];
        /** @var Node $node */
        foreach ($flowQuery->getContext() as $node) {
            $hash = $this->createNodeHash($node);
            if (!array_key_exists($hash, $nodesByHash)) {
                $nodesByHash[$hash] = $node;
            }
        }
        $flowQuery->setContext(array_values($nodesByHash));
    }

    public static function getShortName(): string
    {
        return 'unique';
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
