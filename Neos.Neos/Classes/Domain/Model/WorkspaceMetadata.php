<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;

/**
 * @api
 */
#[Flow\Proxy(false)]
final readonly class WorkspaceMetadata
{
    public function __construct(
        public WorkspaceName $workspaceName,
        public WorkspaceTitle $title,
        public WorkspaceDescription $description,
        public WorkspaceClassification $classification,
    ) {
    }

    /**
     * Note: To be used with named arguments!
     */
    public function with(
        WorkspaceTitle $title = null,
        WorkspaceDescription $description = null,
    ): self {
        return new self(
            $this->workspaceName,
            $title ?? $this->title,
            $description ?? $this->description,
            $this->classification
        );
    }
}
