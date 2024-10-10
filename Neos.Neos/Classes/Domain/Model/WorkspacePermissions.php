<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\Flow\Annotations as Flow;

/**
 * Calculated permissions a specific user has on a workspace
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
     */
    public static function create(
        bool $read,
        bool $write,
        bool $manage,
    ): self {
        return new self($read, $write, $manage);
    }

    /**
     * @param bool $read Permission to read data from the corresponding workspace (e.g. get hold of and traverse the content graph)
     * @param bool $write Permission to write to the corresponding workspace, including publishing a derived workspace to it
     * @param bool $manage Permission to change the metadata and roles of the corresponding workspace (e.g. change description/title or add/remove workspace roles)
     */
    private function __construct(
        public bool $read,
        public bool $write,
        public bool $manage,
    ) {
    }

    public static function all(): self
    {
        return new self(true, true, true);
    }
}
