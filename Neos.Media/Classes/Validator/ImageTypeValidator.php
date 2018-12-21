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
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageInterface;

/**
 * Validator that checks the type of a given image
 *
 * Example:
 * [at]Flow\Validate("$image", type="\Neos\Media\Validator\ImageTypeValidator", options={ "allowedTypes"={"jpeg", "png"} })
 */
class ImageTypeValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'allowedTypes' => [null, 'Allowed image types (using image/* IANA media subtypes)', 'array', true]
    ];

    /**
     * The given $value is valid if it is an \Neos\Media\Domain\Model\ImageInterface of the
     * configured type (one of the image/* IANA media subtypes)
     *
     * Note: a value of NULL or empty string ('') is considered valid
     *
     * @param ImageInterface $image The image that should be validated
     * @return void
     * @api
     */
    protected function isValid($image)
    {
        $this->validateOptions();

        if (!$image instanceof Image) {
            $this->addError('The given value was not an Image instance.', 1327947256);
            return;
        }
        $allowedImageTypes = $this->options['allowedTypes'];
        array_walk($allowedImageTypes, function (&$value) {
            $value = 'image/' . $value;
        });

        if (!in_array($image->getMediaType(), $allowedImageTypes)) {
            $this->addError('The media type "%s" is not allowed for this image.', 1327947647, [$image->getMediaType()]);
        }
    }

    /**
     * Checks if this validator is correctly configured
     *
     * @return void
     * @throws InvalidValidationOptionsException if the configured validation options are incorrect
     */
    protected function validateOptions()
    {
        if (!isset($this->options['allowedTypes'])) {
            throw new InvalidValidationOptionsException('The option "allowedTypes" was not specified.', 1327947194);
        } elseif (!is_array($this->options['allowedTypes']) || $this->options['allowedTypes'] === []) {
            throw new InvalidValidationOptionsException('The option "allowedTypes" must be an array with at least one item.', 1327947224);
        }
    }
}
