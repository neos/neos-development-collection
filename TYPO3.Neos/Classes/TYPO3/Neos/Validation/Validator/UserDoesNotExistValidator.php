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
use TYPO3\Flow\Validation\Exception\InvalidSubjectException;
use TYPO3\Flow\Validation\Validator\AbstractValidator;
use TYPO3\Neos\Domain\Service\UserService;

/**
 * Validator for Neos users
 */
class UserDoesNotExistValidator extends AbstractValidator {

	/**
	 * @Flow\Inject
	 * @var UserService
	 */
	protected $userService;

	/**
	 * Returns TRUE, if the specified user ($value) does not exist yet.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @throws InvalidSubjectException
	 */
	protected function isValid($value) {
		if (!is_string($value)) {
			throw new InvalidSubjectException('The given username was not a string.', 1325155784);
		}

		if ($this->userService->getUser($value) !== NULL) {
			$this->addError('The username is already in use.', 1325156008);
		}
	}

}
