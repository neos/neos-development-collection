<?php
namespace TYPO3\Neos\Validation\Validator;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Validation\Exception\InvalidSubjectException;
use TYPO3\Flow\Validation\Validator\AbstractValidator;
use TYPO3\Neos\Domain\Service\UserService;

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
