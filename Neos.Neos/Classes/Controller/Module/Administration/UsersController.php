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
use Neos\Flow\I18n\Translator;
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
use Neos\Flow\Security\Policy\Role;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Controller\Module\ModuleTranslationTrait;
use Neos\Neos\Domain\Exception;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Service\UserService;
use Neos\Party\Domain\Model\ElectronicAddress;

/**
 * The Neos User Admin module controller that allows for managing Neos users
 */
class UsersController extends AbstractModuleController
{
    use ModuleTranslationTrait;

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

    protected ?User $currentUser;

    /**
     * @Flow\Inject
     * @var TokenAndProviderFactoryInterface
     */
    protected $tokenAndProviderFactory;

    /**
     * @Flow\Inject
     * @var Translator
     */
    protected $translator;

    /**
     * @Flow\InjectConfiguration(package="Neos.Flow", path="security.authentication.providers")
     * @var array
     * @phpstan-var array<string,mixed>
     */
    protected $authenticationProviderSettings;

    /**
     * @return void
     * @throws NoSuchArgumentException
     */
    protected function initializeAction()
    {
        parent::initializeAction();
        $translationHelper = new TranslationHelper();
        $this->setTitle(
            $translationHelper->translate($this->moduleConfiguration['label']) . ' :: '
                . $translationHelper->translate(
                    str_replace('label', 'action.', $this->moduleConfiguration['label'])
                        . $this->request->getControllerActionName()
                )
        );
        if ($this->arguments->hasArgument('user')) {
            $propertyMappingConfigurationForUser
                = $this->arguments->getArgument('user')->getPropertyMappingConfiguration();
            $propertyMappingConfigurationForUserName = $propertyMappingConfigurationForUser->forProperty('user.name');
            $propertyMappingConfigurationForPrimaryAccount
                = $propertyMappingConfigurationForUser->forProperty('user.primaryAccount');
            $propertyMappingConfigurationForPrimaryAccount->setTypeConverterOption(
                PersistentObjectConverter::class,
                PersistentObjectConverter::CONFIGURATION_TARGET_TYPE,
                Account::class
            );
            /** @var PropertyMappingConfiguration $propertyMappingConfiguration */
            foreach ([
                $propertyMappingConfigurationForUser,
                $propertyMappingConfigurationForUserName,
                $propertyMappingConfigurationForPrimaryAccount
            ] as $propertyMappingConfiguration) {
                $propertyMappingConfiguration->setTypeConverterOption(
                    PersistentObjectConverter::class,
                    PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
                    true
                );
                $propertyMappingConfiguration->setTypeConverterOption(
                    PersistentObjectConverter::class,
                    PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED,
                    true
                );
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
    public function indexAction(string $searchTerm = ''): void
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
            'roles' => $this->getAllowedRoles(),
            'providers' => $this->getAuthenticationProviders()
        ]);
    }

