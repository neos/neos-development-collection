<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Storage;

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
 * @package TYPO3CR
 * @subpackage Storage
 * @version $Id$
 */

/**
 * Storage search interface
 *
 * @package TYPO3CR
 * @subpackage Storage
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
interface SearchInterface {

	/**
	 * Sets the name of the current workspace
	 *
	 * @param string $workspaceName Name of the workspace which should be used for all search operations
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function setWorkspaceName($workspaceName);

	/**
	 * Performs any needed initialization before the search backend can be used
	 *
	 * @return void
	 */
	public function connect();

	/**
	 * Adds the given node to the index
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return void
	 */
	public function addNode(\F3\PHPCR\NodeInterface $node);

	/**
	 * Updates the given node in the index
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return void
	 */
	public function updateNode(\F3\PHPCR\NodeInterface $node);

	/**
	 * Deletes the given node from the index
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return void
	 */
	public function deleteNode(\F3\PHPCR\NodeInterface $node);

	/**
	 * Returns an array with node identifiers matching the query
	 *
	 * @param \F3\PHPCR\Query\QOM\QueryObjectModelInterface $query
	 * @return array
	 */
	public function findNodeIdentifiers(\F3\PHPCR\Query\QOM\QueryObjectModelInterface $query);

}
?>