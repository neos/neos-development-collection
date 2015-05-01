<?php
namespace TYPO3\TYPO3CR\Migration\Transformations;

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
use TYPO3\TYPO3CR\Domain\Model\NodeDimension;
use TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository;

/**
 * Change the value of a given property.
 */
class SetDimensions extends AbstractTransformation {

	/**
	 * @Flow\Inject
	 * @var ContentDimensionRepository
	 */
	protected $contentDimensionRepository;

	/**
	 * If you omit a configured dimension this transformation will add the default value for that dimension.
	 *
	 * @var array
	 */
	protected $dimensionValues = array();

	/**
	 * Adds the default dimension values for all dimensions that were not given.
	 *
	 * @var boolean
	 */
	protected $addDefaultDimensionValues = TRUE;

	/**
	 * @param array $dimensionValues
	 */
	public function setDimensionValues($dimensionValues) {
		$this->dimensionValues = $dimensionValues;
	}

	/**
	 * @param boolean $addDefaultDimensionValues
	 */
	public function setAddDefaultDimensionValues($addDefaultDimensionValues) {
		$this->addDefaultDimensionValues = $addDefaultDimensionValues;
	}

	/**
	 * Change the property on the given node.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeData $node
	 * @return void
	 */
	public function execute(\TYPO3\TYPO3CR\Domain\Model\NodeData $node) {
		$dimensions = array();
		foreach ($this->dimensionValues as $dimensionName => $dimensionConfiguration) {
			foreach ($dimensionConfiguration as $dimensionValues) {
				if (is_array($dimensionValues)) {
					foreach ($dimensionValues as $dimensionValue) {
						$dimensions[] = new NodeDimension($node, $dimensionName, $dimensionValue);
					}
				} else {
					$dimensions[] = new NodeDimension($node, $dimensionName, $dimensionValues);
				}
			}
		}

		if ($this->addDefaultDimensionValues === TRUE) {
			$configuredDimensions = $this->contentDimensionRepository->findAll();
			foreach ($configuredDimensions as $configuredDimension) {
				if (!isset($this->dimensionValues[$configuredDimension->getIdentifier()])) {
					$dimensions[] = new NodeDimension($node, $configuredDimension->getIdentifier(), $configuredDimension->getDefault());
				}
			}
		}

		$node->setDimensions($dimensions);
	}
}
