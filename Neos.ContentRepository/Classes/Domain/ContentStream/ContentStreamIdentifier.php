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

namespace Neos\ContentRepository\Domain\ContentStream;

use Neos\Cache\CacheAwareInterface;
use Neos\Flow\Utility\Algorithms;
use Neos\Flow\Annotations as Flow;

/**
 * The ContentStreamIdentifier is the identifier for a Content Stream, which is
 * a central concept in the Event-Sourced CR introduced with Neos 5.0.
 */
#[Flow\Proxy(false)]
final class ContentStreamIdentifier implements \JsonSerializable, CacheAwareInterface
{
    /**
     * @var array<string,self>
     */
    private static array $instances;

    private function __construct(
        private string $value
    ) {}

    public static function instance(string $value): self
    {
        if (!isset(self::$instances[$value])) {
            self::$instances[$value] = new self($value);
        }

        return self::$instances[$value];
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

    public function getCacheEntryIdentifier(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(ContentStreamIdentifier $other): bool
    {
        return $this === $other;
    }
}
