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

namespace Neos\ContentRepository\Core\SharedModel\Workspace;

use Neos\Flow\Utility\Algorithms;

/**
 * The ContentStreamId is the identifier for a Content Stream, which is
 * a central concept in the Event-Sourced CR introduced with Neos 5.0.
 *
 * @api
 */
final class ContentStreamId implements \JsonSerializable, \Stringable
{
    /**
     * @var array<string,self>
     */
    private static array $instances = [];

    private function __construct(
        public string $value
    ) {
    }

    private static function instance(string $value): self
    {
        return self::$instances[$value] ??= new self($value);
    }

    public static function fromString(string $value): self
    {
        return self::instance($value);
    }

    public static function create(): self
    {
        return self::instance(Algorithms::generateUUID());
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this === $other;
    }
}
