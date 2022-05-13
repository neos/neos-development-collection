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

namespace Neos\Neos\Domain\Model;

use Neos\Cache\CacheAwareInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\AbstractDimensionSpacePoint;

/**
 * The cache entry identifier data transfer object for dimension space points
 */
final class DimensionSpacePointCacheEntryIdentifier implements CacheAwareInterface
{
    private function __construct(
        private readonly string $value
    ) {
    }

    public static function fromDimensionSpacePoint(AbstractDimensionSpacePoint $dimensionSpacePoint): self
    {
        return new self($dimensionSpacePoint->hash);
    }

    public function getCacheEntryIdentifier(): string
    {
        return $this->value;
    }
}
