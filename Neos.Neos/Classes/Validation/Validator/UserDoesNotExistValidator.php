<?php
namespace Neos\Neos\Validation\Validator;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Validation\Exception\InvalidSubjectException;
use Neos\Flow\Validation\Validator\AbstractValidator;
use Neos\Neos\Domain\Service\UserService;

/**
 * Validator for Neos users
 */
class UserDoesNotExistValidator extends AbstractValidator
{
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
    protected function isValid($value)
    {
        if (!is_string($value)) {
            throw new InvalidSubjectException('The given username was not a string.', 1325155784);
        }

        if ($this->userService->getUser($value) !== null) {
            $this->addError('The username is already in use.', 1325156008);
        }
    }
}
