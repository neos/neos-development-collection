<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Privilege;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * @api
 */
final class Privileges
{
    private function __construct(
        public readonly ?ContentStreamPrivilege $contentStreamPrivilege,

    ) {
    }

    public static function create(): self
    {
        return new self(null);
    }

    public function with(
        ContentStreamPrivilege $contentStreamPrivilege = null,
    ): self
    {
        return new self(
            $contentStreamPrivilege ?? $this->contentStreamPrivilege,
        );
    }

    public function isContentStreamAllowed(ContentStreamId $contentStreamId): bool
    {
        if ($this->contentStreamPrivilege === null) {
            return true;
        }
        return $this->contentStreamPrivilege->allowedContentStreamIds->contain($contentStreamId);
    }
}
