<?php
namespace TYPO3\Media\Validator;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * Validator that checks the orientation (square, portrait, landscape) of a given image.
 *
 * Supported validator options are (array)allowedOrientations with one or two out of 'square', 'landcape' or 'portrait'.
 *
 * *Example*::
 *
 *   [at]Flow\Validate("$image", type="\TYPO3\Media\Validator\ImageOrientationValidator",
 *         options={ "allowedOrientations"={"square", "landscape"} })
 *
 * this would refuse an image that is in portrait orientation, but allow landscape and square ones.
 */
class ImageOrientationValidator extends \TYPO3\Flow\Validation\Validator\AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = array(
        'allowedOrientations' => array(array(), 'Array of image orientations, one or two out of \'square\', \'landcape\' or \'portrait\'', 'array', true)
    );

    /**
     * The given $value is valid if it is an \TYPO3\Media\Domain\Model\ImageInterface of the
     * configured orientation (square, portrait and/or landscape)
     * Note: a value of NULL or empty string ('') is considered valid
     *
     * @param \TYPO3\Media\Domain\Model\ImageInterface $image The image that should be validated
     * @return void
     * @api
     */
    protected function isValid($image)
    {
        $this->validateOptions();
        if (!$image instanceof \TYPO3\Media\Domain\Model\ImageInterface) {
            $this->addError('The given value was not an Image instance.', 1328028604);
            return;
        }
        if (!in_array($image->getOrientation(), $this->options['allowedOrientations'])) {
            if (count($this->options['allowedOrientations']) === 1) {
                reset($this->options['allowedOrientations']);
                $allowedOrientation = current($this->options['allowedOrientations']);
                $this->addError('The image orientation must be "%s".', 1328029406, array($allowedOrientation));
            } else {
                $this->addError('The image orientation "%s" is not allowed.', 1328029362, array($image->getOrientation()));
            }
        }
    }

    /**
     * @return void
     * @throws \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException if the configured validation options are incorrect
     */
    protected function validateOptions()
    {
        if (!isset($this->options['allowedOrientations'])) {
            throw new \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException('The option "allowedOrientations" was not specified.', 1328028795);
        } elseif (!is_array($this->options['allowedOrientations']) || $this->options['allowedOrientations'] === array()) {
            throw new \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException('The option "allowedOrientations" must be an array with at least one element of "square", "portrait" or "landscape".', 1328028798);
        }
        foreach ($this->options['allowedOrientations'] as $orientation) {
            if ($orientation !== \TYPO3\Media\Domain\Model\ImageInterface::ORIENTATION_LANDSCAPE
                && $orientation !== \TYPO3\Media\Domain\Model\ImageInterface::ORIENTATION_PORTRAIT
                && $orientation !== \TYPO3\Media\Domain\Model\ImageInterface::ORIENTATION_SQUARE) {
                throw new \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException(sprintf('The option "allowedOrientations" contains an invalid orientation "%s".', $orientation), 1328029114);
            }
        }
        if (count($this->options['allowedOrientations']) === 3) {
            throw new \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException('The option "allowedOrientations" must contain at most two elements of "square", "portrait" or "landscape".', 1328029781);
        }
    }
}
