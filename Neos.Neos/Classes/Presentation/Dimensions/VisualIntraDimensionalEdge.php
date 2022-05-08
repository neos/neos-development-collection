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

namespace Neos\Neos\Presentation\Dimensions;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final class VisualIntraDimensionalEdge
{
    private const VERTICAL_OFFSET = 40;

    public function __construct(
        public readonly int $x1,
        public readonly int $y1,
        public readonly int $x2,
        public readonly int $y2
    ) {
    }

    public static function forNodes(VisualIntraDimensionalNode $startNode, VisualIntraDimensionalNode $endNode): self
    {
        return new self(
            $startNode->x,
            $startNode->y - self::VERTICAL_OFFSET,
            $endNode->x,
            $endNode->y + self::VERTICAL_OFFSET
        );
    }
}
