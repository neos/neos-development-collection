<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\Flow\Annotations as Flow;

/**
 * The identifier of a workspace role subject that identifiers a group of users or a single user {@see WorkspaceRoleSubjectType}
 *
 * @api
 */
#[Flow\Proxy(false)]
final readonly class WorkspaceRoleSubject implements \JsonSerializable
{
    public function __construct(
        public string $value
    ) {
        if (preg_match('/^[\p{L}\p{P}\d .]{1,200}$/u', $this->value) !== 1) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid workspace role subject.', $value), 1728384932);
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
