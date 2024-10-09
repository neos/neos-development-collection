<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;

/**
 * Neos-specific metadata of a workspace
 *
 * @api
 */
#[Flow\Proxy(false)]
final readonly class WorkspaceMetadata
{
    public function __construct(
        public WorkspaceTitle $title,
        public WorkspaceDescription $description,
        public WorkspaceClassification $classification,
        public UserId|null $ownerUserId,
    ) {
        if ($this->classification === WorkspaceClassification::PERSONAL && $this->ownerUserId === null) {
            throw new \InvalidArgumentException('The owner-user-id must be set if the workspace is personal.', 1728476633);
        }
    }
}
