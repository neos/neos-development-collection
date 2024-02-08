<?php

declare(strict_types=1);

namespace Neos\Workspace\Ui\Aspect;

use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Ui\ContentRepository\Service\WorkspaceService;
use Neos\Utility\Exception\PropertyNotAccessibleException;
use Neos\Utility\ObjectAccess;

#[Flow\Aspect]
class SharedWorkspaceAccessAspect
{
    /**
     * Adjust workspace permission check for shared workspaces in the Neos UI
     * TODO: Move this code into the Neos.Neos.Ui package when the workspace ACL is reimplemented
     *
     * @Flow\Around("method(Neos\Neos\Ui\ContentRepository\Service\WorkspaceService->getAllowedTargetWorkspaces())")
     * @return Workspace[]
     * @throws PropertyNotAccessibleException
     */
    public function getAllowedTargetWorkspacesIncludingSharedOnes(JoinPointInterface $joinPoint): array
    {
        /** @var WorkspaceService $workspaceService */
        $workspaceService = $joinPoint->getProxy();
        /** @var UserService $userService */
        $userService = ObjectAccess::getProperty($workspaceService, 'domainUserService', true);
        $contentRepository = $joinPoint->getMethodArgument('contentRepository');
        $user = $userService->getCurrentUser();
//        $sharedWorkspaceNames = $user ? $this->workspaceDetailsRepository->findAllowedWorkspaceNamesForUser($user) : [];
        $sharedWorkspaceNames = [];

        $workspacesArray = [];
        /** @var Workspace $workspace */
        foreach ($contentRepository->getWorkspaceFinder()->findAll() as $workspace) {
            // Skip personal workspaces and private workspace not shared with the current user
            if (!in_array($workspace->workspaceName->value, $sharedWorkspaceNames)
                && (
                    ($workspace->workspaceOwner !== null && $workspace->workspaceOwner !== $user)
                    || $workspace->isPersonalWorkspace()
                )
            ) {
                continue;
            }

            $workspaceArray = [
                'name' => $workspace->workspaceName->value,
                'title' => $workspace->workspaceTitle?->value,
                'description' => $workspace->workspaceDescription->value,
                'readonly' => !$userService->currentUserCanPublishToWorkspace($workspace)
            ];
            $workspacesArray[$workspace->workspaceName->value] = $workspaceArray;
        }

        return $workspacesArray;
    }
}
