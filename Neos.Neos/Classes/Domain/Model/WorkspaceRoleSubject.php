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
final readonly class WorkspaceRoleSubject
{
    private function __construct(
        public WorkspaceRoleSubjectType $type,
        public string $value,
    ) {
        if (preg_match('/^[\p{L}\p{P}\d .]{1,200}$/u', $this->value) !== 1) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid workspace role subject.', $value), 1728384932);
        }
    }

    public static function createForUser(UserId $userId): self
    {
        return new self(
            WorkspaceRoleSubjectType::USER,
            $userId->value,
        );
    }

    public static function createForGroup(string $flowRoleIdentifier): self
    {
        return new self(
            WorkspaceRoleSubjectType::GROUP,
            $flowRoleIdentifier,
        );
    }

    public static function create(
        WorkspaceRoleSubjectType $type,
        string $value,
    ): self {
        return new self($type, $value);
    }

    public function equals(self $other): bool
    {
        return $this->type === $other->type && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return "{$this->type->value}: {$this->value}";
    }
}
