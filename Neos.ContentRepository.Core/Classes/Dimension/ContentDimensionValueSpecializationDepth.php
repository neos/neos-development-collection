<?php

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Dimension;

use Neos\ContentRepository\Core\Dimension\Exception\ContentDimensionValueSpecializationDepthIsInvalid;

/**
 * The value object describing the specialization depth between two content dimension values
 *
 *   0 --> A ◀─┐
 *   1 --> │   │
 *         B   │ <-- 2
 *   1 --> │   │
 *         C ──┘
 *
 * @internal
 */
final readonly class ContentDimensionValueSpecializationDepth implements \JsonSerializable
{
    /**
     * @throws ContentDimensionValueSpecializationDepthIsInvalid
     */
    public function __construct(
        public int $value
    ) {
        if ($value < 0) {
            throw ContentDimensionValueSpecializationDepthIsInvalid::becauseItMustBeNonNegative($value);
        }
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function isGreaterThan(ContentDimensionValueSpecializationDepth $otherDepth): bool
    {
        return $this->value > $otherDepth->value;
    }

    public function isZero(): bool
    {
        return $this->value === 0;
    }

    public function increment(): self
    {
        return new self($this->value + 1);
    }

    public function decreaseBy(ContentDimensionValueSpecializationDepth $other): self
    {
        return new self($this->value - $other->value);
    }

    public function jsonSerialize(): int
    {
        return $this->value;
    }
}
