<?php
namespace TYPO3\TYPO3\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A TypoScript Section object
 *
 * @FLOW3\Scope("prototype")
 */
class Section extends ContentArray {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TypoScript\ObjectFactory
	 */
	protected $objectFactory;

	/**
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 */
	public function setNode(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		parent::setNode($node);

		foreach ($node->getChildNodes() as $childNode) {
			$this->contentArray[] = $this->objectFactory->createByNode($childNode);
		}
	}

}
?>