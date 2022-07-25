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

use Neos\ContentRepository\DimensionSpace\Dimension\Exception\ContentDimensionIdentifierIsInvalid;

/**
 * The content dimension identifier value object
 */
final class ContentDimensionIdentifier implements \JsonSerializable, \Stringable
{
    /**
     * @throws ContentDimensionIdentifierIsInvalid
     */
    public function __construct(
        public readonly string $identifier
    ) {
        if (empty($identifier)) {
            throw ContentDimensionIdentifierIsInvalid::becauseItMustNotBeEmpty();
        }
    }

    public function equals(ContentDimensionIdentifier $other): bool
    {
        return $this->identifier === $other->identifier;
    }

    public function jsonSerialize(): string
    {
        return $this->identifier;
    }

    public function __toString(): string
    {
        return $this->identifier;
    }
}
