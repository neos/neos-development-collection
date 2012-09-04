<?php
namespace TYPO3\TYPO3CR\Migration\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Migration configuration for a specific direction.
 */
class MigrationConfiguration {

	/**
	 * @var string
	 */
	protected $comments;

	/**
	 * @var string
	 */
	protected $warnings;

	/**
	 * @var array
	 */
	protected $migration;

	/**
	 * @param array $configuration
	 */
	public function __construct(array $configuration = array()) {
		$this->comments = isset($configuration['comments']) ? $configuration['comments'] : NULL;
		$this->warnings = isset($configuration['warnings']) ? $configuration['warnings'] : NULL;
		$this->migration = isset($configuration['migration']) ? $configuration['migration'] : NULL;
	}

	/**
	 * @return string
	 */
	public function getComments() {
		return $this->comments;
	}

	/**
	 * @return boolean
	 */
	public function hasComments() {
		return ($this->comments !== NULL);
	}

	/**
	 * @return array
	 */
	public function getMigration() {
		return $this->migration;
	}

	/**
	 * @return string
	 */
	public function getWarnings() {
		return $this->warnings;
	}

	/**
	 * @return boolean
	 */
	public function hasWarnings() {
		return ($this->warnings !== NULL);
	}
}
?>