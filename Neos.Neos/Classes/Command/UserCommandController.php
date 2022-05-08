<?php
namespace Neos\Neos\Command;

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
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Exception\NoSuchRoleException;
use Neos\Flow\Security\Policy\Role;
use Neos\Utility\Arrays;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Service\UserService;

/**
 * The User Command Controller
 *
 * @Flow\Scope("singleton")
 */
class UserCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @Flow\InjectConfiguration(package="Neos.Flow", path="security.authentication.providers")
     * @var array
     * @phpstan-var array<string,mixed>
     */
    protected $authenticationProviderSettings;

    /**
     * List all users
     *
     * This command lists all existing Neos users.
     *
     * @return void
     */
    public function listCommand()
    {
        $users = $this->userService->getUsers();

        $tableRows = [];
        $headerRow = ['Name', 'Email', 'Account(s)', 'Role(s)', 'Active'];

        foreach ($users as $user) {
            $tableRows[] = $this->getTableRowForUser($user);
        }

        $this->output->outputTable($tableRows, $headerRow);
    }

    /**
     * Shows the given user
     *
     * This command shows some basic details about the given user. If such a user does not exist, this command
     * will exit with a non-zero status code.
     *
     * The user will be retrieved by looking for a Neos backend account with the given identifier (ie. the username)
     * and then retrieving the user which owns that account. If an authentication provider is specified, this command
     * will look for an account identified by "username" for that specific provider.
     *
     * @param string $username The username of the user to show.
     *                         Usually refers to the account identifier of the user's Neos backend account.
     * @param string $authenticationProvider Name of the authentication provider to use. Example: "Neos.Neos:Backend"
     * @return void
     */
    public function showCommand($username, $authenticationProvider = null)
    {
        $user = $this->userService->getUser($username, $authenticationProvider);
        if (!$user instanceof User) {
            $this->outputLine('The username "%s" is not in use', [$username]);
            $this->quit(1);
        }
        /** @var User $user */

        $headerRow = ['Name', 'Email', 'Account(s)', 'Role(s)', 'Active'];
        $tableRows = [$this->getTableRowForUser($user)];

        $this->output->outputTable($tableRows, $headerRow);
    }

    /**
     * Create a new user
     *
     * This command creates a new user which has access to the backend user interface.
     *
     * More specifically, this command will create a new user and a new account at the same time. The created account
     * is, by default, a Neos backend account using the the "Neos.Neos:Backend" for authentication. The given username
     * will be used as an account identifier for that new account.
     *
     * If an authentication provider name is specified, the new account will be created for that provider instead.
     *
     * Roles for the new user can optionally be specified as a comma separated list. For all roles provided by
     * Neos, the role namespace "Neos.Neos:" can be omitted.
     *
     * @param string $username The username of the user to be created,
     *                         used as an account identifier for the newly created account
     * @param string $password Password of the user to be created
     * @param string $firstName First name of the user to be created
     * @param string $lastName Last name of the user to be created
     * @param string $roles A comma separated list of roles to assign. Examples: "Editor, Acme.Foo:Reviewer"
     * @param string $authenticationProvider Name of the authentication provider to use for the new account.
     *                                       Example: "Neos.Neos:Backend"
     * @return void
     */
    public function createCommand(
        $username,
        $password,
        $firstName,
        $lastName,
        $roles = null,
        $authenticationProvider = null
    ) {
        $user = $this->userService->getUser($username, $authenticationProvider);
        if ($user instanceof User) {
            $this->outputLine('The username "%s" is already in use', [$username]);
            $this->quit(1);
        }

        try {
            if ($roles === null) {
                $user = $this->userService->createUser(
                    $username,
                    $password,
                    $firstName,
                    $lastName,
                    null,
                    $authenticationProvider
                );
            } else {
                $roleIdentifiers = Arrays::trimExplode(',', $roles);
                $user = $this->userService->createUser(
                    $username,
                    $password,
                    $firstName,
                    $lastName,
                    $roleIdentifiers,
                    $authenticationProvider
                );
            }

            $roleIdentifiers = [];
            /** @var Account $account */
            foreach ($user->getAccounts() as $account) {
                foreach ($account->getRoles() as $role) {
                    /** @var Role $role */
                    $roleIdentifiers[$role->getIdentifier()] = true;
                }
            }
            $roleIdentifiers = array_keys($roleIdentifiers);

            if (count($roleIdentifiers) === 0) {
                $this->outputLine('Created user "%s".', [$username]);
                $this->outputLine('<b>Please note that this user currently does not have any roles assigned.</b>');
            } else {
                $this->outputLine(
                    'Created user "%s" and assigned the following role%s: %s.',
                    [$username, (count($roleIdentifiers) > 1 ? 's' : ''), implode(', ', $roleIdentifiers)]
                );
            }
        } catch (\Exception $exception) {
            $this->outputLine($exception->getMessage());
            $this->quit(1);
        }
    }

    /**
     * Delete a user (with globbing)
     *
     * This command deletes an existing Neos user. All content and data directly related to this user, including but
     * not limited to draft workspace contents, will be removed as well.
     *
     * All accounts owned by the given user will be deleted.
     *
     * If an authentication provider is specified, this command will look for an account with the given username related
     * to the given provider. Specifying an authentication provider does <b>not</b> mean that only the account for that
     * provider is deleted! If a user was found by the combination of username and authentication provider, <b>all</b>
     * related accounts will be deleted.
     *
     * @param string $username The username of the user to be removed (globbing is supported)
     * @param boolean $assumeYes Assume "yes" as the answer to the confirmation dialog
     * @param string $authenticationProvider Name of the authentication provider to use. Example: "Neos.Neos:Backend"
     * @return void
     */
    public function deleteCommand($username, $assumeYes = false, $authenticationProvider = null)
    {
        $users = $this->findUsersByUsernamePattern($username, $authenticationProvider);
        if (empty($users)) {
            $this->outputLine('No users that match name-pattern "%s" do exist.', [$username]);
            $this->quit(1);
        }

        foreach ($users as $user) {
            $username = $this->userService->getUsername($user, $authenticationProvider);

            if ($assumeYes === true) {
                $delete = true;
            } else {
                $delete = $this->output->askConfirmation(sprintf(
                    'Are you sure you want to delete the user "%s" (%s) including all directly related data? (y/n) ',
                    $username,
                    $user->getName()
                ));
            }

            if ($delete) {
                $this->userService->deleteUser($user);
                $this->outputLine('Deleted user "%s".', [$username]);
            }
        }
    }

    /**
     * Activate a user (with globbing)
     *
     * This command reactivates possibly expired accounts for the given user.
     *
     * If an authentication provider is specified, this command will look for an account with the given username related
     * to the given provider. Still, this command will activate <b>all</b> accounts of a user, once such a user has been
     * found.
     *
     * @param string $username The username of the user to be activated (globbing is supported)
     * @param string $authenticationProvider Name of the authentication provider to use for finding the user.
     *                                       Example: "Neos.Neos:Backend"
     * @return void
     */
    public function activateCommand($username, $authenticationProvider = null)
    {
        $users = $this->findUsersByUsernamePattern($username, $authenticationProvider);
        if (empty($users)) {
            $this->outputLine('No users that match name-pattern "%s" do exist.', [$username]);
            $this->quit(1);
        }

        foreach ($users as $user) {
            $username = $this->userService->getUsername($user, $authenticationProvider);
            $this->userService->activateUser($user);
            $this->outputLine('Activated user "%s".', [$username]);
        }
    }

    /**
     * Deactivate a user (with globbing)
     *
     * This command deactivates a user by flagging all of its accounts as expired.
     *
     * If an authentication provider is specified, this command will look for an account with the given username
     * related to the given provider. Still, this command will deactivate <b>all</b> accounts of a user,
     * once such a user has been found.
     *
     * @param string $username The username of the user to be deactivated (globbing is supported)
     * @param string $authenticationProvider Name of the authentication provider to use for finding the user.
     *                                       Example: "Neos.Neos:Backend"
     * @return void
     */
    public function deactivateCommand($username, $authenticationProvider = null)
    {
        $users = $this->findUsersByUsernamePattern($username, $authenticationProvider);
        if (empty($users)) {
            $this->outputLine('No users that match name-pattern "%s" do exist.', [$username]);
            $this->quit(1);
        }

        foreach ($users as $user) {
            $username = $this->userService->getUsername($user, $authenticationProvider);
            $this->userService->deactivateUser($user);
            $this->outputLine('Deactivated user "%s".', [$username]);
        }
    }

    /**
     * Set a new password for the given user
     *
     * This command sets a new password for an existing user. More specifically, all accounts related to the user
     * which are based on a username / password token will receive the new password.
     *
     * If an authentication provider was specified, the user will be determined by an account identified by "username"
     * related to the given provider.
     *
     * @param string $username Username of the user to modify
     * @param string $password The new password
     * @param string $authenticationProvider Name of the authentication provider to use for finding the user.
     *                                       Example: "Neos.Neos:Backend"
     * @return void
     */
    public function setPasswordCommand($username, $password, $authenticationProvider = null)
    {
        $user = $this->userService->getUser($username, $authenticationProvider);
        if (!$user instanceof User) {
            $this->outputLine('The user "%s" does not exist.', [$username]);
            $this->quit(1);
        }
        /** @var User $user */
        $this->userService->setUserPassword($user, $password);
        $this->outputLine('The new password for user "%s" was set.', [$username]);
    }

    /**
     * Add a role to a user
     *
     * This command allows for adding a specific role to an existing user.
     *
     * Roles can optionally be specified as a comma separated list. For all roles provided by Neos, the role
     * namespace "Neos.Neos:" can be omitted.
     *
     * If an authentication provider was specified, the user will be determined by an account identified by "username"
     * related to the given provider. However, once a user has been found, the new role will be added to <b>all</b>
     * existing accounts related to that user, regardless of its authentication provider.
     *
     * @param string $username The username of the user (globbing is supported)
     * @param string $role Role to be added to the user, for example "Neos.Neos:Administrator" or just "Administrator"
     * @param string $authenticationProvider Name of the authentication provider to use. Example: "Neos.Neos:Backend"
     * @return void
     */
    public function addRoleCommand($username, $role, $authenticationProvider = null)
    {
        $users = $this->findUsersByUsernamePattern($username, $authenticationProvider);
        if (empty($users)) {
            $this->outputLine('No users that match name-pattern "%s" do exist.', [$username]);
            $this->quit(1);
        }

        foreach ($users as $user) {
            $username = $this->userService->getUsername($user, $authenticationProvider);
            try {
                if ($this->userService->addRoleToUser($user, $role) > 0) {
                    $this->outputLine('Added role "%s" to accounts of user "%s".', [$role, $username]);
                } else {
                    $this->outputLine('User "%s" already had the role "%s" assigned.', [$username, $role]);
                }
            } catch (NoSuchRoleException $exception) {
                $this->outputLine('The role "%s" does not exist.', [$role]);
                $this->quit(2);
            }
        }
    }

    /**
     * Remove a role from a user
     *
     * This command allows for removal of a specific role from an existing user.
     *
     * If an authentication provider was specified, the user will be determined by an account identified by "username"
     * related to the given provider. However, once a user has been found, the role will be removed from <b>all</b>
     * existing accounts related to that user, regardless of its authentication provider.
     *
     * @param string $username The username of the user (globbing is supported)
     * @param string $role Role to be removed from the user,
     *                     for example "Neos.Neos:Administrator" or just "Administrator"
     * @param string $authenticationProvider Name of the authentication provider to use. Example: "Neos.Neos:Backend"
     * @return void
     */
    public function removeRoleCommand($username, $role, $authenticationProvider = null)
    {
        $users = $this->findUsersByUsernamePattern($username, $authenticationProvider);
        if (empty($users)) {
            $this->outputLine('No users that match name-pattern "%s" do exist.', [$username]);
            $this->quit(1);
        }

        foreach ($users as $user) {
            $username = $this->userService->getUsername($user, $authenticationProvider);
            try {
                if ($this->userService->removeRoleFromUser($user, $role) > 0) {
                    $this->outputLine('Removed role "%s" from user "%s".', [$role, $username]);
                } else {
                    $this->outputLine('User "%s" did not have the role "%s" assigned.', [$username, $role]);
                }
            } catch (NoSuchRoleException $exception) {
                $this->outputLine('The role "%s" does not exist.', [$role]);
                $this->quit(2);
            }
        }
    }

    /**
     * Find all users the match the given username with globbing support
     *
     * @param string $usernamePattern pattern for the username of the users to find
     * @param string $authenticationProviderName Name of the authentication provider to use
     * @return array<User>
     */
    protected function findUsersByUsernamePattern($usernamePattern, $authenticationProviderName = null)
    {
        if (preg_match('/[\\?\\*\\{\\[]/u', $usernamePattern)) {
            return array_filter(
                $this->userService->getUsers()->toArray(),
                function ($user) use ($usernamePattern, $authenticationProviderName) {
                    return fnmatch(
                        $usernamePattern,
                        $this->userService->getUsername($user, $authenticationProviderName) ?: ''
                    );
                }
            );
        } else {
            $user = $this->userService->getUser($usernamePattern, $authenticationProviderName);
            if ($user instanceof User) {
                return [$user];
            }
        }
        return [];
    }

    /**
     * Prepares a table row for output with data of the given User
     *
     * @param User $user The user
     * @return array<int,mixed>
     */
    protected function getTableRowForUser(User $user)
    {
        $roleNames = [];
        $accountIdentifiers = [];
        foreach ($user->getAccounts() as $account) {
            /** @var Account $account */
            $authenticationProviderName = $account->getAuthenticationProviderName();
            if ($authenticationProviderName !== $this->userService->getDefaultAuthenticationProviderName()) {
                $authenticationProviderLabel = ' ('
                    . ($this->authenticationProviderSettings[$authenticationProviderName]['label']
                        ?? $authenticationProviderName) . ')';
            } else {
                $authenticationProviderLabel = '';
            }
            $accountIdentifiers[] = $account->getAccountIdentifier() . $authenticationProviderLabel;
            foreach ($account->getRoles() as $role) {
                /** @var Role $role */
                $roleNames[] = $role->getIdentifier();
            }
        }
        return [
            $user->getName()->getFullName(),
            $user->getPrimaryElectronicAddress(),
            implode(', ', $accountIdentifiers),
            implode(', ', $roleNames),
            ($user->isActive() ? 'yes' : 'no')
        ];
    }
}
