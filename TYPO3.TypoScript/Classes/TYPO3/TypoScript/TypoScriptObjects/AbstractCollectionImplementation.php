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
use TYPO3\TypoScript\Exception as TypoScriptException;

/**
 * Abstract implementation of a collection renderer for TypoScript.
 */
abstract class AbstractCollectionImplementation extends AbstractTypoScriptObject {

	/**
	 * The number of rendered nodes, filled only after evaluate() was called.
	 *
	 * @var integer
	 */
	protected $numberOfRenderedNodes;

	/**
	 * Render the array collection by triggering the itemRenderer for every element
	 *
	 * @return array
	 */
	public function getCollection() {
		return $this->tsValue('collection');
	}

	/**
	 * @return string
	 */
	public function getItemName() {
		return $this->tsValue('itemName');
	}

	/**
	 * If set iteration data (index, cycle, isFirst, isLast) is available in context with the name given.
	 *
	 * @return string
	 */
	public function getIterationName() {
		return $this->tsValue('iterationName');
	}

	/**
	 * Evaluate the collection nodes
	 *
	 * @return string
	 * @throws TypoScriptException
	 */
	public function evaluate() {
		$collection = $this->getCollection();

		$output = '';
		if ($collection === NULL) {
			return '';
		}
		$this->numberOfRenderedNodes = 0;
		$itemName = $this->getItemName();
		if ($itemName === NULL) {
			throw new \TYPO3\TypoScript\Exception('The Collection needs an itemName to be set.', 1344325771);
		}
		$iterationName = $this->getIterationName();
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
		$iteration = array(
			'index' => $this->numberOfRenderedNodes,
			'cycle' => ($this->numberOfRenderedNodes + 1),
			'isFirst' => FALSE,
			'isLast' => FALSE,
			'isEven' => FALSE,
			'isOdd' => FALSE
		);

		if ($this->numberOfRenderedNodes === 0) {
			$iteration['isFirst'] = TRUE;
		}
		if (($this->numberOfRenderedNodes + 1) === $collectionCount) {
			$iteration['isLast'] = TRUE;
		}
		if (($this->numberOfRenderedNodes + 1) % 2 === 0) {
			$iteration['isEven'] = TRUE;
		} else {
			$iteration['isOdd'] = TRUE;
		}

		return $iteration;
	}
}