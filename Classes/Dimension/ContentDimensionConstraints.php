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

namespace Neos\ContentRepository\DimensionSpace\Dimension;

/**
 * Content dimension constraints across multiple dimensions
 */
final class ContentDimensionConstraints
{
    /**
     * if TRUE, the logic is "all allowed, except..." (blacklist approach).
     * if FALSE, the logic is "nothing allowed, except..." (whitelist approach).
     */
    public readonly bool $isWildcardAllowed;

    /**
     * An array of identifier restrictions, defined via value => bool, e.g.
     * [
     *      'foo' => true,
     *      'bar' => false
     * ]
     *
     * @var array<string,bool>
     */
    public readonly array $identifierRestrictions;

    public function __construct(
        bool $wildcardAllowed = true,
        array $identifierRestrictions = []
    ) {
        $this->isWildcardAllowed = $wildcardAllowed;
        $this->identifierRestrictions = $identifierRestrictions;
    }

    /**
     * @return array<string,bool>
     */
    public function getIdentifierRestrictions(): array
    {
        return $this->identifierRestrictions;
    }

    public function allowsCombinationWith(ContentDimensionValue $dimensionValue): bool
    {
        return $this->identifierRestrictions[(string)$dimensionValue] ?? $this->isWildcardAllowed;
    }
}
