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

use TYPO3\Neos\Validation\Validator\AccountExistsValidator;

/**
 * Testcase for the AccountExistsValidator
 *
 */
class AccountExistsValidatorTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Validation\Exception\InvalidSubjectException
	 */
	public function validateThrowsExceptionForNonStringValue() {
		$validator = new AccountExistsValidator();
		$validator->validate(FALSE);
	}

	/**
	 * @test
	 */
	public function validateReturnsNoErrorsWithNullAccount() {
		$validator = new AccountExistsValidator();

		$mockAccountRepository = $this->getMock('TYPO3\Flow\Security\AccountRepository');
		$this->inject($validator, 'accountRepository', $mockAccountRepository);

		$result = $validator->validate('j.doe');

		$this->assertFalse($result->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsAnErrorWithExistingAccount() {
		$validator = new AccountExistsValidator();

		$mockAccountRepository = $this->getMock('TYPO3\Flow\Security\AccountRepository');
		$this->inject($validator, 'accountRepository', $mockAccountRepository);

		$mockAccount = $this->getMock('TYPO3\Flow\Security\Account');

		$mockAccountRepository
			->expects($this->atLeastOnce())
			->method('findByAccountIdentifierAndAuthenticationProviderName')
			->with('j.doe', $this->anything())
			->will($this->returnValue($mockAccount));

		$result = $validator->validate('j.doe');

		$this->assertTrue($result->hasErrors());
	}

}
