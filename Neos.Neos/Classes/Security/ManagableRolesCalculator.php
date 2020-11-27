<?php
declare(strict_types=1);

namespace Neos\Neos\Security;

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
use Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException;
use Neos\Flow\Security\Exception as SecurityException;
use Neos\Flow\Security\Exception\NoSuchRoleException;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Security\Policy\Role;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Service\UserService;

/**
 * @Flow\Scope("singleton")
 */
class ManagableRolesCalculator implements ManagableRolesCalculatorInterface
{

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @Flow\Inject
     * @var PolicyService
     */
    protected $policyService;

    /**
     * Returns the roles that the current editor is able to assign
     * Administrator can assign any roles, other users can only assign their own roles
     *
     * @return array
     * @throws NoSuchRoleException
     * @throws SecurityException
     * @throws InvalidConfigurationTypeException
     */
    public function getAssignableRolesForCurrentUser(): array
    {
        $currentUserRoles = $this->userService->getAllRoles($this->userService->getCurrentUser());
        $currentUserRoles = array_filter($currentUserRoles, static function (Role $role) {
            return $role->isAbstract() !== true;
        });

        $roles = $this->userService->currentUserIsAdministrator() ? $this->policyService->getRoles() : $currentUserRoles;

        usort($roles, static function (Role $a, Role $b) {
            return strcmp($a->getName(), $b->getName());
        });

        return $roles;
    }

    /**
     * Returns whether the current user is allowed to edit the given user.
     * Administrators can edit anybody.
     *
     * @param User $user
     * @return bool
     * @throws NoSuchRoleException
     * @throws SecurityException
     */
    public function isCurrentUserAllowedToEdit(User $user): bool
    {
        if ($this->userService->currentUserIsAdministrator()) {
            return true;
        }

        $currentUserRoles = $this->userService->getAllRoles($this->userService->getCurrentUser());
        $userRoles = $this->userService->getAllRoles($user);
        return count(array_diff($userRoles, $currentUserRoles)) === 0;
    }
}
