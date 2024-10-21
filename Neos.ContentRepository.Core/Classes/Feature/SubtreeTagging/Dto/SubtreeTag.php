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
final class SubtreeTag implements \JsonSerializable
{
    /**
     * @var array<string,self>
     */
    private static array $instances = [];

    private static function instance(string $value): self
    {
        if (!array_key_exists($value, self::$instances)) {
            self::$instances[$value] = new self($value);
        }
        return self::$instances[$value];
    }

    private function __construct(public string $value)
    {
        $regexPattern = '/^[a-z0-9_.-]{1,36}$/';
        if (preg_match($regexPattern, $value) !== 1) {
            throw new \InvalidArgumentException(sprintf('The SubtreeTag value "%s" does not adhere to the regular expression "%s"', $value, $regexPattern), 1695467813);
        }
    }

    public static function fromString(string $value): self
    {
        return self::instance($value);
    }

    public static function disabled(): self
    {
        return self::instance('disabled');
    }

    public function equals(self $other): bool
    {
        return $this === $other;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
