<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace Neos\Neos\Security;

use Neos\Neos\Domain\Model\User;

interface ManagableRolesCalculatorInterface
{
    /**
     * Returns whether the current user is allowed to edit the given user.
     * Administrators can edit anybody.
     *
     * @param User $user
     * @return bool
     */
    public function isCurrentUserAllowedToEdit(User $user): bool;

    /**
     * Returns the roles that the current editor is able to assign
     * Administrator can assign any roles, other users can only assign their own roles
     *
     * @return array
     */
    public function getAssignableRolesForCurrentUser(): array;
}
