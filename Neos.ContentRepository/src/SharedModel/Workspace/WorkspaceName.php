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

namespace Neos\ContentRepository\SharedModel\Workspace;

use Neos\Cache\CacheAwareInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Name of a workspace.
 *
 * Implements CacheAwareInterface because of Fusion Runtime caching and Routing
 */
#[Flow\Proxy(false)]
final class WorkspaceName implements \JsonSerializable, CacheAwareInterface, \Stringable
{
    const WORKSPACE_NAME_LIVE = 'live';

    /**
     * @var array<string,self>
     */
    private static array $instances = [];

    private function __construct(
        public readonly string $name
    ) {
        if (preg_match('/^[\p{L}\p{P}\d \.]{1,200}$/u', $name) !== 1) {
            throw new \InvalidArgumentException('Invalid workspace name given.', 1505826610318);
        }
    }

    private static function instance(string $name): self
    {
        return self::$instances[$name] ??= new self($name);
    }

    public static function fromString(string $value): self
    {
        return self::instance($value);
    }

    public static function forLive(): self
    {
        return self::instance(self::WORKSPACE_NAME_LIVE);
    }

    public function isLive(): bool
    {
        return $this->name === self::WORKSPACE_NAME_LIVE;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function jsonSerialize(): string
    {
        return $this->name;
    }

    public function getCacheEntryIdentifier(): string
    {
        return $this->name;
    }
}
