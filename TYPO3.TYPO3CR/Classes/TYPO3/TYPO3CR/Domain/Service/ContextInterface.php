<?php
namespace TYPO3\TYPO3CR\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Context Interface
 *
 */
interface ContextInterface {

	/**
	 * Returns the current workspace.
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\Workspace
	 */
	public function getWorkspace();

	/**
	 * Sets the current node.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return void
	 */
	public function setCurrentNode(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node);

	/**
	 * Returns the current node
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	public function getCurrentNode();

	/**
	 * Returns a node specified by the given absolute path.
	 *
	 * @param string $path Absolute path specifying the node
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The specified node or NULL if no such node exists
	 */
	public function getNode($path);

	/**
	 * Finds all nodes lying on the path specified by (and including) the given
	 * starting point and end point.
	 *
	 * @param mixed $startingPoint Either an absolute path or an actual node specifying the starting point, for example /sites/mysite.com/
	 * @param mixed $endPoint Either an absolute path or an actual node specifying the end point, for example /sites/mysite.com/homepage/subpage
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> The nodes found between and including the given paths or an empty array of none were found
	 */
	public function getNodesOnPath($startingPoint, $endPoint);

	/**
	 * Sets the invisibleContentShown flag. This flag defines if invisible content elements
	 * should be shown in Node->filterNodeByContext()
	 *
	 * @param boolean $invisibleContentShown
	 * @return void
	 */
	public function setInvisibleContentShown($invisibleContentShown);

	/**
	 * Gets the invisibleContentShown flag. This flag defines if invisible content elements
	 * should be shown in Node->filterNodeByContext()
	 *
	 * @return boolean
	 */
	public function isInvisibleContentShown();

	/**
	 * Sets the removedContentShown flag. This flag defines if removed content elements
	 * should be shown in Node->filterNodeByContext()
	 *
	 * @param boolean $removedContentShown
	 * @return void
	 */
	public function setRemovedContentShown($removedContentShown);

	/**
	 * Gets the removedContentShown flag. This flag defines if removed content elements
	 * should be shown in Node->filterNodeByContext()
	 *
	 * @return boolean
	 */
	public function isRemovedContentShown();

	/**
	 * @param boolean $inaccessibleContentShown
	 * @return void
	 */
	public function setInaccessibleContentShown($inaccessibleContentShown);

	/**
	 * @return boolean
	 */
	public function isInaccessibleContentShown();

}
?>