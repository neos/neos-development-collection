<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;

/**
 * A reference to a given node by a name and with optional reference properties
 *
 * {@see References}
 *
 * @api
 */
final class Reference
{
    public function __construct(
        public readonly Node $node,
        public readonly ReferenceName $name,
        public readonly ?PropertyCollection $properties
    ) {
    }
}
