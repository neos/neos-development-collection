<?php
namespace Neos\Neos\Tests\Unit\Validation\Validator;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Security\Account;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Validation\Exception\InvalidSubjectException;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Validation\Validator\UserDoesNotExistValidator;

/**
 * Test case for the UserDoesNotExistValidator
 *
 */
class UserDoesNotExistValidatorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function validateThrowsExceptionForNonStringValue()
    {
        $this->expectException(InvalidSubjectException::class);
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

        self::assertFalse($result->hasErrors());
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
            ->expects(self::atLeastOnce())
            ->method('getUser')
            ->with('j.doe')
            ->will(self::returnValue($mockUser));

        $result = $validator->validate('j.doe');

        self::assertTrue($result->hasErrors());
    }
}
