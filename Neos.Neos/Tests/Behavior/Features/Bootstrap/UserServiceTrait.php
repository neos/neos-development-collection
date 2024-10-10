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

use Behat\Gherkin\Node\TableNode;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\AccountFactory;
use Neos\Flow\Security\AccountRepository;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Service\UserService;
use Neos\Party\Domain\Model\PersonName;
use Neos\Party\Domain\Repository\PartyRepository;
use Neos\Party\Domain\Service\PartyService;
use Neos\Utility\ObjectAccess;

/**
 * Step implementations for UserService related tests inside Neos.Neos
 *
 * @internal only for behat tests within the Neos.Neos package
 */
trait UserServiceTrait
{
    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T
     */
    abstract private function getObject(string $className): object;

    /**
     * @Given the Neos user :username exists with id :id and first name :firstName and last name :lastName and roles :roles
     * @Given the Neos user :username exists with id :id and first name :firstName and last name :lastName
     * @Given the Neos user :username exists with first name :firstName and last name :lastName
     * @Given the Neos user :username exists
     */
    public function theNeosUserExists(string $username, string $id = null, string $firstName = null, string $lastName = null, string $roles = null): void
    {
        $this->createUser(
            username: $username,
            firstName: $firstName,
            lastName: $lastName,
            roleIdentifiers: $roles !== null ? explode(',', $roles) : null,
            id: $id,
        );
    }


    /**
     * @Given the following Neos users exist:
     */
    public function theFollowingNeosUsersExist(TableNode $usersTable): void
    {
        foreach ($usersTable->getHash() as $userData) {
            $this->createUser(
                username: $userData['Username'],
                firstName: $userData['First name'] ?? null,
                lastName: $userData['Last name'] ?? null,
                roleIdentifiers: isset($userData['Roles']) ? explode(',', $userData['Roles']) : null,
                id: $userData['Id'] ?? null,
            );
        }
    }

    /**
     * NOTE: We don't use {@see UserService::addUser()} here because that uses the {@see AccountFactory} internally which creates a strong password – which is really slow...
     */
    private function createUser(string $username, string $firstName = null, string $lastName = null, array $roleIdentifiers = null, string $id = null): void
    {
        $userService = $this->getObject(UserService::class);
        $user = new User();
        if ($id !== null) {
            ObjectAccess::setProperty($user, 'Persistence_Object_Identifier', $id, true);
        }
        $name = new PersonName('', $firstName ?? 'John', '', $lastName ?? 'Doe', '', $username);
        $user->setName($name);
        $account = new Account();
        $account->setAccountIdentifier($username);
        $account->setAuthenticationProviderName($userService->getDefaultAuthenticationProviderName());

        $policyService = $this->getObject(PolicyService::class);
        $account->setRoles(array_map($policyService->getRole(...), $roleIdentifiers ?? ['Neos.Neos:Editor']));
        $this->getObject(PartyService::class)->assignAccountToParty($account, $user);
        $this->getObject(PartyRepository::class)->add($user);
        $this->getObject(AccountRepository::class)->add($account);
        $this->getObject(PersistenceManagerInterface::class)->persistAll();
    }
}
