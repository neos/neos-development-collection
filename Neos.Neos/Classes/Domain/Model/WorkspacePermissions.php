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
        public bool $write,
        public bool $manage,
    ) {
    }

    public static function create(
        bool $read,
        bool $write,
        bool $manage,
    ): self {
        return new self($read, $write, $manage);
    }

    public static function all(): self
    {
        return new self(true, true, true);
    }

    public static function none(): self
    {
        return new self(false, false, false);
    }
}
