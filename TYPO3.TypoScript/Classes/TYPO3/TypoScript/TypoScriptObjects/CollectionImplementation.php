<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Render a TypoScript collection of nodes
 *
 * //tsPath collection *Collection
 * //tsPath itemRenderer the TS object which is triggered for each element in the node collection
 */
class CollectionImplementation extends AbstractTypoScriptObject {

	/**
	 * Render the array collection by triggering the itemRenderer for every element
	 *
	 * @var array or array-like
	 */
	protected $collection;

	/**
	 * The number of rendered nodes, filled only after evaluate() was called.
	 *
	 * @var integer
	 */
	protected $numberOfRenderedNodes;

	/**
	 * @var string
	 */
	protected $itemName;

	/**
	 * If set iteration data (index, cycle, isFirst, isLast) is available in context with the name given.
	 *
	 * @var string
	 */
	protected $iterationName;

	/**
	 * @param array $collection
	 * @return void
	 */
	public function setCollection($collection) {
		$this->collection = $collection;
	}

	/**
	 * @param string $itemName
	 * @return void
	 */
	public function setItemName($itemName) {
		$this->itemName = $itemName;
	}

	/**
	 * @param string $iterationName
	 * @return void
	 */
	public function setIterationName($iterationName) {
		$this->iterationName = $iterationName;
	}

	/**
	 * Evaluate the collection nodes
	 *
	 * @return string
	 * @throws \TYPO3\TypoScript\Exception
	 */
	public function evaluate() {
		$collection = $this->tsValue('collection');

		$output = '';
		if ($collection === NULL) {
			return '';
		}
		$this->numberOfRenderedNodes = 0;
		$itemName = $this->tsValue('itemName');
		if ($itemName === NULL) {
			throw new \TYPO3\TypoScript\Exception('The Collection needs an itemName to be set.', 1344325771);
		}
		$iterationName = $this->tsValue('iterationName');
		$collectionTotalCount = count($collection);
		foreach ($collection as $collectionElement) {
			$context = $this->tsRuntime->getCurrentContext();
			$context[$itemName] = $collectionElement;
			if ($iterationName !== NULL) {
				$context[$iterationName] = $this->prepareIterationInformation($collectionTotalCount);
			}

			$this->tsRuntime->pushContextArray($context);
			$output .= $this->tsRuntime->render($this->path . '/itemRenderer');
			$this->tsRuntime->popContext();
			$this->numberOfRenderedNodes++;
		}

		return $output;
	}

	/**
	 * @param integer $collectionCount
	 * @return array
	 */
	protected function prepareIterationInformation($collectionCount) {
		$iteration = array (
			'index' => $this->numberOfRenderedNodes,
			'cycle' => ($this->numberOfRenderedNodes + 1),
			'isFirst' => FALSE,
			'isLast' => FALSE
		);
		if ($this->numberOfRenderedNodes === 0) {
			$iteration['isFirst'] = TRUE;
		}
		if (($this->numberOfRenderedNodes + 1) === $collectionCount) {
			$iteration['isLast'] = TRUE;
		}

		return $iteration;
	}
}
?>