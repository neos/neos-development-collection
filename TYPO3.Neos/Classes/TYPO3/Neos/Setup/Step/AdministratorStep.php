<?php
namespace TYPO3\Neos\Setup\Step;

/*                                                                        *
 * This script belongs to the Flow package "TYPO3.Neos".                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Validation\Validator\NotEmptyValidator;
use TYPO3\Flow\Validation\Validator\StringLengthValidator;
use TYPO3\Neos\Domain\Service\UserService;
use TYPO3\Neos\Validation\Validator\UserDoesNotExistValidator;

/**
 * @Flow\Scope("singleton")
 */
class AdministratorStep extends \TYPO3\Setup\Step\AbstractStep {

	/**
	 * @var boolean
	 */
	protected $optional = TRUE;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\AccountRepository
	 */
	protected $accountRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Party\Domain\Repository\PartyRepository
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
	 * @param \TYPO3\Form\Core\Model\FormDefinition $formDefinition
	 * @return void
	 */
	protected function buildForm(\TYPO3\Form\Core\Model\FormDefinition $formDefinition) {
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
	public function postProcessFormValues(array $formValues) {
		$this->userService->createUser($formValues['username'], $formValues['password'], $formValues['firstName'], $formValues['lastName'], array('TYPO3.Neos:Administrator'));
	}

}
