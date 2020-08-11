<?php
namespace Neos\Media\Validator;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Validation\Exception\InvalidValidationOptionsException;
use Neos\Flow\Validation\Validator\AbstractValidator;
use Neos\Media\Domain\Model\ImageInterface;

/**
 * Validator that checks size (resolution) of a given image
 *
 * Example:
 * [at]Flow\Validate("$image", type="\Neos\Media\Validator\ImageSizeValidator", options={ "minimumWidth"=150, "maximumResolution"=60000 })
 */
class ImageSizeValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'minimumWidth' => [null, 'The minimum width of the image', 'integer'],
        'minimumHeight' => [null, 'The minimum height of the image', 'integer'],
        'maximumWidth' => [null, 'The maximum width of the image', 'integer'],
        'maximumHeight' => [null, 'The maximum height of the image', 'integer'],
        'minimumResolution' => [null, 'The minimum resolution of the image', 'integer'],
        'maximumResolution' => [null, 'The maximum resolution of the image', 'integer']
    ];

    /**
     * The given $value is valid if it is an \Neos\Media\Domain\Model\ImageInterface of the configured resolution
     * Note: a value of NULL or empty string ('') is considered valid
     *
     * @param ImageInterface $image The image that should be validated
     * @return void
     * @api
     */
    protected function isValid($image)
    {
        $this->validateOptions();

        if (!$image instanceof ImageInterface) {
            $this->addError('The given value was not an Image instance.', 1327943859);
            return;
        }
        if (isset($this->options['minimumWidth']) && $image->getWidth() < $this->options['minimumWidth']) {
            $this->addError('The actual image width of %1$d is lower than the allowed minimum width of %2$d.', 1319801362, [$image->getWidth(), $this->options['minimumWidth']]);
        } elseif (isset($this->options['maximumWidth']) && $image->getWidth() > $this->options['maximumWidth']) {
            $this->addError('The actual image width of %1$d is higher than the allowed maximum width of %2$d.', 1319801859, [$image->getWidth(), $this->options['maximumWidth']]);
        }
        if (isset($this->options['minimumHeight']) && $image->getHeight() < $this->options['minimumHeight']) {
            $this->addError('The actual image height of %1$d is lower than the allowed minimum height of %2$d.', 1319801925, [$image->getHeight(), $this->options['minimumHeight']]);
        } elseif (isset($this->options['maximumHeight']) && $image->getHeight() > $this->options['maximumHeight']) {
            $this->addError('The actual image height of %1$d is higher than the allowed maximum height of %2$d.', 1319801929, [$image->getHeight(), $this->options['maximumHeight']]);
        }

        if (isset($this->options['minimumResolution']) || isset($this->options['maximumResolution'])) {
            $resolution = $image->getWidth() * $image->getHeight();
            if (isset($this->options['minimumResolution']) && $resolution < $this->options['minimumResolution']) {
                $this->addError('The given image size of %1$d x %2$d is too low for the required minimum resolution of %3$d.', 1319813336, [$image->getHeight(), $image->getHeight(), $this->options['minimumResolution']]);
            } elseif (isset($this->options['maximumResolution']) && $resolution > $this->options['maximumResolution']) {
                $this->addError('The given image size of %1$d x %2$d is too high for the required maximum resolution of %3$d.', 1319813355, [$image->getHeight(), $image->getHeight(), $this->options['maximumResolution']]);
            }
        }
    }

    /**
     * @return void
     * @throws InvalidValidationOptionsException if the configured validation options are incorrect
     */
    protected function validateOptions()
    {
        if (!isset($this->options['minimumWidth'])
            && !isset($this->options['maximumWidth'])
            && !isset($this->options['minimumHeight'])
            && !isset($this->options['maximumHeight'])
            && !isset($this->options['minimumResolution'])
            && !isset($this->options['maximumResolution'])) {
            throw new InvalidValidationOptionsException('At least one of the options "minimumWidth", "maximumWidth", "minimumHeight", "maximumHeight", "minimumResolution" or "maximumResolution" must be specified.', 1328026094);
        }
        if (isset($this->options['minimumWidth']) && isset($this->options['maximumWidth'])
            && $this->options['minimumWidth'] > $this->options['maximumWidth']) {
            throw new InvalidValidationOptionsException('The option "minimumWidth" must not be greater than "maximumWidth".', 1327946137);
        } elseif (isset($this->options['minimumHeight']) && isset($this->options['maximumHeight'])
            && $this->options['minimumHeight'] > $this->options['maximumHeight']) {
            throw new InvalidValidationOptionsException('The option "minimumHeight" must not be greater than "maximumHeight".', 1327946156);
        } elseif (isset($this->options['minimumResolution']) && isset($this->options['maximumResolution'])
            && $this->options['minimumResolution'] > $this->options['maximumResolution']) {
            throw new InvalidValidationOptionsException('The option "minimumResolution" must not be greater than "maximumResolution".', 1327946274);
        }
    }
}
