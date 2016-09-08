<?php
namespace TYPO3\Neos\Setup\Step;

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
use TYPO3\Flow\Security\AccountRepository;
use TYPO3\Flow\Validation\Validator\NotEmptyValidator;
use TYPO3\Flow\Validation\Validator\StringLengthValidator;
use TYPO3\Form\Core\Model\FormDefinition;
use TYPO3\Neos\Domain\Service\UserService;
use TYPO3\Neos\Validation\Validator\UserDoesNotExistValidator;
use TYPO3\Party\Domain\Repository\PartyRepository;
use TYPO3\Setup\Step\AbstractStep;

/**
 * @Flow\Scope("singleton")
 */
class AdministratorStep extends AbstractStep
{
    /**
     * @var boolean
     */
    protected $optional = true;

    /**
     * @Flow\Inject
     * @var AccountRepository
     */
    protected $accountRepository;

    /**
     * @Flow\Inject
     * @var PartyRepository
     */
    protected $partyRepository;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * Returns the form definitions for the step
     *
     * @param FormDefinition $formDefinition
     * @return void
     */
    protected function buildForm(FormDefinition $formDefinition)
    {
        $page1 = $formDefinition->createPage('page1');
        $page1->setRenderingOption('header', 'Create administrator account');

        $introduction = $page1->createElement('introduction', 'TYPO3.Form:StaticText');
        $introduction->setProperty('text', 'Enter the personal data and credentials for your backend account:');

        $personalSection = $page1->createElement('personalSection', 'TYPO3.Form:Section');
        $personalSection->setLabel('Personal Data');

        $firstName = $personalSection->createElement('firstName', 'TYPO3.Form:SingleLineText');
        $firstName->setLabel('First name');
        $firstName->addValidator(new NotEmptyValidator());
        $firstName->addValidator(new StringLengthValidator(array('minimum' => 1, 'maximum' => 255)));

        $lastName = $personalSection->createElement('lastName', 'TYPO3.Form:SingleLineText');
        $lastName->setLabel('Last name');
        $lastName->addValidator(new NotEmptyValidator());
        $lastName->addValidator(new StringLengthValidator(array('minimum' => 1, 'maximum' => 255)));

        $credentialsSection = $page1->createElement('credentialsSection', 'TYPO3.Form:Section');
        $credentialsSection->setLabel('Credentials');

        $username = $credentialsSection->createElement('username', 'TYPO3.Form:SingleLineText');
        $username->setLabel('Username');
        $username->addValidator(new NotEmptyValidator());
        $username->addValidator(new UserDoesNotExistValidator());

        $password = $credentialsSection->createElement('password', 'TYPO3.Form:PasswordWithConfirmation');
        $password->addValidator(new NotEmptyValidator());
        $password->addValidator(new StringLengthValidator(array('minimum' => 6, 'maximum' => 255)));
        $password->setLabel('Password');
        $password->setProperty('passwordDescription', 'At least 6 characters');

        $formDefinition->setRenderingOption('skipStepNotice', 'If you skip this step make sure that you have an existing user or create one with the user:create command');
    }

    /**
     * This method is called when the form of this step has been submitted
     *
     * @param array $formValues
     * @return void
     */
    public function postProcessFormValues(array $formValues)
    {
        $this->userService->createUser($formValues['username'], $formValues['password'], $formValues['firstName'], $formValues['lastName'], array('TYPO3.Neos:Administrator'));
    }
}
