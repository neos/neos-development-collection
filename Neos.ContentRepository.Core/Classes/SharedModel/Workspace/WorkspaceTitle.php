<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Workspace;

/**
 * Human-Readable title of a workspace
 *
 * @deprecated with 9.0.0-beta14 metadata should be assigned to workspaces outside the Content Repository core
 * @internal
 */
final readonly class WorkspaceTitle implements \JsonSerializable
{
    public function __construct(
        public string $value
    ) {
        if (preg_match('/^[\p{L}\p{P}\d \.]{1,200}$/u', $this->value) !== 1) {
            throw new \InvalidArgumentException('Invalid workspace title given.', 1505827170288);
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

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
