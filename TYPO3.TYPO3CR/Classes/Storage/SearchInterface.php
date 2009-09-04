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
 * Storage search interface
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface SearchInterface {

	/**
	 * Sets the name of the current workspace
	 *
	 * @param string $workspaceName Name of the workspace which should be used for all search operations
	 * @return void
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function setWorkspaceName($workspaceName);

	/**
	 * Sets the namespace registry used by the backend to translate prefixed names into (URI, name) tuples
	 *
	 * @param \F3\PHPCR\NamespaceRegistryInterface $namespaceRegistry
	 * @return void
	 * @api
	 */
	public function setNamespaceRegistry(\F3\PHPCR\NamespaceRegistryInterface $namespaceRegistry);

	/**
	 * Performs any needed initialization before the search backend can be used
	 *
	 * @return void
	 * @api
	 */
	public function connect();

	/**
	 * Performs any needed cleanup before the search backend can be discarded
	 *
	 * @return void
	 * @api
	 */
	public function disconnect();

	/**
	 * Adds the given node to the index
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return void
	 * @api
	 */
	public function addNode(\F3\PHPCR\NodeInterface $node);

	/**
	 * Updates the given node in the index
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return void
	 * @api
	 */
	public function updateNode(\F3\PHPCR\NodeInterface $node);

	/**
	 * Deletes the given node from the index
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return void
	 * @api
	 */
	public function deleteNode(\F3\PHPCR\NodeInterface $node);

	/**
	 * Returns an array with node identifiers matching the query. The array
	 * is expected to be like this:
	 * array(
	 *  array('selectorA' => '12345', 'selectorB' => '67890')
	 *  array('selectorA' => '54321', 'selectorB' => '09876')
	 * )
	 *
	 * @param \F3\PHPCR\Query\QOM\QueryObjectModelInterface $query
	 * @return array
	 * @api
	 */
	public function findNodeIdentifiers(\F3\PHPCR\Query\QOM\QueryObjectModelInterface $query);

}
?>