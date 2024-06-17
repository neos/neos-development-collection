<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\Flow\Annotations as Flow;

/**
 * @api
 */
#[Flow\Proxy(false)]
final readonly class WorkspacePermissions
{
    private function __construct(
        public bool $read,
        public bool $publish,
        public bool $manage,
    ) {
    }

    public static function create(
        bool $read,
        bool $publish,
        bool $manage,
    ): self {
        return new self($read, $publish, $manage);
    }
}
