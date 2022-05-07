<?php
declare(strict_types=1);

namespace Neos\Neos\ViewHelpers\Backend;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Service\UserService;

/**
 * Returns true, if the current user is allowed to edit the given user, false otherwise.
 */
class IsAllowedToEditUserViewHelper extends AbstractViewHelper
{
    /**
     * @see AbstractViewHelper::isOutputEscapingEnabled()
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @return void
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('user', User::class, 'The user subject', true);
    }

    /**
     * Returns whether the current user is allowed to edit the given user.
     * Administrators can edit anybody.
     *
     * ViewHelper arguments: @see initializeArguments
     *
     * @return bool
     * @throws \Neos\Flow\Security\Exception
     * @throws \Neos\Flow\Security\Exception\NoSuchRoleException
     */
    protected function render()
    {
        if ($this->userService->currentUserIsAdministrator()) {
            return true;
        }

        $user = $this->arguments['user'] ?? null;
        if (!$user instanceof User) {
            return false;
        }

        $currentUser = $this->userService->getCurrentUser();
        if (!$currentUser instanceof User) {
            return false;
        }

        $currentUserRoles = $this->userService->getAllRoles($currentUser);
        $userRoles = $this->userService->getAllRoles($user);
        return count(array_diff($userRoles, $currentUserRoles)) === 0;
    }
}
