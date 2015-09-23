<?php
namespace TYPO3\Media\Validator;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Validator that checks size (resolution) of a given image
 *
 * Example:
 * [at]Flow\Validate("$image", type="\TYPO3\Media\Validator\ImageSizeValidator", options={ "minimumWidth"=150, "maximumResolution"=60000 })
 */
class ImageSizeValidator extends \TYPO3\Flow\Validation\Validator\AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = array(
        'minimumWidth' => array(null, 'The minimum width of the image', 'integer'),
        'minimumHeight' => array(null, 'The minimum height of the image', 'integer'),
        'maximumWidth' => array(null, 'The maximum width of the image', 'integer'),
        'maximumHeight' => array(null, 'The maximum height of the image', 'integer'),
        'minimumResolution' => array(null, 'The minimum resolution of the image', 'integer'),
        'maximumResolution' => array(null, 'The maximum resolution of the image', 'integer')
    );

    /**
     * The given $value is valid if it is an \TYPO3\Media\Domain\Model\ImageInterface of the configured resolution
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
            $this->addError('The given value was not an Image instance.', 1327943859);
            return;
        }
        if (isset($this->options['minimumWidth']) && $image->getWidth() < $this->options['minimumWidth']) {
            $this->addError('The actual image width of %1$d is lower than the allowed minimum width of %2$d.', 1319801362, array($image->getWidth(), $this->options['minimumWidth']));
        } elseif (isset($this->options['maximumWidth']) && $image->getWidth() > $this->options['maximumWidth']) {
            $this->addError('The actual image width of %1$d is higher than the allowed maximum width of %2$d.', 1319801859, array($image->getWidth(), $this->options['maximumWidth']));
        }
        if (isset($this->options['minimumHeight']) && $image->getHeight() < $this->options['minimumHeight']) {
            $this->addError('The actual image height of %1$d is lower than the allowed minimum height of %2$d.', 1319801925, array($image->getHeight(), $this->options['minimumHeight']));
        } elseif (isset($this->options['maximumHeight']) && $image->getHeight() > $this->options['maximumHeight']) {
            $this->addError('The actual image height of %1$d is higher than the allowed maximum height of %2$d.', 1319801929, array($image->getHeight(), $this->options['maximumHeight']));
        }

        if (isset($this->options['minimumResolution']) || isset($this->options['maximumResolution'])) {
            $resolution = $image->getWidth() * $image->getHeight();
            if (isset($this->options['minimumResolution']) && $resolution < $this->options['minimumResolution']) {
                $this->addError('The given image size of %1$d x %2$d is too low for the required minimum resolution of %3$d.', 1319813336, array($image->getHeight(), $image->getHeight(), $this->options['minimumResolution']));
            } elseif (isset($this->options['maximumResolution']) && $resolution > $this->options['maximumResolution']) {
                $this->addError('The given image size of %1$d x %2$d is too high for the required maximum resolution of %3$d.', 1319813355, array($image->getHeight(), $image->getHeight(), $this->options['maximumResolution']));
            }
        }
    }

    /**
     * @return void
     * @throws \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException if the configured validation options are incorrect
     */
    protected function validateOptions()
    {
        if (!isset($this->options['minimumWidth'])
            && !isset($this->options['maximumWidth'])
            && !isset($this->options['minimumHeight'])
            && !isset($this->options['maximumHeight'])
            && !isset($this->options['minimumResolution'])
            && !isset($this->options['maximumResolution'])) {
            throw new \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException('At least one of the options "minimumWidth", "maximumWidth", "minimumHeight", "maximumHeight", "minimumResolution" or "maximumResolution" must be specified.', 1328026094);
        }
        if (isset($this->options['minimumWidth']) && isset($this->options['maximumWidth'])
            && $this->options['minimumWidth'] > $this->options['maximumWidth']) {
            throw new \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException('The option "minimumWidth" must not be greater than "maximumWidth".', 1327946137);
        } elseif (isset($this->options['minimumHeight']) && isset($this->options['maximumHeight'])
            && $this->options['minimumHeight'] > $this->options['maximumHeight']) {
            throw new \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException('The option "minimumHeight" must not be greater than "maximumHeight".', 1327946156);
        } elseif (isset($this->options['minimumResolution']) && isset($this->options['maximumResolution'])
            && $this->options['minimumResolution'] > $this->options['maximumResolution']) {
            throw new \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException('The option "minimumResolution" must not be greater than "maximumResolution".', 1327946274);
        }
    }
}
