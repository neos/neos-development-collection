<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Security\Authorization\ContentRepositoryAuthorizationService;

/**
 * Evaluated permissions a specific user has on a workspace, usually evaluated by the {@see ContentRepositoryAuthorizationService}
 *
 * - read: Permission to read data from the corresponding workspace (e.g. get hold of and traverse the content graph)
 * - write: Permission to write to the corresponding workspace, including publishing a derived workspace to it
 * - manage: Permission to change the metadata and roles of the corresponding workspace (e.g. change description/title or add/remove workspace roles)
 *
 * @api
 */
#[Flow\Proxy(false)]
final readonly class WorkspacePermissions
{
    /**
     * @param bool $read Permission to read data from the corresponding workspace (e.g. get hold of and traverse the content graph)
     * @param bool $write Permission to write to the corresponding workspace, including publishing a derived workspace to it
     * @param bool $manage Permission to change the metadata and roles of the corresponding workspace (e.g. change description/title or add/remove workspace roles)
     * @param string $reason Human-readable explanation for why this permission was evaluated {@see getReason()}
     */
    private function __construct(
        public bool $read,
        public bool $write,
        public bool $manage,
        private string $reason,
    ) {
    }

    /**
     * @param bool $read Permission to read data from the corresponding workspace (e.g. get hold of and traverse the content graph)
     * @param bool $write Permission to write to the corresponding workspace, including publishing a derived workspace to it
     * @param bool $manage Permission to change the metadata and roles of the corresponding workspace (e.g. change description/title or add/remove workspace roles)
     * @param string $reason Human-readable explanation for why this permission was evaluated {@see getReason()}
     */
    public static function create(
        bool $read,
        bool $write,
        bool $manage,
        string $reason,
    ): self {
        return new self($read, $write, $manage, $reason);
    }

    public static function all(string $reason): self
    {
        return new self(true, true, true, $reason);
    }

    public static function manage(string $reason): self
    {
        return new self(false, false, true, $reason);
    }

    public static function none(string $reason): self
    {
        return new self(false, false, false, $reason);
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
