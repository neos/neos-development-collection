<?php
namespace TYPO3\Neos\ViewHelpers\Node;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\Eel\FlowQuery\FlowQuery;

/**
 * ViewHelper to find the closest document node to a given node
 */
class ClosestDocumentViewHelper extends AbstractViewHelper {

	/**
	 * @param NodeInterface $node
	 * @return NodeInterface
	 */
	public function render(NodeInterface $node) {
		$flowQuery = new FlowQuery(array($node));
		return $flowQuery->closest('[instanceof TYPO3.Neos:Document]')->get(0);
	}

}