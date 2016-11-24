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

use Neos\Flow\Security\Account;
use Neos\Flow\Tests\UnitTestCase;
use TYPO3\Neos\Domain\Service\UserService;
use TYPO3\Neos\Validation\Validator\UserDoesNotExistValidator;

/**
 * Test case for the UserDoesNotExistValidator
 *
 */
class UserDoesNotExistValidatorTest extends UnitTestCase
{
    /**
     * @test
     * @expectedException \Neos\Flow\Validation\Exception\InvalidSubjectException
     */
    public function validateThrowsExceptionForNonStringValue()
    {
        $validator = new UserDoesNotExistValidator();
        $validator->validate(false);
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorsWithNullAccount()
    {
        $validator = new UserDoesNotExistValidator();

        $mockUserService = $this->createMock(UserService::class);
        $this->inject($validator, 'userService', $mockUserService);

        $result = $validator->validate('j.doe');

        $this->assertFalse($result->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsAnErrorWithExistingAccount()
    {
        $validator = new UserDoesNotExistValidator();

        $mockUserService = $this->createMock(UserService::class);
        $this->inject($validator, 'userService', $mockUserService);

        $mockUser = $this->createMock(Account::class);

        $mockUserService
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->with('j.doe')
            ->will($this->returnValue($mockUser));

        $result = $validator->validate('j.doe');

        $this->assertTrue($result->hasErrors());
    }
}
