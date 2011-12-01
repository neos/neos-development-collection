<?php
namespace TYPO3\TYPO3\Validation\Validator;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Validator for accounts
 */
class AccountExistsValidator extends \TYPO3\FLOW3\Validation\Validator\AbstractValidator {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\AccountRepository
	 */
	protected $accountRepository;

	/**
	 * Returns TRUE, if the given property ($value) is a valid array consistent of two equal passwords and their length
	 * is between 'minimum' (defaults to 0 if not specified) and 'maximum' (defaults to infinite if not specified)
	 * to be specified in the validation options.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @throws TYPO3\FLOW3\Validation\Exception\InvalidSubjectException
	 */
	protected function isValid($value) {
		if (!is_string($value)) {
			throw new \TYPO3\FLOW3\Validation\Exception\InvalidSubjectException('The given value was not a string.', 1325155784);
		}

		$authenticationProviderName = isset($this->options['authenticationProviderName']) ? $this->options['authenticationProviderName'] : 'Typo3BackendProvider';

		$account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($value, $authenticationProviderName);

		if ($account !== NULL) {
			$this->addError('The username is already in use.', 1325156008);
		}
	}

}
?>