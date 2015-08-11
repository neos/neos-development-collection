<?php
namespace TYPO3\Neos\ViewHelpers\Backend;

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
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Renders a label for the given authentication provider identifier
 */
class AuthenticationProviderLabelViewHelper extends AbstractViewHelper {

	/**
	 * @Flow\InjectConfiguration(package="TYPO3.Flow", path="security.authentication.providers")
	 * @var array
	 */
	protected $authenticationProviderSettings;

	/**
	 * Outputs a human friendly label for the authentication provider specified by $identifier
	 *
	 * @param string $identifier
	 * @return string
	 * @throws \Exception
	 */
	public function render($identifier) {
		return (isset($this->authenticationProviderSettings[$identifier]['label']) ? $this->authenticationProviderSettings[$identifier]['label'] : $identifier);
	}
}