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

namespace Neos\ContentRepository\SharedModel\User;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Algorithms;

#[Flow\Proxy(false)]
final class UserIdentifier implements \JsonSerializable, \Stringable
{
    const SYSTEM_USER_IDENTIFIER = 'system';

    /**
     * @var array<string,self>
     */
    private static array $instances = [];

    private function __construct(
        public readonly string $value
    ) {
    }

    private static function instance(string $value): self
    {
        return self::$instances[$value] ??= new self($value);
    }

    public static function create(): self
    {
        return self::instance(Algorithms::generateUUID());
    }

    /**
     * Creates a special user identifier which refers to the virtual "system" user.
     */
    public static function forSystemUser(): self
    {
        return self::instance(self::SYSTEM_USER_IDENTIFIER);
    }

    public static function fromString(string $value): self
    {
        return self::instance($value);
    }

    public function isSystemUser(): bool
    {
        return $this->value === self::SYSTEM_USER_IDENTIFIER;
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
