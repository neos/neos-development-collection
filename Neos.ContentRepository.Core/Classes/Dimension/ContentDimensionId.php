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
final readonly class ContentDimensionId implements \JsonSerializable
{
    /**
     * @throws ContentDimensionIdIsInvalid
     */
    public function __construct(
        public string $value
    ) {
        if (empty($value)) {
            throw ContentDimensionIdIsInvalid::becauseItMustNotBeEmpty();
        }
    }

    public function equals(ContentDimensionId $other): bool
    {
        return $this->value === $other->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
