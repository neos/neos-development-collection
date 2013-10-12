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
 * Validator that checks the type of a given image
 *
 * Example:
 * [at]Flow\Validate("$image", type="\TYPO3\Media\Validator\ImageTypeValidator", options={ "allowedTypes"={"jpeg", "png"} })
 */
class ImageTypeValidator extends \TYPO3\Flow\Validation\Validator\AbstractValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'allowedTypes' => array(NULL, 'Allowed image types (using the IMAGETYPE_* constants)', 'array', TRUE)
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
	protected function isValid($image) {
		$this->validateOptions();
		if (!$image instanceof \TYPO3\Media\Domain\Model\ImageInterface) {
			$this->addError('The given value was not an Image instance.', 1327947256);
			return;
		}
		$allowedImageTypes = $this->parseAllowedImageTypes();
		if (!in_array($image->getType(), $allowedImageTypes)) {
			$imageExtension = image_type_to_extension($image->getType(), FALSE);
			if ($imageExtension !== FALSE) {
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
	protected function validateOptions() {
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
	protected function parseAllowedImageTypes() {
		$allowedImageTypes = array();
		foreach ($this->options['allowedTypes'] as $type) {
			$constantName = 'IMAGETYPE_' . strtoupper($type);
			if (defined($constantName) !== TRUE) {
				throw new \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException(sprintf('The option "allowedTypes" contain an invalid image type "%s".', $type), 1319809048);
			}
			$allowedImageTypes[] = constant($constantName);
		}
		return $allowedImageTypes;
	}
}
