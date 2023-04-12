<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Privilege;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamIds;

/**
 * @internal except for custom PrivilegeProviderInterface implementations
 */
final class ContentStreamPrivilege
{
    private function __construct(
        public readonly ?ContentStreamIds $allowedContentStreamIds,
        public readonly ?ContentStreamIds $disallowedContentStreamIds,
    ) {
    }

    public static function create(): self
    {
        return new self(null, null);
    }

    public function with(
        ContentStreamIds $allowedContentStreamIds = null,
        ContentStreamIds $disallowedContentStreamIds = null,
    ): self
    {
        return new self(
            $allowedContentStreamIds ?? $this->allowedContentStreamIds,
            $disallowedContentStreamIds ?? $this->disallowedContentStreamIds,
        );
    }
}
