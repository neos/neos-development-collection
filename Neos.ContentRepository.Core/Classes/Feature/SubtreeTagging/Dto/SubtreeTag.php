<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto;

/**
 * A tag that can be added to Node aggregates that is inherited by all its descendants
 *
 * @api
 */
final readonly class SubtreeTag implements \JsonSerializable
{

    private function __construct(public string $value)
    {
        $regexPattern = '/^[a-z0-9_.]{1,30}$/';
        if (preg_match($regexPattern, $value) !== 1) {
            throw new \InvalidArgumentException(sprintf('The SubtreeTag value must adhere to the regular expression "%s"', $regexPattern), 1695467813);
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
