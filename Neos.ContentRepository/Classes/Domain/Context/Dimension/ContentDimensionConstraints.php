<?php
namespace Neos\ContentRepository\Domain\Context\Dimension;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * The content dimension constraints model
 */
final class ContentDimensionConstraints
{
    /**
     * @var bool
     */
    protected $wildcardAllowed;

    /**
     * An array of identifier restrictions, defined via value => bool, e.g.
     * [
     *      'foo' => true,
     *      'bar' => false
     * ]
     *
     * @var array|bool[]
     */
    protected $identifierRestrictions;


    /**
     * @param bool $wildcardAllowed
     * @param array $identifierRestrictions
     */
    public function __construct(bool $wildcardAllowed = true, array $identifierRestrictions = [])
    {
        $this->wildcardAllowed = $wildcardAllowed;
        $this->identifierRestrictions = $identifierRestrictions;
    }

    /**
     * @return bool
     */
    public function isWildcardAllowed(): bool
    {
        return $this->wildcardAllowed;
    }

    /**
     * @return array|bool[]
     */
    public function getIdentifierRestrictions(): array
    {
        return $this->identifierRestrictions;
    }


    /**
     * @param ContentDimensionValue $dimensionValue
     * @return bool
     */
    public function allowsCombinationWith(ContentDimensionValue $dimensionValue): bool
    {
        return $this->identifierRestrictions[(string)$dimensionValue] ?? $this->wildcardAllowed;
    }
}
