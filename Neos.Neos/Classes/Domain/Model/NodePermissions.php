<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Security\Authorization\ContentRepositoryAuthorizationService;

/**
 * Evaluated permissions a specific user has on a node, usually evaluated by the {@see ContentRepositoryAuthorizationService}
 *
 * - read: Permission to read the node and its properties and references
 * - edit: Permission to change the node
 *
 * @api because it is returned by the {@see ContentRepositoryAuthorizationService}
 */
#[Flow\Proxy(false)]
final readonly class NodePermissions
{
    /**
     * @param bool $read Permission to read data from the corresponding node
     * @param bool $edit Permission to edit the corresponding node
     */
    private function __construct(
        public bool $read,
        public bool $edit,
        private string $reason,
    ) {
    }

    /**
     * @param bool $read Permission to read data from the corresponding node
     * @param bool $edit Permission to edit the corresponding node
     * @param string $reason Human-readable explanation for why this permission was evaluated {@see getReason()}
     */
    public static function create(
        bool $read,
        bool $edit,
        string $reason,
    ): self {
        return new self($read, $edit, $reason);
    }

    public static function all(string $reason): self
    {
        return new self(true, true, $reason);
    }

    public static function none(string $reason): self
    {
        return new self(false, false, $reason);
    }

    /**
     * Human-readable explanation for why this permission was evaluated
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
