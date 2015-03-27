<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Domain\Fixtures;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A test class that has a property to wrap some value for serialization in Node properties
 */
class TestObjectForSerialization {

	/**
	 * @var object
	 */
	protected $value;

	/**
	 * @param object $value
	 */
	public function __construct($value) {
		$this->value = $value;
	}

	/**
	 * @return object
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * @return array
	 */
	public function __sleep() {
		return array('value');
	}

}
