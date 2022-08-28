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

/**
 * Content dimension constraints across multiple dimensions
 * @internal
 */
final class ContentDimensionConstraints
{
    public function __construct(
        /**
         * if TRUE, the logic is "all allowed, except..." (blacklist approach).
         * if FALSE, the logic is "nothing allowed, except..." (whitelist approach).
         */
        public readonly bool $isWildcardAllowed = true,
        /**
         * An array of identifier restrictions, defined via value => bool, e.g.
         * [
         *      'foo' => true,
         *      'bar' => false
         * ]
         *
         * @var array<string,bool>
         */
        public readonly array $identifierRestrictions = []
    ) {
    }

    public function allowsCombinationWith(ContentDimensionValue $dimensionValue): bool
    {
        return $this->identifierRestrictions[(string)$dimensionValue] ?? $this->isWildcardAllowed;
    }
}
