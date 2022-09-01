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

use Neos\ContentRepository\Core\Dimension\Exception\ContentDimensionIdIsInvalid;

/**
 * The content dimension id value object
 *
 * @api
 */
final class ContentDimensionId implements \JsonSerializable, \Stringable
{
    /**
     * @throws ContentDimensionIdIsInvalid
     */
    public function __construct(
        public readonly string $id
    ) {
        if (empty($id)) {
            throw ContentDimensionIdIsInvalid::becauseItMustNotBeEmpty();
        }
    }

    public function equals(ContentDimensionId $other): bool
    {
        return $this->id === $other->id;
    }

    public function jsonSerialize(): string
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
