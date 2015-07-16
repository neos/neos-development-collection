<?php
namespace TYPO3\TYPO3CR\Migration\Filters;

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
use TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository;

/**
 * Filter nodes by their dimensions.
 */
class DimensionValues implements FilterInterface {

	/**
	 * @Flow\Inject
	 * @var ContentDimensionRepository
	 */
	protected $contentDimensionRepository;

	/**
	 * The array of dimension values to filter for.
	 *
	 * @var array
	 */
	protected $dimensionValues = array();

	/**
	 * Overrides the given dimensionValues with dimension defaults.
	 *
	 * @var boolean
	 */
	protected $filterForDefaultDimensionValues = FALSE;

	/**
	 * @param array $dimensionValues
	 */
	public function setDimensionValues($dimensionValues) {
		$this->dimensionValues = $dimensionValues;
	}

	/**
	 * @param boolean $filterForDefaultDimensionValues
	 */
	public function setFilterForDefaultDimensionValues($filterForDefaultDimensionValues) {
		$this->filterForDefaultDimensionValues = $filterForDefaultDimensionValues;
	}

	/**
	 * Returns TRUE if the given node has the default dimension values.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeData $node
	 * @return boolean
	 */
	public function matches(\TYPO3\TYPO3CR\Domain\Model\NodeData $node) {
		if ($this->filterForDefaultDimensionValues === TRUE) {
			$configuredDimensions = $this->contentDimensionRepository->findAll();
			foreach ($configuredDimensions as $dimension) {
				$this->dimensionValues[$dimension->getIdentifier()] = array($dimension->getDefault());
			}
		}

		return ($node->getDimensionValues() === $this->dimensionValues);
	}

}
