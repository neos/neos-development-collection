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
 * Description for a workspace
 *
 * @deprecated with 9.0.0-beta14 metadata should be assigned to workspaces outside the Content Repository core
 */
final readonly class WorkspaceDescription implements \JsonSerializable
{
    public function __construct(
        public string $value,
    ) {
        if (preg_match('/^[\p{L}\p{P}\d \.]{0,500}$/u', $this->value) !== 1) {
            throw new \InvalidArgumentException('Invalid workspace description given.', 1505831660363);
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
