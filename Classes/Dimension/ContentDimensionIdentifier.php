<?php

namespace Neos\ContentRepository\DimensionSpace\Dimension;

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * The content dimension identifier value object
 */
final class ContentDimensionIdentifier implements \JsonSerializable
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @param string $identifier
     * @throws Exception\ContentDimensionIdentifierIsInvalid
     */
    public function __construct(string $identifier)
    {
        if (empty($identifier)) {
            throw new Exception\ContentDimensionIdentifierIsInvalid('Content dimension identifiers must not be empty.', 1515166615);
        }
        $this->identifier = $identifier;
    }

    /**
     * @param ContentDimensionIdentifier $otherContentDimensionIdentifier
     * @return bool
     */
    public function equals(ContentDimensionIdentifier $otherContentDimensionIdentifier): bool
    {
        return $this->__toString() === $otherContentDimensionIdentifier->__toString();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->identifier;
    }
}
