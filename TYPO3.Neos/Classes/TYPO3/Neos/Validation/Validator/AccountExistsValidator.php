<?php
namespace TYPO3\Neos\Validation\Validator;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Validator for accounts
 */
class AccountExistsValidator extends \TYPO3\Flow\Validation\Validator\AbstractValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'authenticationProviderName' => array('Typo3BackendProvider', 'The authentication provider to use when checking for an account', 'string')
	);

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\AccountRepository
	 */
	protected $accountRepository;

	/**
	 * Returns TRUE, if the given property ($value) does not yet exist in the account repository.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @throws \TYPO3\Flow\Validation\Exception\InvalidSubjectException
	 */
	protected function isValid($value) {
		if (!is_string($value)) {
			throw new \TYPO3\Flow\Validation\Exception\InvalidSubjectException('The given account identifier was not a string.', 1325155784);
		}

		$account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($value, $this->options['authenticationProviderName']);

		if ($account !== NULL) {
			$this->addError('The account identifier (username) is already in use.', 1325156008);
		}
	}

}
?>