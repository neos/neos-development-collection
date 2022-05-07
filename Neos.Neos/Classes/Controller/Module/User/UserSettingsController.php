<?php
namespace Neos\Neos\Controller\Module\User;

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
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Controller\Module\ModuleTranslationTrait;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Service\UserService;
use Neos\Party\Domain\Model\ElectronicAddress;

/**
 * The Neos User Settings module controller
 *
 * @Flow\Scope("singleton")
 */
class UserSettingsController extends AbstractModuleController
{
    use ModuleTranslationTrait;

    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $domainUserService;

    protected ?User $currentUser;

    /**
     * @Flow\Inject
     * @var Translator
     */
    protected $translator;

    /**
     * @return void
     */
    protected function initializeAction()
    {
        parent::initializeAction();
        $translationHelper = new TranslationHelper();
        $this->setTitle(
            $translationHelper->translate($this->moduleConfiguration['label'])
                . ' :: ' . $translationHelper->translate(
                    str_replace('label', 'action.', $this->moduleConfiguration['label'])
                        . $this->request->getControllerActionName()
                )
        );
        if ($this->arguments->hasArgument('user')) {
            $propertyMappingConfigurationForUser = $this->arguments->getArgument('user')
                ->getPropertyMappingConfiguration();
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
                    PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED,
                    true
                );
            }
        }
        $this->currentUser = $this->domainUserService->getCurrentUser();
    }

    /**
     * Index
     *
     * @return void
     */
    public function indexAction()
    {
        $this->forward('edit');
    }

    /**
     * Edit settings of the current user
     *
     * @return void
     */
    public function editAction()
    {
        $this->assignElectronicAddressOptions();

        $this->view->assignMultiple([
            'user' => $this->currentUser
        ]);
    }

    /**
     * Update the current user
     *
     * @param User $user The user to update, including updated data already (name, email address etc)
     * @return void
     */
    public function updateAction(User $user)
    {
        $this->domainUserService->updateUser($user);
        $this->addFlashMessage(
            $this->getModuleLabel('userSettings.UserUpdated.body'),
            $this->getModuleLabel('userSettings.UserUpdated.title'),
            Message::SEVERITY_OK
        );
        $this->redirect('edit');
    }

    /**
     * Edit the given account
     *
     * @param Account $account
     * @return void
     */
    public function editAccountAction(Account $account)
    {
        $this->view->assignMultiple([
            'account' => $account,
            'user' => $this->domainUserService->getUser(
                $account->getAccountIdentifier(),
                $account->getAuthenticationProviderName()
            )
        ]);
    }

    /**
     * Update a given account, ie. the password
     *
     * @param array<int,string> $password Expects an array in the format array('<password>', '<password confirmation>')
     * @codingStandardsIgnoreStart
     * @Flow\Validate(argumentName="password", type="\Neos\Neos\Validation\Validator\PasswordValidator", options={ "allowEmpty"=1, "minimum"=1, "maximum"=255 })
     * @codingStandardsIgnoreEnd
     * @return void
     */
    public function updateAccountAction(array $password = [])
    {
        $user = $this->currentUser;
        $password = array_shift($password);
        if (strlen(trim(strval($password))) > 0) {
            $this->domainUserService->setUserPassword($user, $password);
            $this->addFlashMessage(
                $this->getModuleLabel('userSettings.passwordUpdated.body'),
                $this->getModuleLabel('userSettings.passwordUpdated.title'),
                Message::SEVERITY_OK
            );
        }
        $this->redirect('index');
    }

    /**
     * The add new electronic address action
     *
     * @param User $user
     * @Flow\IgnoreValidation("$user")
     * @return void
     */
    public function newElectronicAddressAction(User $user)
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
     */
    public function createElectronicAddressAction(User $user, ElectronicAddress $electronicAddress): void
    {
        $user->addElectronicAddress($electronicAddress);
        $this->domainUserService->updateUser($user);

        $this->addFlashMessage(
            $this->getModuleLabel(
                'userSettings.electronicAddressAdded.body',
                [
                    htmlspecialchars($electronicAddress->getIdentifier()),
                    htmlspecialchars($electronicAddress->getType())
                ]
            ),
            $this->getModuleLabel('userSettings.electronicAddressAdded.title'),
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
     */
    public function deleteElectronicAddressAction(User $user, ElectronicAddress $electronicAddress)
    {
        $user->removeElectronicAddress($electronicAddress);
        $this->domainUserService->updateUser($user);

        $this->addFlashMessage(
            $this->getModuleLabel(
                'userSettings.electronicAddressRemoved.body',
                [
                    htmlspecialchars($electronicAddress->getIdentifier()),
                    htmlspecialchars($electronicAddress->getType()),
                    htmlspecialchars($user->getName())
                ]
            ),
            $this->getModuleLabel('userSettings.electronicAddressRemoved.title'),
            Message::SEVERITY_NOTICE,
            [],
            1412374678
        );
        $this->redirect('edit', null, null, ['user' => $user]);
    }

    /**
     *  @return void
     */
    protected function assignElectronicAddressOptions()
    {
        $electronicAddress = new ElectronicAddress();
        $electronicAddressTypes = [];
        foreach ($electronicAddress->getAvailableElectronicAddressTypes() as $type) {
            $electronicAddressTypes[$type] = $type;
        }
        $electronicAddressUsageTypes = [];
        foreach ($electronicAddress->getAvailableUsageTypes() as $type) {
            $electronicAddressUsageTypes[$type] = $type;
        }
        array_unshift($electronicAddressUsageTypes, '');
        $this->view->assignMultiple([
            'electronicAddressTypes' => $electronicAddressTypes,
            'electronicAddressUsageTypes' => $electronicAddressUsageTypes
        ]);
    }
}
