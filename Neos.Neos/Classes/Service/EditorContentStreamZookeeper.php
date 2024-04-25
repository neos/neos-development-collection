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

namespace Neos\Neos\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Neos\Flow\Security\Authentication;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Policy\Role;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Workspace\WorkspaceProvider;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionFailedException;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Party\Domain\Service\PartyService;

/**
 * The service for keeping track of editors' workspaces
 *
 * On authentication, workspaces may have to be created based on live
 */
#[Flow\Scope('singleton')]
final class EditorContentStreamZookeeper
{
    public function __construct(
        private readonly PartyService $partyService,
        private readonly Bootstrap $bootstrap,
        private readonly WorkspaceProvider $workspaceProvider
    ) {
    }

    /**
     * This method is called whenever a login happens (AuthenticationProviderManager::class, 'authenticatedToken'),
     * using Signal/Slot
     *
     * @param TokenInterface $token
     */
    public function relayEditorAuthentication(Authentication\TokenInterface $token): void
    {
        $requestHandler = $this->bootstrap->getActiveRequestHandler();
        if (!$requestHandler instanceof HttpRequestHandlerInterface) {
            // we might be in testing context
            return;
        }
        try {
            $siteDetectionResult = SiteDetectionResult::fromRequest($requestHandler->getHttpRequest());
        } catch (SiteDetectionFailedException) {
            return;
        }

        $isEditor = false;
        foreach ($token->getAccount()->getRoles() as $role) {
            /** @var Role $role */
            if (isset($role->getAllParentRoles()['Neos.Neos:AbstractEditor'])) {
                $isEditor = true;
                break;
            }
        }
        if (!$isEditor) {
            return;
        }
        $user = $this->partyService->getAssignedPartyOfAccount($token->getAccount());
        if (!$user instanceof User) {
            return;
        }

        $this->workspaceProvider->providePrimaryPersonalWorkspaceForUser($siteDetectionResult->contentRepositoryId, $user);
    }
}
