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
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;

/**
 * "findByIdentifier" operation working on ContentRepository nodes. This operation allows for retrieval of nodes by identifier
 * from the current subgraph
 *
 * Example:
 *
 *  q(site).findByIdentifier('30e893c1-caef-0ca5-b53d-e5699bb8e506')
 */
class FindByIdentifierOperation extends AbstractOperation
{
    use CreateNodeHashTrait;

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'findByIdentifier';

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
        if (count($contextNodes) === 0 || empty($arguments[0])) {
            return;
        }

        $firstContextNode = reset($contextNodes);
        assert($firstContextNode instanceof Node);

        $nodeAggregateId = NodeAggregateId::fromString($arguments[0]);

        /** @var Node[] $result */
        $result = [];

        /** @var Node $contextNode */
        foreach ($flowQuery->getContext() as $contextNode) {
            $subgraph = $this->contentRepositoryRegistry->subgraphForNode($contextNode);
            $nodeByIdentifier = $subgraph->findNodeById($nodeAggregateId);
            if ($nodeByIdentifier) {
                $result[] = $nodeByIdentifier;
            }
        }

        $flowQuery->setContext($result);
    }
}
