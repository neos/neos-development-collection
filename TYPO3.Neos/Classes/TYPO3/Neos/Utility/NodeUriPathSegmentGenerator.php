<?php
namespace TYPO3\Neos\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations\After;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\Eel\FlowQuery\FlowQuery;

/**
 * Static Utility to generate a valid, non-conflicting uriPathSegment for Nodes.
 */
class NodeUriPathSegmentGenerator {

	/**
	 * Sets the best possible uriPathSegment for the given Node.
	 * Will use an already set uriPathSegment or alternatively the node name as base,
	 * then checks if the uriPathSegment already exists on the same level and appends a counter until a unique path segment was found.
	 *
	 * @param NodeInterface $node
	 * @return void
	 */
	public static function setUniqueUriPathSegment(NodeInterface $node) {
		if ($node->getNodeType()->isOfType('TYPO3.Neos:Document')) {
			$q = new FlowQuery(array($node));
			$q = $q->context(array('invisibleContentShown' => TRUE, 'removedContentShown' => TRUE, 'inaccessibleContentShown' => TRUE));

			$possibleUriPathSegment = $initialUriPathSegment = !$node->hasProperty('uriPathSegment') ? $node->getName() : $node->getProperty('uriPathSegment');
			$i = 1;
			while ($q->siblings('[instanceof TYPO3.Neos:Document][uriPathSegment="' . $possibleUriPathSegment . '"]')->count() > 0) {
				$possibleUriPathSegment = $initialUriPathSegment . '-' . $i++;
			}
			$node->setProperty('uriPathSegment', $possibleUriPathSegment);
		}
	}
}