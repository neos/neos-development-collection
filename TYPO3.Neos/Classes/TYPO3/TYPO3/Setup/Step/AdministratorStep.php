<?php
namespace TYPO3\TYPO3\Setup\Step;

/*                                                                        *
 * This script belongs to the Flow package "TYPO3.Setup".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow,
	TYPO3\Form\Core\Model\FormDefinition;

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
	 * @var \TYPO3\Flow\Security\AccountFactory
	 */
	protected $accountFactory;

	/**
	 * Returns the form definitions for the step
	 *
	 * @param \TYPO3\Form\Core\Model\FormDefinition $formDefinition
	 * @return void
	 */
	protected function buildForm(\TYPO3\Form\Core\Model\FormDefinition $formDefinition) {
		$page1 = $formDefinition->createPage('page1');

		$introduction = $page1->createElement('introduction', 'TYPO3.Form:StaticText');
		$introduction->setProperty('text', 'Create an administrator account:');

		$personalSection = $page1->createElement('personalSection', 'TYPO3.Form:Section');
		$personalSection->setLabel('Personal Data');

		$firstName = $personalSection->createElement('firstName', 'TYPO3.Form:SingleLineText');
		$firstName->setLabel('First name');
		$firstName->addValidator(new \TYPO3\Flow\Validation\Validator\NotEmptyValidator());
		$firstName->addValidator(new \TYPO3\Flow\Validation\Validator\StringLengthValidator(array('minimum' => 1, 'maximum' => 255)));

		$lastName = $personalSection->createElement('lastName', 'TYPO3.Form:SingleLineText');
		$lastName->setLabel('Last name');
		$lastName->addValidator(new \TYPO3\Flow\Validation\Validator\NotEmptyValidator());
		$lastName->addValidator(new \TYPO3\Flow\Validation\Validator\StringLengthValidator(array('minimum' => 1, 'maximum' => 255)));

		$credentialsSection = $page1->createElement('credentialsSection', 'TYPO3.Form:Section');
		$credentialsSection->setLabel('Credentials');

		$username = $credentialsSection->createElement('username', 'TYPO3.Form:SingleLineText');
		$username->setLabel('Username');
		$username->addValidator(new \TYPO3\Flow\Validation\Validator\NotEmptyValidator());
		$username->addValidator(new \TYPO3\Flow\Validation\Validator\AlphanumericValidator());
		$username->addValidator(new \TYPO3\TYPO3\Validation\Validator\AccountExistsValidator(array('authenticationProviderName' => 'Typo3BackendProvider')));

		$password = $credentialsSection->createElement('password', 'TYPO3.Form:PasswordWithConfirmation');
		$password->addValidator(new \TYPO3\Flow\Validation\Validator\NotEmptyValidator());
		$password->addValidator(new \TYPO3\Flow\Validation\Validator\StringLengthValidator(array('minimum' => 6, 'maximum' => 255)));
		$password->setLabel('Password');
	}

	/**
	 * This method is called when the form of this step has been submitted
	 *
	 * @param array $formValues
	 * @return void
	 */
	public function postProcessFormValues(array $formValues) {
		$user = new \TYPO3\TYPO3\Domain\Model\User();
		$name = new \TYPO3\Party\Domain\Model\PersonName('', $formValues['firstName'], '', $formValues['lastName'], '', $formValues['username']);
		$user->setName($name);
		$user->getPreferences()->set('context.workspace', 'user-' . $formValues['username']);
		$this->partyRepository->add($user);

		$account = $this->accountFactory->createAccountWithPassword($formValues['username'], $formValues['password'], array('Administrator'), 'Typo3BackendProvider');
		$account->setParty($user);
		$this->accountRepository->add($account);
	}

}
?>