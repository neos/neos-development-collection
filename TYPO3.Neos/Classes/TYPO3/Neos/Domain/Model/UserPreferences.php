<?php
namespace TYPO3\Neos\Domain\Model;

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
 * A preferences container for a user.
 *
 * This is a very naïve, rough and temporary implementation of a User Preferences container.
 * We'll need a better one which understands which options are available and contains some
 * information about possible help texts etc.
 *
 * @Flow\Entity
 * @todo Provide a more capable implementation
 */
class UserPreferences {

	/**
	 * The actual settings
	 *
	 * @var array
	 */
	protected $preferences = array();

	/**
	 * Get preferences
	 *
	 * @return array
	 */
	public function getPreferences() {
		return $this->preferences;
	}

	/**
	 * @param array $preferences
	 * @return void
	 */
	public function setPreferences(array $preferences) {
		$this->preferences = $preferences;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function set($key, $value) {
		$this->preferences[$key] = $value;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {
		return isset($this->preferences[$key]) ? $this->preferences[$key] : NULL;
	}

	/**
	 * @param string $localeIdentifier
	 * @return void
	 */
	public function setInterfaceLanguage($localeIdentifier) {
		$this->set('interfaceLanguage', $localeIdentifier);
	}

	/**
	 * @return string the locale identifier
	 */
	public function getInterfaceLanguage() {
		return $this->get('interfaceLanguage');
	}

}
