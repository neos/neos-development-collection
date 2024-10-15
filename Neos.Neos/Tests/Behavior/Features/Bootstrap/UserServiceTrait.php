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
use Neos\Flow\Security\AccountFactory;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Service\UserService;
use Neos\Party\Domain\Model\PersonName;
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

    private function createUser(string $username, string $firstName = null, string $lastName = null, array $roleIdentifiers = null, string $id = null): void
    {
        $userService = $this->getObject(UserService::class);
        $user = new User();
        if ($id !== null) {
            ObjectAccess::setProperty($user, 'Persistence_Object_Identifier', $id, true);
        }

        $accountFactory = $this->getObject(AccountFactory::class);

        // NOTE: We replace the original {@see HashService} by a "mock" for performance reasons (the default hashing strategy usually takes a while to create passwords)

        /** @var HashService $originalHashService */
        $originalHashService = ObjectAccess::getProperty($accountFactory, 'hashService', true);
        $hashServiceMock = new class extends HashService {
            public function hashPassword($password, $strategyIdentifier = 'default'): string
            {
                return 'hashed-password';
            }
        };
        ObjectAccess::setProperty($accountFactory, 'hashService', $hashServiceMock, true);

        $name = new PersonName('', $firstName ?? 'John', '', $lastName ?? 'Doe', '', $username);
        $user->setName($name);
        $userService->addUser($username, 'password', $user, $roleIdentifiers);
        $this->getObject(PersistenceManagerInterface::class)->persistAll();
        ObjectAccess::setProperty($accountFactory, 'hashService', $originalHashService, true);
    }
}
