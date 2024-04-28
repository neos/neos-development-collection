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

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;

/**
 * The string-based visual node path, composed of node names and node aggregate IDs as fallback
 */
final readonly class VisualNodePath
{
    private function __construct(
        public string $value
    ) {
    }

    public static function fromAncestors(Node $leafNode, Nodes $ancestors): self
    {
        $pathSegments = [];
        foreach ($ancestors->reverse() as $ancestor) {
            $pathSegments[] = $ancestor->nodeName?->value ?: '[' . $ancestor->nodeAggregateId->value . ']';
        }

        return new self('/' . implode('/', $pathSegments));
    }
}
