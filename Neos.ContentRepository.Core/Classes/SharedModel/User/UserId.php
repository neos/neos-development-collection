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

namespace Neos\ContentRepository\Core\SharedModel\User;

use Neos\ContentRepository\Core\SharedModel\Id\UuidFactory;

/**
 * @api
 */
final class UserId implements \JsonSerializable
{
    private const SYSTEM_USER_ID = 'system';

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
        return new self(UuidFactory::create());
    }

    /**
     * Creates a special user ID which refers to the virtual "system" user.
     */
    public static function forSystemUser(): self
    {
        return self::instance(self::SYSTEM_USER_ID);
    }

    public static function fromString(string $value): self
    {
        return self::instance($value);
    }

    public function isSystemUser(): bool
    {
        return $this->value === self::SYSTEM_USER_ID;
    }

    public function equals(UserId $other): bool
    {
        return $other->value === $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
