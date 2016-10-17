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
use TYPO3\Flow\Validation\Exception\InvalidSubjectException;
use TYPO3\Flow\Validation\Validator\AbstractValidator;
use TYPO3\Flow\Validation\Validator\NotEmptyValidator;
use TYPO3\Flow\Validation\Validator\StringLengthValidator;

/**
 * Validator for passwords
 */
class PasswordValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = array(
        'allowEmpty' => array(false, 'Whether an empty password is allowed or not', 'boolean'),
        'minimum' => array(0, 'Minimum length for a valid string', 'integer'),
        'maximum' => array(PHP_INT_MAX, 'Maximum length for a valid string', 'integer')
    );

    /**
     * Returns TRUE, if the given property ($value) is a valid array consistent of two equal passwords and their length
     * is between 'minimum' (defaults to 0 if not specified) and 'maximum' (defaults to infinite if not specified)
     * to be specified in the validation options.
     *
     * If at least one error occurred, the result is FALSE.
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @throws InvalidSubjectException
     */
    protected function isValid($value)
    {
        if (!is_array($value)) {
            throw new InvalidSubjectException('The given value was not an array.', 1324641197);
        }

        $password = trim(strval(array_shift($value)));
        $repeatPassword = trim(strval(array_shift($value)));

        $passwordNotEmptyValidator = new NotEmptyValidator;
        $passwordNotEmptyValidatorResult = $passwordNotEmptyValidator->validate($password);
        $repeatPasswordNotEmptyValidator = new NotEmptyValidator;
        $repeatPasswordNotEmptyValidatorResult = $repeatPasswordNotEmptyValidator->validate($repeatPassword);

        if (($passwordNotEmptyValidatorResult->hasErrors() === true) && ($repeatPasswordNotEmptyValidatorResult->hasErrors() === true)) {
            if (!isset($this->options['allowEmpty']) || isset($this->options['allowEmpty']) && intval($this->options['allowEmpty']) === 0) {
                $this->addError('The given password was empty.', 1324641097);
            }
            return;
        }

        if (strcmp($password, $repeatPassword) !== 0) {
            $this->addError('The passwords did not match.', 1324640997);
            return;
        }

        $stringLengthValidator = new StringLengthValidator(array(
            'minimum' => $this->options['minimum'],
            'maximum' => $this->options['maximum'],
        ));
        $stringLengthValidatorResult = $stringLengthValidator->validate($password);

        if ($stringLengthValidatorResult->hasErrors() === true) {
            foreach ($stringLengthValidatorResult->getErrors() as $error) {
                $this->result->addError($error);
            }
        }
    }
}
