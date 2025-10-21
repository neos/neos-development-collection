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
use Neos\Neos\Domain\Model\User;

/**
 * The user service provides general context information about the currently
 * authenticated backend user.
 *
 * The methods getters of this class are accessible via the "context.userInformation" variable in security policies
 * and thus are implicitly considered to be part of the public API. This UserService should be replaced by
 * \Neos\Neos\Domain\Service\UserService in the long run.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class UserService
{
    /**
     * @Flow\Inject
     * @var \Neos\Neos\Domain\Service\UserService
     */
    protected $userDomainService;

    /**
     * @Flow\InjectConfiguration("userInterface.defaultLanguage")
     * @var string
     */
    protected $defaultLanguageIdentifier;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\Security\Context
     */
    protected $securityContext;

    /**
     * Returns the current backend user
     *
     * @return ?User
     * @api
     */
    public function getBackendUser()
    {
        return $this->userDomainService->getCurrentUser();
    }

    /**
     * 8.3 behaviour: Returns the name of the currently logged in user's personal workspace
     * (even if that might not exist at that time).
     * If no user is logged in this method returns null.
     *
     * @deprecated and not implemented with Neos 9.0 - can be removed any time, just for the 8.3 upgrade phase
     */
    public function getPersonalWorkspaceName(): ?string
    {
        throw new \LogicException('`userInformation.personalWorkspaceName` was removed in Neos 9.0 see https://github.com/neos/neos-development-collection/pull/5418');
    }

    /**
     * Returns the stored preferences of a user
     *
     * @param string $preference
     * @return mixed|null
     * @api
     */
    public function getUserPreference($preference)
    {
        $user = $this->getBackendUser();
        if ($user) {
            return $user->getPreferences()->get($preference) ?: null;
        }

        return null;
    }

    /**
     * Returns the interface language the user selected. Will fall back to the default language defined in settings
     *
     * @return string
     * @api
     */
    public function getInterfaceLanguage()
    {
        return $this->getUserPreference('interfaceLanguage') ?: $this->defaultLanguageIdentifier;
    }
}
