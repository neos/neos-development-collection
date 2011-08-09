<?php
namespace TYPO3\TYPO3CR\Domain\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Context Interface
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
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
	 * Sets the showHidden flag. This flag defines if hidden content elements
	 * should be shown in Node->treatNodeWithContext()
	 *
	 * @param boolean $showHidden
	 * @return void
	 * @author Rens Admiraal <rens.admiraal@typo3.org>
	 */
	public function showHidden($showHidden);

	/**
	 * Gets the showHidden flag. This flag defines if hidden content elements
	 * should be shown in Node->treatNodeWithContext()
	 *
	 * @return boolean
	 * @author Rens Admiraal <rens.admiraal@typo3.org>
	 */
	public function shouldShowHidden();

	/**
	 * Sets the showRemoved flag. This flag defines if removed content elements
	 * should be shown in Node->treatNodeWithContext()
	 *
	 * @param boolean $showRemoved
	 * @return void
	 * @author Rens Admiraal <rens.admiraal@typo3.org>
	 */
	public function showRemoved($showRemoved);

	/**
	 * Gets the showRemoved flag. This flag defines if removed content elements
	 * should be shown in Node->treatNodeWithContext()
	 *
	 * @return boolean
	 * @author Rens Admiraal <rens.admiraal@typo3.org>
	 */
	public function shouldShowRemoved();

}
?>