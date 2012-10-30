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
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\AccountRepository
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
	 * @throws \TYPO3\Flow\Validation\Exception\InvalidSubjectException
	 */
	protected function isValid($value) {
		if (!is_string($value)) {
			throw new \TYPO3\Flow\Validation\Exception\InvalidSubjectException('The given value was not a string.', 1325155784);
		}

		$authenticationProviderName = isset($this->options['authenticationProviderName']) ? $this->options['authenticationProviderName'] : 'Typo3BackendProvider';

		$account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($value, $authenticationProviderName);

		if ($account !== NULL) {
			$this->addError('The username is already in use.', 1325156008);
		}
	}

}
?>