<?php
namespace TYPO3\Neos\Tests\Unit\Validation\Validator;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Neos\Validation\Validator\AccountExistsValidator;

/**
 * Testcase for the AccountExistsValidator
 *
 */
class AccountExistsValidatorTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     * @expectedException \TYPO3\Flow\Validation\Exception\InvalidSubjectException
     */
    public function validateThrowsExceptionForNonStringValue()
    {
        $validator = new AccountExistsValidator();
        $validator->validate(false);
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorsWithNullAccount()
    {
        $validator = new AccountExistsValidator();

        $mockAccountRepository = $this->getMock('TYPO3\Flow\Security\AccountRepository');
        $this->inject($validator, 'accountRepository', $mockAccountRepository);

        $result = $validator->validate('j.doe');

        $this->assertFalse($result->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsAnErrorWithExistingAccount()
    {
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
