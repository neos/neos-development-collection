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
final readonly class VisualIntraDimensionalNode
{
    public int $textX;

    public int $textY;

    public string $color;

    public function __construct(
        public int $id,
        public string $name,
        public int $parent,
        public int $x,
        public int $y
    ) {
        $this->textX = $x - 40;
        $this->textY = $y - 5 + 50; // 50 for padding
        $this->color = '#3F3F3F';
    }
}
