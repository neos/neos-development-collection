<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\Flow\Annotations as Flow;

/**
 * Description for a workspace
 *
 * @api
 */
#[Flow\Proxy(false)]
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

    public static function empty(): self
    {
        return new self('');
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
