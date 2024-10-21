<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Presentation;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\Neos\Domain\NodeLabel\NodeLabelGeneratorInterface;

/**
 * The string-based visual node path
 *
 * @internal helper for enriched debug information
 */
final readonly class VisualNodePath
{
    private function __construct(
        public string $value
    ) {
    }

    public static function buildFromNodes(Nodes $nodes, NodeLabelGeneratorInterface $nodeLabelGenerator): self
    {
        $pathSegments = [];
        foreach ($nodes as $node) {
            $pathSegments[] = $nodeLabelGenerator->getLabel($node);
        }
        return new self('/' . implode('/', $pathSegments));
    }

    public static function buildFromAncestors(Node $startingNode, ContentRepository $contentRepository, NodeLabelGeneratorInterface $nodeLabelGenerator): self
    {
        $nodes = $contentRepository->getContentGraph($startingNode->workspaceName)
            ->getSubgraph($startingNode->dimensionSpacePoint, $startingNode->visibilityConstraints)
            ->findAncestorNodes(
                $startingNode->aggregateId,
                FindAncestorNodesFilter::create()
            )->reverse()->append($startingNode);
        return self::buildFromNodes($nodes, $nodeLabelGenerator);
    }
}
