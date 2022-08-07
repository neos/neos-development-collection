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

namespace Neos\ContentRepository\Feature\Common;

use Neos\Flow\Annotations as Flow;

/**
 * The recursion mode to be used in recursive operations.
 * Determines which descendants will be affected by an operation.
 */
#[Flow\Proxy(false)]
enum RecursionMode: string implements \JsonSerializable
{
    /**
     * The "all descendants" mode,
     * meaning that all descendants will be affected by the operation
     */
    case MODE_ALL_DESCENDANTS = 'allDescendants';

    /**
     * The "only tethered descendants" mode,
     * meaning that only the tethered descendants will be affected by the operation
     */
    case MODE_ONLY_TETHERED_DESCENDANTS = 'onlyTetheredDescendants';

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
