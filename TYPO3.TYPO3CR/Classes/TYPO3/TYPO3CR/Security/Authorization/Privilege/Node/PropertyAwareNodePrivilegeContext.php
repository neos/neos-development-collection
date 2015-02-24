<?php
namespace TYPO3\TYPO3CR\Security\Authorization\Privilege\Node;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * An Eel context matching expression for the node privileges including
 * node properties.
 *
 * @Flow\Proxy(false)
 */
class PropertyAwareNodePrivilegeContext extends NodePrivilegeContext {

	/**
	 * @var array
	 */
	protected $propertyNames = array();

	/**
	 * @param array $propertyNames
	 * @return boolean
	 */
	public function nodePropertyIsIn($propertyNames) {
		if (!is_array($this->propertyNames)) {
			$propertyNames = array($propertyNames);
		}
		$this->propertyNames = $propertyNames;
		return TRUE;
	}

	/**
	 * @return array
	 */
	public function getNodePropertyNames() {
		return $this->propertyNames;
	}
}