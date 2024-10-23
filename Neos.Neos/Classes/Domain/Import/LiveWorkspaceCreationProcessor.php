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

namespace Neos\Neos\Domain\Import;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\Severity;
use Neos\Neos\Domain\Model\WorkspaceDescription;
use Neos\Neos\Domain\Model\WorkspaceRole;
use Neos\Neos\Domain\Model\WorkspaceRoleAssignment;
use Neos\Neos\Domain\Model\WorkspaceTitle;
use Neos\Neos\Domain\Service\WorkspaceService;

/**
 * Import processor that creates the "live" workspace if it doesn't exist
 */
final readonly class LiveWorkspaceCreationProcessor implements ProcessorInterface
{
    public function __construct(
        private ContentRepository $contentRepository,
        private WorkspaceService $workspaceService,
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        $context->dispatch(Severity::NOTICE, 'Creating live workspace');
        $existingWorkspace = $this->contentRepository->findWorkspaceByName(WorkspaceName::forLive());
        if ($existingWorkspace !== null) {
            $context->dispatch(Severity::NOTICE, 'Workspace already exists, skipping');
            return;
        }
        $this->workspaceService->createRootWorkspace($this->contentRepository->id, WorkspaceName::forLive(), WorkspaceTitle::fromString('Live workspace'), WorkspaceDescription::fromString(''));
        $this->workspaceService->assignWorkspaceRole($this->contentRepository->id, WorkspaceName::forLive(), WorkspaceRoleAssignment::createForGroup('Neos.Neos:LivePublisher', WorkspaceRole::COLLABORATOR));
    }
}
