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

namespace Neos\ContentRepository\SharedModel\Node;

use Neos\Flow\Annotations as Flow;

/**
 * The classification of a node aggregate
 */
#[Flow\Proxy(false)]
enum NodeAggregateClassification: string implements \JsonSerializable
{
    /**
     * Denotes a regular node aggregate
     */
    case CLASSIFICATION_REGULAR = 'regular';

    /**
     * Denotes a root node aggregate which
     * * does not have parents
     * * always originates in the empty dimension space point
     * * cannot be varied
     */
    case CLASSIFICATION_ROOT = 'root';

    /**
     * Denotes a tethered node aggregate which
     * * is created and removed alongside a regular parent
     * * cannot be directly structurally changed
     */
    case CLASSIFICATION_TETHERED = 'tethered';

    public function isRoot(): bool
    {
        return $this === self::CLASSIFICATION_ROOT;
    }

    public function isRegular(): bool
    {
        return $this === self::CLASSIFICATION_REGULAR;
    }

    public function isTethered(): bool
    {
        return $this === self::CLASSIFICATION_TETHERED;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this === $other;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
