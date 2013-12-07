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
class CollectionImplementation extends AbstractCollectionImplementation {

	/**
	 * Evaluate the collection nodes
	 *
	 * @return string
	 * @throws \TYPO3\TypoScript\Exception
	 */
	public function evaluate() {
		return parent::evaluate();
	}
}