    /**
     * Create a new user
     *
     * @param string $username The user name (ie. account identifier) of the new user
     * @param array<int,string> $password Expects an array in the format array('<password>', '<password confirmation>')
     * @param User $user The user to create
     * @param array<int,string> $roleIdentifiers A list of roles (role identifiers) to assign to the new user
     * @param string $authenticationProviderName Optional name of the authentication provider.
     *                                         If not provided the user server uses the default authentication provider
     * @return void
     * @throws NoSuchRoleException
     * @throws StopActionException
     * @throws \Neos\Flow\Security\Exception
     *
     * @Flow\Validate(argumentName="username", type="\Neos\Flow\Validation\Validator\NotEmptyValidator")
     * @Flow\Validate(argumentName="username", type="\Neos\Neos\Validation\Validator\UserDoesNotExistValidator")
     * @codingStandardsIgnoreStart
     * @Flow\Validate(argumentName="password", type="\Neos\Neos\Validation\Validator\PasswordValidator", options={ "allowEmpty"=0, "minimum"=1, "maximum"=255 })
     * @codingStandardsIgnoreEnd
     */
    public function createAction(
        string $username,
        array $password,
        User $user,
        array $roleIdentifiers,
        string $authenticationProviderName = null
    ): void {
        $currentUserRoles = $this->userService->getAllRoles($this->currentUser);
        $isCreationAllowed = $this->userService->currentUserIsAdministrator()
            || count(array_diff($roleIdentifiers, $currentUserRoles)) === 0;
        if ($isCreationAllowed) {
            $this->userService->addUser($username, $password[0], $user, $roleIdentifiers, $authenticationProviderName);
            $this->addFlashMessage(
                $this->getModuleLabel('users.userCreated.body', [htmlspecialchars($username)]),
                $this->getModuleLabel('users.userCreated.title'),
                Message::SEVERITY_OK,
                [],
                1416225561
            );
        } else {
            $this->addFlashMessage(
                $this->getModuleLabel('users.userCreationDenied.body', [implode(', ', $roleIdentifiers)]),
                $this->getModuleLabel('users.userCreationDenied.title'),
                Message::SEVERITY_ERROR,
                [],
                1416225562
            );
        }
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
        if (!$this->isEditingAllowed($user)) {
            $this->addFlashMessage(
                $this->getModuleLabel(
                    'users.userEditingDenied.editing.body',
                    [htmlspecialchars($user->getName()->getFullName())]
                ),
                $this->getModuleLabel('users.userEditingDenied.editing.title'),
                Message::SEVERITY_ERROR,
                [],
                1416225563
            );
            $this->redirect('index');
        }

        $this->assignElectronicAddressOptions();

        $this->view->assignMultiple([
            'currentUser' => $this->currentUser,
            'user' => $user,
            'availableRoles' => $this->getAllowedRoles()
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
        if (!$this->isEditingAllowed($user)) {
            $this->addFlashMessage(
                $this->getModuleLabel('users.userEditingDenied.editing.body', [$user->getName()->getFullName()]),
                $this->getModuleLabel('users.userEditingDenied.editing.title'),
                Message::SEVERITY_ERROR,
                [],
                1416225563
            );
            $this->redirect('index');
        }
        $this->userService->updateUser($user);
        $this->addFlashMessage(
            $this->getModuleLabel('users.userUpdated.body', [$user->getName()->getFullName()]),
            $this->getModuleLabel('users.userUpdated.title'),
            Message::SEVERITY_OK,
            [],
            1412374498
        );
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
        if (!$this->isEditingAllowed($user)) {
            $this->addFlashMessage(
                $this->getModuleLabel('users.userEditingDenied.deletion.body', [$user->getName()->getFullName()]),
                $this->getModuleLabel('users.userEditingDenied.deletion.title'),
                Message::SEVERITY_ERROR,
                [],
                1416225564
            );
            $this->redirect('index');
        }
        if ($user === $this->currentUser) {
            $this->addFlashMessage(
                $this->getModuleLabel('users.currentUserCannotBeDeleted.body'),
                $this->getModuleLabel('users.currentUserCannotBeDeleted.title'),
                Message::SEVERITY_WARNING,
                [],
                1412374546
            );
            $this->redirect('index');
        }
        $this->userService->deleteUser($user);
        $this->addFlashMessage(
            $this->getModuleLabel('users.userDeleted.body', [htmlspecialchars($user->getName()->getFullName())]),
            $this->getModuleLabel('users.userDeleted.title'),
            Message::SEVERITY_NOTICE,
            [],
            1412374546
        );
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
        $user = $this->userService->getUser(
            $account->getAccountIdentifier(),
            $account->getAuthenticationProviderName()
        );
        if (!$user instanceof User || !$this->isEditingAllowed($user)) {
            $this->addFlashMessage(
                $this->getModuleLabel('users.userAccountEditingDenied.body', [$user?->getName()->getFullName()]),
                $this->getModuleLabel('users.userAccountEditingDenied.title'),
                Message::SEVERITY_ERROR,
                [],
                1416225565
            );
            $this->redirect('index');
        }

        $this->view->assignMultiple([
            'account' => $account,
            'user' => $user,
            'availableRoles' => $this->getAllowedRoles()
        ]);
    }

    /**
     * Update a given account
     *
     * @param Account $account The account to update
     * @param array<int,string> $roleIdentifiers A possibly updated list of roles for the user's primary account
     * @param array<int,string> $password Expects an array in the format array('<password>', '<password confirmation>')
     * @return void
     * @throws StopActionException
     * @throws ForwardException
     * @throws NoSuchRoleException
     * @throws Exception
     * @codingStandardsIgnoreStart
     * @Flow\Validate(argumentName="password", type="\Neos\Neos\Validation\Validator\PasswordValidator", options={ "allowEmpty"=1, "minimum"=1, "maximum"=255 })
     * @codingStandardsIgnoreEnd
     */
    public function updateAccountAction(Account $account, array $roleIdentifiers, array $password = []): void
    {
        $user = $this->userService->getUser(
            $account->getAccountIdentifier(),
            $account->getAuthenticationProviderName()
        );
        if (!$user instanceof User) {
            $this->throwStatus(404, 'Could not resolve user for account "' . $account->getAccountIdentifier() . '"');
        }
        if (!$this->isEditingAllowed($user)) {
            $this->addFlashMessage(
                $this->getModuleLabel('users.userAccountEditingDenied.body', [$user->getName()->getFullName()]),
                $this->getModuleLabel('users.userAccountEditingDenied.title'),
                Message::SEVERITY_ERROR,
                [],
                1416225565
            );
            $this->redirect('index');
        }
        if ($user === $this->currentUser) {
            $roles = [];
            foreach ($roleIdentifiers as $roleIdentifier) {
                $roles[$roleIdentifier] = $this->policyService->getRole($roleIdentifier);
            }
            if (!$this->privilegeManager->isPrivilegeTargetGrantedForRoles(
                $roles,
                'Neos.Neos:Backend.Module.Administration.Users'
            )) {
                $this->addFlashMessage(
                    $this->getModuleLabel('users.doNotLockYourselfOut.body'),
                    $this->getModuleLabel('users.doNotLockYourselfOut.title'),
                    Message::SEVERITY_WARNING,
                    [],
                    1416501197
                );
                $this->forward('edit', null, null, ['user' => $this->currentUser]);
            }
        }
        $password = array_shift($password);
        if (strlen(trim(strval($password))) > 0) {
            $this->userService->setUserPassword($user, $password);
        }

        $this->userService->setRolesForAccount($account, $roleIdentifiers);
        $this->addFlashMessage(
            $this->getModuleLabel('users.accountUpdated.body'),
            $this->getModuleLabel('users.accountUpdated.title'),
            Message::SEVERITY_OK
        );
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
        if (!$this->isEditingAllowed($user)) {
            $this->addFlashMessage(
                $this->getModuleLabel(
                    'users.userEmailEditingDenied.body',
                    [$user->getName()->getFullName()]
                ),
                $this->getModuleLabel('users.userEmailEditingDenied.title'),
                Message::SEVERITY_ERROR,
                [],
                1416225566
            );
            $this->redirect('index');
        }
        /** @var User $user */
        $user->addElectronicAddress($electronicAddress);
        $this->userService->updateUser($user);

        $this->addFlashMessage(
            $this->getModuleLabel(
                'users.electronicAddressAdded.body',
                [
                    htmlspecialchars($electronicAddress->getIdentifier()),
                    htmlspecialchars($electronicAddress->getType())
                ]
            ),
            $this->getModuleLabel('users.electronicAddressAdded.title'),
            Message::SEVERITY_OK,
            [],
            1412374814
        );
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
        if (!$this->isEditingAllowed($user)) {
            $this->addFlashMessage(
                $this->getModuleLabel(
                    'users.userEmailDeletionDenied.body',
                    [$user->getName()->getFullName()]
                ),
                $this->getModuleLabel('users.userEmailDeletionDenied.title'),
                Message::SEVERITY_ERROR,
                [],
                1416225567
            );
            $this->redirect('index');
        }
        $user->removeElectronicAddress($electronicAddress);
        $this->userService->updateUser($user);

        $this->addFlashMessage(
            $this->getModuleLabel(
                'users.electronicAddressRemoved.body',
                [
                    htmlspecialchars($electronicAddress->getIdentifier()),
                    htmlspecialchars($electronicAddress->getType()),
                    htmlspecialchars($user->getName()->getFullName())
                ]
            ),
            $this->getModuleLabel('users.electronicAddressRemoved.title'),
            Message::SEVERITY_NOTICE,
            [],
            1412374678
        );
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
            $electronicAddressUsageTypes[$type] = $translationHelper->translate(
                'users.electronicAddress.usage.type.' . $type,
                $type,
                [],
                'Modules',
                'Neos.Neos'
            );
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
     * @return array<string,array<string,mixed>>
     */
    protected function getAuthenticationProviders(): array
    {
        $providers = array_keys($this->tokenAndProviderFactory->getProviders());

        $providerNames =[];
        foreach ($providers as $authenticationProviderName) {
            $providerNames[$authenticationProviderName] = [
                'label' => $this->authenticationProviderSettings[$authenticationProviderName]['label']
                    ?? $authenticationProviderName,
                'identifier' => $authenticationProviderName
            ];
        }

        sort($providerNames);
        return $providerNames;
    }

    /**
     * Returns the roles that the current editor is able to assign
     * Administrator can assign any roles, other users can only assign their own roles
     *
     * @return array<string,Role>
     * @throws NoSuchRoleException
     * @throws \Neos\Flow\Security\Exception
     */
    protected function getAllowedRoles(): array
    {
        $currentUserRoles = $this->userService->getAllRoles($this->currentUser);
        $currentUserRoles = array_filter($currentUserRoles, static function (Role $role) {
            return $role->isAbstract() !== true;
        });

        $roles = $this->userService->currentUserIsAdministrator()
            ? $this->policyService->getRoles()
            : $currentUserRoles;

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
     */
    protected function isEditingAllowed(User $user): bool
    {
        if ($this->userService->currentUserIsAdministrator()) {
            return true;
        }

        $currentUserRoles = $this->userService->getAllRoles($this->currentUser);
        $userRoles = $this->userService->getAllRoles($user);
        return count(array_diff($userRoles, $currentUserRoles)) === 0;
    }
}
