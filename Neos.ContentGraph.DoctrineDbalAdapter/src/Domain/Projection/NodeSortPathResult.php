<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

/**
 * @internal
 */
final class NodeSortPathResult
{
    private function __construct(
        public NodeSortPath $nodeSortPath,
        public bool $rebalanced,
    ) {
    }

    public static function create(NodeSortPath $nodeSortPath, bool $rebalanced): self
    {
        return new self($nodeSortPath, $rebalanced);
    }
}
