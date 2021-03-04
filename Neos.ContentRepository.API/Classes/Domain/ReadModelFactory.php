<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Api\Domain;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Api\Domain\NodeBasedReadModelInterface;
use Neos\ContentRepository\Api\Domain\NodeBasedReadModels;
use Neos\ContentRepository\Api\Domain\NodeImplementationClassName;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class ReadModelFactory
{
    public static function createReadModel(NodeInterface $node, ContentSubgraphInterface $subgraph): NodeBasedReadModelInterface
    {
        $implementationClassName = NodeImplementationClassName::forNodeType($node->getNodeType());

        return new $implementationClassName(
            $node,
            $subgraph
        );
    }

    public static function createReadModels(array $nodes, ContentSubgraphInterface $subgraph): NodeBasedReadModels
    {
        $readModels = [];
        foreach ($nodes as $node) {
            $readModels = self::createReadModel($node, $subgraph);
        }

        return NodeBasedReadModels::fromArray($readModels);
    }
}
