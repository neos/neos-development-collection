<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\Flow\Annotations as Flow;

/**
 * Human-Readable title of a workspace
 *
 * @api
 */
#[Flow\Proxy(false)]
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
}
