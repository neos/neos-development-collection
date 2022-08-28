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

namespace Neos\ContentRepository\Dimension;

use Neos\ContentRepository\Dimension\Exception\ContentDimensionValueSpecializationDepthIsInvalid;
use Neos\Flow\Annotations as Flow;

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
final class ContentDimensionValueSpecializationDepth implements \JsonSerializable, \Stringable
{
    /**
     * @throws ContentDimensionValueSpecializationDepthIsInvalid
     */
    public function __construct(
        public readonly int $depth
    ) {
        if ($depth < 0) {
            throw ContentDimensionValueSpecializationDepthIsInvalid::becauseItMustBeNonNegative($depth);
        }
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function isGreaterThan(ContentDimensionValueSpecializationDepth $otherDepth): bool
    {
        return $this->depth > $otherDepth->depth;
    }

    public function isZero(): bool
    {
        return $this->depth === 0;
    }

    public function increment(): self
    {
        return new self($this->depth + 1);
    }

    public function decreaseBy(ContentDimensionValueSpecializationDepth $other): self
    {
        return new self($this->depth - $other->depth);
    }

    public function jsonSerialize(): int
    {
        return $this->depth;
    }

    public function __toString(): string
    {
        return (string)$this->depth;
    }
}
