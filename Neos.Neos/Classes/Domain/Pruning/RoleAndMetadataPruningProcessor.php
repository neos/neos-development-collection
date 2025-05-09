<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\Pruning;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\Neos\Domain\Repository\WorkspaceMetadataAndRoleRepository;

/**
 * Pruning processor that removes role and metadata for a specified content repository
 */
final readonly class RoleAndMetadataPruningProcessor implements ProcessorInterface
{
    public function __construct(
        private ContentRepositoryId $contentRepositoryId,
        private WorkspaceMetadataAndRoleRepository $workspaceMetadataAndRoleRepository,
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        $this->workspaceMetadataAndRoleRepository->pruneRoleAssignments($this->contentRepositoryId);
        $this->workspaceMetadataAndRoleRepository->pruneWorkspaceMetadata($this->contentRepositoryId);
    }
}
