<?php
namespace TYPO3\Neos\Tests\Unit\Validation\Validator;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Neos\Validation\Validator\UserDoesNotExistValidator;

/**
 * Test case for the UserDoesNotExistValidator
 *
 */
class UserDoesNotExistValidatorTest extends UnitTestCase {

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Validation\Exception\InvalidSubjectException
	 */
	public function validateThrowsExceptionForNonStringValue() {
		$validator = new UserDoesNotExistValidator();
		$validator->validate(FALSE);
	}

	/**
	 * @test
	 */
	public function validateReturnsNoErrorsWithNullAccount() {
		$validator = new UserDoesNotExistValidator();

		$mockUserService = $this->getMock('TYPO3\Neos\Domain\Service\UserService');
		$this->inject($validator, 'userService', $mockUserService);

		$result = $validator->validate('j.doe');

		$this->assertFalse($result->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsAnErrorWithExistingAccount() {
		$validator = new UserDoesNotExistValidator();

		$mockUserService = $this->getMock('TYPO3\Neos\Domain\Service\UserService');
		$this->inject($validator, 'userService', $mockUserService);

		$mockUser = $this->getMock('TYPO3\Flow\Security\Account');

		$mockUserService
			->expects($this->atLeastOnce())
			->method('getUser')
			->with('j.doe')
			->will($this->returnValue($mockUser));

		$result = $validator->validate('j.doe');

		$this->assertTrue($result->hasErrors());
	}

}
