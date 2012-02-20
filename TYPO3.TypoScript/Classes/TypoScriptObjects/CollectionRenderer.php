<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Render a TypoScript collection of nodes
 *
 * //tsPath collection *Collection
 * //tsPath itemRenderer the TS object which is triggered for each element in the node collection
 */
class CollectionRenderer extends AbstractTsObject {

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
	 * @param array $collection
	 */
	public function setCollection($collection) {
		$this->collection = $collection;
	}

	/**
	 * Evaluate the collection nodes
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return string
	 */
	public function evaluate($node) {
		$collection = $this->tsValue('collection');

		$output = '';
		$this->numberOfRenderedNodes = 0;
		foreach ($collection as $node) {
			$this->tsRuntime->pushContext($node);
			$output .= $this->tsRuntime->render($this->path . '/itemRenderer');
			$this->tsRuntime->popContext();
			$this->numberOfRenderedNodes++;
		}

		return $output;
	}
}
?>