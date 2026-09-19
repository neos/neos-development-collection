<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration\Command;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Execute a Content Repository migration (which is defined in a YAML file)
 */
final readonly class ExecuteMigration
{
    private function __construct(
        public MigrationConfiguration $migrationConfiguration,
        public WorkspaceName $sourceWorkspaceName,
        public WorkspaceName $targetWorkspaceName,
        public ContentStreamId $contentStreamId,
        public bool $publishOnSuccess,
        public bool $requireConfirmation,
    ) {
    }

    public static function create(
        MigrationConfiguration $migrationConfiguration,
        WorkspaceName $sourceWorkspaceName,
        WorkspaceName $targetWorkspaceName
    ): self {
        return new self(
            $migrationConfiguration,
            $sourceWorkspaceName,
            $targetWorkspaceName,
            ContentStreamId::create(),
            publishOnSuccess: true,
            requireConfirmation: true
        );
    }

    public function withoutPublishOnSuccess(): self
    {
        return new self(
            $this->migrationConfiguration,
            $this->sourceWorkspaceName,
            $this->targetWorkspaceName,
            $this->contentStreamId,
            false,
            $this->requireConfirmation,
        );
    }

    public function withoutRequiringConfirmation(): self
    {
        return new self(
            $this->migrationConfiguration,
            $this->sourceWorkspaceName,
            $this->targetWorkspaceName,
            $this->contentStreamId,
            $this->publishOnSuccess,
            false,
        );
    }

    public function withContentStreamId(ContentStreamId $contentStreamId): self
    {
        return new self(
            $this->migrationConfiguration,
            $this->sourceWorkspaceName,
            $this->targetWorkspaceName,
            $contentStreamId,
            $this->publishOnSuccess,
            $this->requireConfirmation,
        );
    }
}
