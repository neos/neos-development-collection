<?php
namespace TYPO3\Neos\Validation\Validator;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Validator for http://tools.ietf.org/html/rfc1123 compatible host names
 */
class HostnameValidator extends \TYPO3\Flow\Validation\Validator\AbstractValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'ignoredHostnames' => array('', 'Hostnames that are not to be validated', 'string'),
	);

	/**
	 * Validates if the hostname is valid.
	 *
	 * @param mixed $hostname The hostname that should be validated
	 * @return void
	 */
	protected function isValid($hostname) {
		$pattern = '/(?=^.{4,253}$)(^((?!-)[a-zA-Z0-9-]{1,63}(?<!-)\.)*(?!-)[a-zA-Z]{2,63}(?<!-)$)/';

		if ($this->options['ignoredHostnames']) {
			$ignoredHostnames = explode(',', $this->options['ignoredHostnames']);
			if (in_array($hostname, $ignoredHostnames)) {
				return;
			}
		}

		if (!preg_match($pattern, $hostname)) {
			$this->addError('The hostname "%1$s" was not valid.', 1324641097, array($hostname));
		}
	}
}