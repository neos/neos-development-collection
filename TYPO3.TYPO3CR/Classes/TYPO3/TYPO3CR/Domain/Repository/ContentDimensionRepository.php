<?php
namespace TYPO3\TYPO3CR\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\ContentDimension;

/**
 * A repository for access to available content dimensions (from configuration)
 *
 * @Flow\Scope("singleton")
 */
class ContentDimensionRepository {

	/**
	 * @var array
	 */
	protected $dimensionsConfiguration = array();

	/**
	 * Returns an array of content dimensions that are available in the system.
	 *
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\ContentDimension>
	 */
	public function findAll() {
		$dimensions = array();
		foreach ($this->dimensionsConfiguration as $dimensionIdentifier => $dimensionConfiguration) {
			$dimensions[] = new ContentDimension($dimensionIdentifier, $dimensionConfiguration['default']);
		}
		return $dimensions;
	}

	/**
	 * Set the content dimensions available in the system.
	 *
	 * @param array $dimensionsConfiguration
	 * @return void
	 */
	public function setDimensionsConfiguration(array $dimensionsConfiguration) {
		$this->dimensionsConfiguration = $dimensionsConfiguration;
	}

}
