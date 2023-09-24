<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeAttributes\Dto;

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
 * @api
 */
final readonly class Attribute implements \JsonSerializable
{
    private function __construct(
        public string $value
    ) {
        if (preg_match('/^[a-z0-9_\.]{1,30}$/', $value) !== 1) {
            throw new \InvalidArgumentException('Invalid attribute given.', 1695467813);
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
