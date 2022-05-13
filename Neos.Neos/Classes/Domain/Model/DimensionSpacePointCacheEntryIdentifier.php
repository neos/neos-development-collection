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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;

/**
 * The cache entry identifier data transfer object for dimension space points
 */
final class DimensionSpacePointCacheEntryIdentifier implements CacheAwareInterface
{
    private function __construct(
        public readonly DimensionSpacePoint $dimensionSpacePoint
    ) {
    }

    public static function fromDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        return new self($dimensionSpacePoint);
    }

    public function getCacheEntryIdentifier(): string
    {
        return $this->dimensionSpacePoint->hash;
    }
}
