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
 * Validator that checks the type of a given image
 *
 * Example:
 * [at]Flow\Validate("$image", type="\TYPO3\Media\Validator\ImageTypeValidator", options={ "allowedTypes"={"jpeg", "png"} })
 */
class ImageTypeValidator extends \TYPO3\Flow\Validation\Validator\AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = array(
        'allowedTypes' => array(null, 'Allowed image types (using the IMAGETYPE_* constants)', 'array', true)
    );

    /**
     * The given $value is valid if it is an \TYPO3\Media\Domain\Model\ImageInterface of the
     * configured type (one or more of PHPs IMAGETYPE_* constants)
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
            $this->addError('The given value was not an Image instance.', 1327947256);
            return;
        }
        $allowedImageTypes = $this->parseAllowedImageTypes();
        if (!in_array($image->getType(), $allowedImageTypes)) {
            $imageExtension = image_type_to_extension($image->getType(), false);
            if ($imageExtension !== false) {
                $this->addError('The image type "%s" is not allowed.', 1327947647, array($imageExtension));
            } else {
                $this->addError('The uploaded file is no valid image.', 1328030664);
            }
        }
    }

    /**
     * @return void
     * @throws \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException if the configured validation options are incorrect
     */
    protected function validateOptions()
    {
        if (!isset($this->options['allowedTypes'])) {
            throw new \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException('The option "allowedTypes" was not specified.', 1327947194);
        } elseif (!is_array($this->options['allowedTypes']) || $this->options['allowedTypes'] === array()) {
            throw new \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException('The option "allowedTypes" must be an array with at least one item.', 1327947224);
        }
    }

    /**
     * @return array
     * @throws \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException if the allowedTypes option contain an invalid image type
     */
    protected function parseAllowedImageTypes()
    {
        $allowedImageTypes = array();
        foreach ($this->options['allowedTypes'] as $type) {
            $constantName = 'IMAGETYPE_' . strtoupper($type);
            if (defined($constantName) !== true) {
                throw new \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException(sprintf('The option "allowedTypes" contain an invalid image type "%s".', $type), 1319809048);
            }
            $allowedImageTypes[] = constant($constantName);
        }
        return $allowedImageTypes;
    }
}
