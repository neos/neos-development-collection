<?php
namespace TYPO3\Neos\Domain\Factory;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * A factory to conveniently create User models
 *
 * @Flow\Scope("singleton")
 */
class UserFactory
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Security\AccountFactory
     */
    protected $accountFactory;

    /**
     * Creates a User with the given information
     *
     * The User is not added to the repository, the caller has to add the
     * User account to the AccountRepository and the User to the
     * PartyRepository to persist it.
     *
     * @param string $username The username of the user to be created.
     * @param string $password Password of the user to be created
     * @param string $firstName First name of the user to be created
     * @param string $lastName Last name of the user to be created
     * @param array $roleIdentifiers A list of role identifiers to assign
     * @param string $authenticationProvider Name of the authentication provider to use
     * @return \TYPO3\Neos\Domain\Model\User The created user instance
     */
    public function create($username, $password, $firstName, $lastName, array $roleIdentifiers = null, $authenticationProvider = 'Typo3BackendProvider')
    {
        $user = new \TYPO3\Neos\Domain\Model\User();
        $name = new \TYPO3\Party\Domain\Model\PersonName('', $firstName, '', $lastName, '', $username);
        $user->setName($name);

        if ($roleIdentifiers === null || $roleIdentifiers === array()) {
            $roleIdentifiers = array('TYPO3.Neos:Editor');
        }

        $account = $this->accountFactory->createAccountWithPassword($username, $password, $roleIdentifiers, $authenticationProvider);
        $user->addAccount($account);

        return $user;
    }
}
