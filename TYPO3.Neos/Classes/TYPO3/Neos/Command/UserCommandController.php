<?php
namespace TYPO3\Neos\Command;

/*
 * This file is part of the TYPO3.Neos package.
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
use TYPO3\Neos\Domain\Exception;
use TYPO3\Neos\Domain\Model\User;
use TYPO3\Neos\Domain\Service\UserService;

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

        $tableRows = array();
        $headerRow = array('Name', 'Email', 'Account(s)', 'Role(s)', 'Active');

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
     * @param string $username The username of the user to show. Usually refers to the account identifier of the user's Neos backend account.
     * @param string $authenticationProvider Name of the authentication provider to use. Example: "Typo3BackendProvider"
     * @return void
     */
    public function showCommand($username, $authenticationProvider = null)
    {
        $user = $this->userService->getUser($username, $authenticationProvider);
        if (!$user instanceof User) {
            $this->outputLine('The username "%s" is not in use', array($username));
            $this->quit(1);
        }

        $headerRow = array('Name', 'Email', 'Account(s)', 'Role(s)', 'Active');
        $tableRows = array($this->getTableRowForUser($user));

        $this->output->outputTable($tableRows, $headerRow);
    }

    /**
     * Create a new user
     *
     * This command creates a new user which has access to the backend user interface.
     *
     * More specifically, this command will create a new user and a new account at the same time. The created account
     * is, by default, a Neos backend account using the the "Typo3BackendProvider" for authentication. The given username
     * will be used as an account identifier for that new account.
     *
     * If an authentication provider name is specified, the new account will be created for that provider instead.
     *
     * Roles for the new user can optionally be specified as a comma separated list. For all roles provided by
     * Neos, the role namespace "TYPO3.Neos:" can be omitted.
     *
     * @param string $username The username of the user to be created, used as an account identifier for the newly created account
     * @param string $password Password of the user to be created
     * @param string $firstName First name of the user to be created
     * @param string $lastName Last name of the user to be created
     * @param string $roles A comma separated list of roles to assign. Examples: "Editor, Acme.Foo:Reviewer"
     * @param string $authenticationProvider Name of the authentication provider to use for the new account. Example: "Typo3BackendProvider"
     * @return void
     */
    public function createCommand($username, $password, $firstName, $lastName, $roles = null, $authenticationProvider = null)
    {
        $user = $this->userService->getUser($username, $authenticationProvider);
        if ($user instanceof User) {
            $this->outputLine('The username "%s" is already in use', array($username));
            $this->quit(1);
        }

        try {
            if ($roles === null) {
                $user = $this->userService->createUser($username, $password, $firstName, $lastName, null, $authenticationProvider);
            } else {
                $roleIdentifiers = Arrays::trimExplode(',', $roles);
                $user = $this->userService->createUser($username, $password, $firstName, $lastName, $roleIdentifiers, $authenticationProvider);
            }

            $roleIdentifiers = array();
            foreach ($user->getAccounts() as $account) {
                /** @var Account $account */
                foreach ($account->getRoles() as $role) {
                    /** @var Role $role */
                    $roleIdentifiers[$role->getIdentifier()] = true;
                }
            }
            $roleIdentifiers = array_keys($roleIdentifiers);

            if (count($roleIdentifiers) === 0) {
                $this->outputLine('Created user "%s".', array($username));
                $this->outputLine('<b>Please note that this user currently does not have any roles assigned.</b>');
            } else {
                $this->outputLine('Created user "%s" and assigned the following role%s: %s.', array($username, (count($roleIdentifiers) > 1 ? 's' : ''), implode(', ', $roleIdentifiers)));
            }
        } catch (\Exception $exception) {
            $this->outputLine($exception->getMessage());
            $this->quit(1);
        }
    }

    /**
     * Delete a user
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
     * @param string $username The username of the user to be removed
     * @param boolean $assumeYes Assume "yes" as the answer to the confirmation dialog
     * @param string $authenticationProvider Name of the authentication provider to use. Example: "Typo3BackendProvider"
     * @return void
     */
    public function deleteCommand($username, $assumeYes = false, $authenticationProvider = null)
    {
        $user = $this->getUserOrFail($username, $authenticationProvider);

        if ($assumeYes === true) {
            $delete = true;
        } else {
            $delete = $this->output->askConfirmation(sprintf('Are you sure you want to delete the user "%s" (%s) including all directly related data? (y/n) ', $username, $user->getName()));
        }

        if ($delete) {
            $this->userService->deleteUser($user);
            $this->outputLine('Deleted user "%s".', array($username));
        }
    }

    /**
     * Activate a user
     *
     * This command reactivates possibly expired accounts for the given user.
     *
     * If an authentication provider is specified, this command will look for an account with the given username related
     * to the given provider. Still, this command will activate <b>all</b> accounts of a user, once such a user has been
     * found.
     *
     * @param string $username The username of the user to be activated.
     * @param string $authenticationProvider Name of the authentication provider to use for finding the user. Example: "Typo3BackendProvider"
     * @return void
     */
    public function activateCommand($username, $authenticationProvider = null)
    {
        $user = $this->getUserOrFail($username, $authenticationProvider);

        $this->userService->activateUser($user);
        $this->outputLine('Activated user "%s".', array($username));
    }

    /**
     * Deactivate a user
     *
     * This command deactivates a user by flagging all of its accounts as expired.
     *
     * If an authentication provider is specified, this command will look for an account with the given username related
     * to the given provider. Still, this command will deactivate <b>all</b> accounts of a user, once such a user has been
     * found.
     *
     * @param string $username The username of the user to be deactivated.
     * @param string $authenticationProvider Name of the authentication provider to use for finding the user. Example: "Typo3BackendProvider"
     * @return void
     */
    public function deactivateCommand($username, $authenticationProvider = null)
    {
        $user = $this->getUserOrFail($username, $authenticationProvider);

        $this->userService->deactivateUser($user);
        $this->outputLine('Deactivated user "%s".', array($username));
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
     * @param string $authenticationProvider Name of the authentication provider to use for finding the user. Example: "Typo3BackendProvider"
     * @return void
     */
    public function setPasswordCommand($username, $password, $authenticationProvider = null)
    {
        $user = $this->getUserOrFail($username, $authenticationProvider);
        $this->userService->setUserPassword($user, $password);
        $this->outputLine('The new password for user "%s" was set.', array($username));
    }

    /**
     * Add a role to a user
     *
     * This command allows for adding a specific role to an existing user.
     *
     * Roles can optionally be specified as a comma separated list. For all roles provided by Neos, the role
     * namespace "TYPO3.Neos:" can be omitted.
     *
     * If an authentication provider was specified, the user will be determined by an account identified by "username"
     * related to the given provider. However, once a user has been found, the new role will be added to <b>all</b>
     * existing accounts related to that user, regardless of its authentication provider.
     *
     * @param string $username The username of the user
     * @param string $role Role to be added to the user, for example "TYPO3.Neos:Administrator" or just "Administrator"
     * @param string $authenticationProvider Name of the authentication provider to use. Example: "Typo3BackendProvider"
     * @return void
     */
    public function addRoleCommand($username, $role, $authenticationProvider = null)
    {
        $user = $this->getUserOrFail($username, $authenticationProvider);

        try {
            if ($this->userService->addRoleToUser($user, $role) > 0) {
                $this->outputLine('Added role "%s" to accounts of user "%s".', array($role, $username));
            } else {
                $this->outputLine('User "%s" already had the role "%s" assigned.', array($username, $role));
            }
        } catch (NoSuchRoleException $exception) {
            $this->outputLine('The role "%s" does not exist.', array($role));
            $this->quit(2);
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
     * @param string $username The username of the user
     * @param string $role Role to be removed from the user, for example "TYPO3.Neos:Administrator" or just "Administrator"
     * @param string $authenticationProvider Name of the authentication provider to use. Example: "Typo3BackendProvider"
     * @return void
     */
    public function removeRoleCommand($username, $role, $authenticationProvider = null)
    {
        $user = $this->getUserOrFail($username, $authenticationProvider);

        try {
            if ($this->userService->removeRoleFromUser($user, $role) > 0) {
                $this->outputLine('Removed role "%s" from user "%s".', array($role, $username));
            } else {
                $this->outputLine('User "%s" did not have the role "%s" assigned.', array($username, $role));
            }
        } catch (NoSuchRoleException $exception) {
            $this->outputLine('The role "%s" does not exist.', array($role));
            $this->quit(2);
        }
    }

    /**
     * Retrieves the given user or fails by exiting with code 1 and a message
     *
     * @param string $username Username of the user to find
     * @param string $authenticationProviderName Name of the authentication provider to use
     * @return User The user
     * @throws Exception
     */
    protected function getUserOrFail($username, $authenticationProviderName)
    {
        $user = $this->userService->getUser($username, $authenticationProviderName);
        if (!$user instanceof User) {
            $this->outputLine('The user "%s" does not exist.', array($username));
            $this->quit(1);
        }
        return $user;
    }

    /**
     * Prepares a table row for output with data of the given User
     *
     * @param User $user The user
     * @return array
     */
    protected function getTableRowForUser(User $user)
    {
        $roleNames = array();
        $accountIdentifiers = array();
        foreach ($user->getAccounts() as $account) {
            /** @var Account $account */
            $authenticationProviderName = $account->getAuthenticationProviderName();
            if ($authenticationProviderName !== $this->userService->getDefaultAuthenticationProviderName()) {
                $authenticationProviderLabel = ' (' . (isset($this->authenticationProviderSettings[$authenticationProviderName]['label']) ? $this->authenticationProviderSettings[$authenticationProviderName]['label'] : $authenticationProviderName) . ')';
            } else {
                $authenticationProviderLabel = '';
            }
            $accountIdentifiers[] = $account->getAccountIdentifier() . $authenticationProviderLabel;
            foreach ($account->getRoles() as $role) {
                /** @var Role $role */
                $roleNames[] = $role->getIdentifier();
            }
        }
        return array($user->getName()->getFullName(), $user->getPrimaryElectronicAddress(), implode(', ', $accountIdentifiers), implode(', ', $roleNames), ($user->isActive() ? 'yes' : 'no'));
    }
}
