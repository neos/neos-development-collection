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

use TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface;

/**
 * Context Interface
 *
 */
interface ContextInterface {

	/**
	 * Returns the current workspace.
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\Workspace
	 * @api
	 */
	public function getWorkspace();

	/**
	 * Sets the current node.
	 *
	 * @param PersistentNodeInterface $node
	 * @return void
	 * @api
	 */
	public function setCurrentNode(PersistentNodeInterface $node);

	/**
	 * Returns the current node
	 *
	 * @return PersistentNodeInterface
	 * @api
	 */
	public function getCurrentNode();

	/**
	 * Returns the current date and time in form of a \DateTime
	 * object.
	 *
	 * If you use this method for getting the current date and time
	 * everywhere in your code, it will be possible to simulate a certain
	 * time in unit tests or in the actual application (for realizing previews etc).
	 *
	 * @return \DateTime The current date and time - or a simulated version of it
	 * @api
	 */
	public function getCurrentDateTime();

	/**
	 * Sets the simulated date and time. This time will then always be returned
	 * by getCurrentDateTime().
	 *
	 * @param \DateTime $currentDateTime A date and time to simulate.
	 * @return void
	 * @api
	 */
	public function setCurrentDateTime(\DateTime $currentDateTime);

	/**
	 * Returns a node specified by the given absolute path.
	 *
	 * @param string $path Absolute path specifying the node
	 * @return PersistentNodeInterface The specified node or NULL if no such node exists
	 * @api
	 */
	public function getNode($path);

	/**
	 * Finds all nodes lying on the path specified by (and including) the given
	 * starting point and end point.
	 *
	 * @param mixed $startingPoint Either an absolute path or an actual node specifying the starting point, for example /sites/mysite.com/
	 * @param mixed $endPoint Either an absolute path or an actual node specifying the end point, for example /sites/mysite.com/homepage/subpage
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface> The nodes found between and including the given paths or an empty array of none were found
	 * @api
	 */
	public function getNodesOnPath($startingPoint, $endPoint);

	/**
	 * Sets if nodes which are usually invisible should be accessible through the Node API and queries
	 *
	 * @param boolean $invisibleContentShown If TRUE, invisible nodes are shown.
	 * @return void
	 * @see Node->filterNodeByContext()
	 * @api
	 */
	public function setInvisibleContentShown($invisibleContentShown);

	/**
	 * Tells if nodes which are usually invisible should be accessible through the Node API and queries
	 *
	 * @return boolean
	 * @see Node->filterNodeByContext()
	 * @api
	 */
	public function isInvisibleContentShown();

	/**
	 * Sets if nodes which have their "removed" flag set should be accessible through
	 * the Node API and queries
	 *
	 * @param boolean $removedContentShown If TRUE, removed nodes are shown
	 * @return void
	 * @see Node->filterNodeByContext()
	 * @api
	 */
	public function setRemovedContentShown($removedContentShown);

	/**
	 * Tells if nodes which have their "removed" flag set should be accessible through
	 * the Node API and queries
	 *
	 * @return boolean
	 * @see Node->filterNodeByContext()
	 * @api
	 */
	public function isRemovedContentShown();

	/**
	 * Sets if nodes which have access restrictions should be accessible through
	 * the Node API and queries even without the necessary roles / rights
	 *
	 * @param boolean $inaccessibleContentShown
	 * @return void
	 * @api
	 */
	public function setInaccessibleContentShown($inaccessibleContentShown);

	/**
	 * Tells if nodes which have access restrictions should be accessible through
	 * the Node API and queries even without the necessary roles / rights
	 *
	 * @return boolean
	 * @api
	 */
	public function isInaccessibleContentShown();

}
?>