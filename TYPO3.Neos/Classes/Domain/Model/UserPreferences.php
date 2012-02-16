<?php
namespace TYPO3\TYPO3\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A preferences container for a user.
 *
 * This is a very naïve, rough and temporary implementation of a User Preferences container.
 * We'll need a better one which understands which options are available and contains some
 * information about possible help texts etc.
 *
 * @FLOW3\Entity
 * @FLOW3\Scope("prototype")
 * @todo Provide a more capable implementation
 */
class UserPreferences {

	/**
	 * The actual settings
	 *
	 * @var array<string>
	 */
	protected $preferences = array();

	/**
	 * @param mixed $key
	 * @param mixed $value
	 * @return void
	 */
	public function set($key, $value) {
		$this->preferences[$key] = $value;
	}

	/**
	 * @param mixed $key
	 * @return array|null
	 */
	public function get($key) {
		return isset($this->preferences[$key]) ? $this->preferences[$key] : NULL;
	}

}
?>