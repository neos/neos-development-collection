<?php
declare(strict_types=1);

namespace Neos\Neos\Controller\Module\Administration;

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
use Neos\Error\Messages\Message;
use Neos\Flow\I18n\EelHelper\TranslationHelper;
use Neos\Flow\Mvc\Exception\ForwardException;
use Neos\Flow\Mvc\Exception\NoSuchArgumentException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Authentication\TokenAndProviderFactoryInterface;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Security\Exception\NoSuchRoleException;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Domain\Exception;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Service\UserService;
use Neos\Party\Domain\Model\ElectronicAddress;

/**
 * The Neos User Admin module controller that allows for managing Neos users
 */
class UsersController extends AbstractModuleController
{
    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @Flow\Inject
     * @var PolicyService
     */
    protected $policyService;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @var User
     */
    protected $currentUser;

    /**
     * @Flow\Inject
     * @var TokenAndProviderFactoryInterface
     */
    protected $tokenAndProviderFactory;

    /**
     * @return void
     * @throws NoSuchArgumentException
     */
    protected function initializeAction()
    {
        parent::initializeAction();
        $translationHelper = new TranslationHelper();
        $this->setTitle($translationHelper->translate($this->moduleConfiguration['label']) . ' :: ' . $translationHelper->translate(str_replace('label', 'action.', $this->moduleConfiguration['label']) . $this->request->getControllerActionName()));
        if ($this->arguments->hasArgument('user')) {
            $propertyMappingConfigurationForUser = $this->arguments->getArgument('user')->getPropertyMappingConfiguration();
            $propertyMappingConfigurationForUserName = $propertyMappingConfigurationForUser->forProperty('user.name');
            $propertyMappingConfigurationForPrimaryAccount = $propertyMappingConfigurationForUser->forProperty('user.primaryAccount');
            $propertyMappingConfigurationForPrimaryAccount->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_TARGET_TYPE, Account::class);
            /** @var PropertyMappingConfiguration $propertyMappingConfiguration */
            foreach ([$propertyMappingConfigurationForUser, $propertyMappingConfigurationForUserName, $propertyMappingConfigurationForPrimaryAccount] as $propertyMappingConfiguration) {
                $propertyMappingConfiguration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, true);
                $propertyMappingConfiguration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, true);
            }
        }
        $this->currentUser = $this->userService->getCurrentUser();
    }

    /**
     * Shows a list of all users
     *
     * @param string $searchTerm
     * @return void
     */
    public function indexAction(string $searchTerm = '')
    {
        if (empty($searchTerm)) {
            $users = $this->userService->getUsers();
        } else {
            $users = $this->userService->searchUsers($searchTerm);
        }

        $this->view->assignMultiple([
            'currentUser' => $this->currentUser,
            'users' => $users,
            'searchTerm' => $searchTerm
        ]);
    }

    /**
     * Shows details for the specified user
     *
     * @param User $user
     * @return void
     */
    public function showAction(User $user): void
    {
        $this->view->assignMultiple([
            'currentUser' => $this->currentUser,
            'user' => $user
        ]);
    }

    /**
     * Renders a form for creating a new user
     *
     * @param User $user
     * @return void
     */
    public function newAction(User $user = null): void
    {
        $this->view->assignMultiple([
            'currentUser' => $this->currentUser,
            'user' => $user,
            'roles' => $this->policyService->getRoles(),
            'providers' => $this->getAuthenticationProviders()
        ]);
    }

    /**
     * Create a new user
     *
     * @param string $username The user name (ie. account identifier) of the new user
     * @param array $password Expects an array in the format array('<password>', '<password confirmation>')
     * @param User $user The user to create
     * @param array $roleIdentifiers A list of roles (role identifiers) to assign to the new user
     * @param string $authenticationProviderName Optional name of the authentication provider. If not provided the user server uses the default authentication provider
     * @Flow\Validate(argumentName="username", type="\Neos\Flow\Validation\Validator\NotEmptyValidator")
     * @Flow\Validate(argumentName="username", type="\Neos\Neos\Validation\Validator\UserDoesNotExistValidator")
     * @Flow\Validate(argumentName="password", type="\Neos\Neos\Validation\Validator\PasswordValidator", options={ "allowEmpty"=0, "minimum"=1, "maximum"=255 })
     * @return void
     * @throws StopActionException
     */
    public function createAction(string $username, array $password, User $user, array $roleIdentifiers, string $authenticationProviderName = null): void
    {
        $this->userService->addUser($username, $password[0], $user, $roleIdentifiers, $authenticationProviderName);
        $this->addFlashMessage('The user "%s" has been created.', 'User created', Message::SEVERITY_OK, [htmlspecialchars($username)], 1416225561);
        $this->redirect('index');
    }

    /**
     * Edit an existing user
     *
     * @param User $user
     * @return void
     */
    public function editAction(User $user): void
    {
        $this->assignElectronicAddressOptions();

        $this->view->assignMultiple([
            'currentUser' => $this->currentUser,
            'user' => $user,
            'availableRoles' => $this->policyService->getRoles()
        ]);
    }

    /**
     * Update a given user
     *
     * @param User $user The user to update, including updated data already (name, email address etc)
     * @return void
     * @throws StopActionException
     */
    public function updateAction(User $user): void
    {
        $this->userService->updateUser($user);
        $this->addFlashMessage('The user "%s" has been updated.', 'User updated', Message::SEVERITY_OK, [$user->getName()->getFullName()], 1412374498);
        $this->redirect('index');
    }

    /**
     * Delete the given user
     *
     * @param User $user
     * @return void
     * @throws Exception
     * @throws StopActionException
     */
    public function deleteAction(User $user): void
    {
        if ($user === $this->currentUser) {
            $this->addFlashMessage('You can not delete the currently logged in user', 'Current user can\'t be deleted', Message::SEVERITY_WARNING, [], 1412374546);
            $this->redirect('index');
        }
        $this->userService->deleteUser($user);
        $this->addFlashMessage('The user "%s" has been deleted.', 'User deleted', Message::SEVERITY_NOTICE, [htmlspecialchars($user->getName()->getFullName())], 1412374546);
        $this->redirect('index');
    }

    /**
     * Edit the given account
     *
     * @param Account $account
     * @return void
     * @throws Exception
     */
    public function editAccountAction(Account $account): void
    {
        $this->view->assignMultiple([
            'account' => $account,
            'user' => $this->userService->getUser($account->getAccountIdentifier(), (string) $account->getAuthenticationProviderName()),
            'availableRoles' => $this->policyService->getRoles()
        ]);
    }

    /**
     * Update a given account
     *
     * @param Account $account The account to update
     * @param array $roleIdentifiers A possibly updated list of roles for the user's primary account
     * @param array $password Expects an array in the format array('<password>', '<password confirmation>')
     * @return void
     * @throws StopActionException
     * @throws ForwardException
     * @throws NoSuchRoleException
     * @throws Exception
     * @Flow\Validate(argumentName="password", type="\Neos\Neos\Validation\Validator\PasswordValidator", options={ "allowEmpty"=1, "minimum"=1, "maximum"=255 })
     */
    public function updateAccountAction(Account $account, array $roleIdentifiers, array $password = []): void
    {
        $user = $this->userService->getUser($account->getAccountIdentifier(), (string) $account->getAuthenticationProviderName());
        if ($user === $this->currentUser) {
            $roles = [];
            foreach ($roleIdentifiers as $roleIdentifier) {
                $roles[$roleIdentifier] = $this->policyService->getRole($roleIdentifier);
            }
            if (!$this->privilegeManager->isPrivilegeTargetGrantedForRoles($roles, 'Neos.Neos:Backend.Module.Administration.Users')) {
                $this->addFlashMessage('With the selected roles the currently logged in user wouldn\'t have access to this module any longer. Please adjust the assigned roles!', 'Don\'t lock yourself out', Message::SEVERITY_WARNING, [], 1416501197);
                $this->forward('edit', null, null, ['user' => $this->currentUser]);
            }
        }
        $password = array_shift($password);
        if (strlen(trim(strval($password))) > 0) {
            $this->userService->setUserPassword($user, $password);
        }

        $this->userService->setRolesForAccount($account, $roleIdentifiers);
        $this->addFlashMessage('The account has been updated.', 'Account updated', Message::SEVERITY_OK);
        $this->redirect('edit', null, null, ['user' => $user]);
    }

    /**
     * The add new electronic address action
     *
     * @param User $user
     * @Flow\IgnoreValidation("$user")
     * @return void
     */
    public function newElectronicAddressAction(User $user): void
    {
        $this->assignElectronicAddressOptions();
        $this->view->assign('user', $user);
    }

    /**
     * Create an new electronic address
     *
     * @param User $user
     * @param ElectronicAddress $electronicAddress
     * @return void
     * @throws StopActionException
     */
    public function createElectronicAddressAction(User $user, ElectronicAddress $electronicAddress): void
    {
        /** @var User $user */
        $user->addElectronicAddress($electronicAddress);
        $this->userService->updateUser($user);

        $this->addFlashMessage('An electronic address "%s" (%s) has been added.', 'Electronic address added', Message::SEVERITY_OK, [htmlspecialchars($electronicAddress->getIdentifier()), htmlspecialchars($electronicAddress->getType())], 1412374814);
        $this->redirect('edit', null, null, ['user' => $user]);
    }

    /**
     * Delete an electronic address action
     *
     * @param User $user
     * @param ElectronicAddress $electronicAddress
     * @return void
     * @throws StopActionException
     */
    public function deleteElectronicAddressAction(User $user, ElectronicAddress $electronicAddress): void
    {
        $user->removeElectronicAddress($electronicAddress);
        $this->userService->updateUser($user);

        $this->addFlashMessage('The electronic address "%s" (%s) has been deleted for "%s".', 'Electronic address removed', Message::SEVERITY_NOTICE, [htmlspecialchars($electronicAddress->getIdentifier()), htmlspecialchars($electronicAddress->getType()), htmlspecialchars($user->getName())], 1412374678);
        $this->redirect('edit', null, null, ['user' => $user]);
    }

    /**
     * @return void
     */
    protected function assignElectronicAddressOptions(): void
    {
        $electronicAddress = new ElectronicAddress();
        $electronicAddressTypes = [];
        foreach ($electronicAddress->getAvailableElectronicAddressTypes() as $type) {
            $electronicAddressTypes[$type] = $type;
        }
        $electronicAddressUsageTypes = [];
        $translationHelper = new TranslationHelper();
        foreach ($electronicAddress->getAvailableUsageTypes() as $type) {
            $electronicAddressUsageTypes[$type] = $translationHelper->translate('users.electronicAddress.usage.type.' . $type, $type, [], 'Modules', 'Neos.Neos');
        }
        array_unshift($electronicAddressUsageTypes, '');
        $this->view->assignMultiple([
            'electronicAddressTypes' => $electronicAddressTypes,
            'electronicAddressUsageTypes' => $electronicAddressUsageTypes
        ]);
    }

    /**
     * Returns sorted list of auth providers by name.
     *
     * @return string[]
     */
    protected function getAuthenticationProviders(): array
    {
        $providerNames = array_keys($this->tokenAndProviderFactory->getProviders());
        sort($providerNames);
        return array_combine($providerNames, $providerNames);
    }
}
