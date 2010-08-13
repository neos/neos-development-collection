<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Model\Structure;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Marker interface for nodes which support the concept of index nodes
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @author Robert Lemke <robert@typo3.org>
 * @api
 */
interface IndexNodeAwareInterface {

	/**
	 * Returns the node which acts as a index node for the children of the current node.
	 *
	 * @param \F3\TYPO3\Domain\Service\ContentContext $contentContext The context
	 * @return \F3\TYPO3\Domain\Model\Structure\NodeInterface The index node or NULL if no index node exists.
	 * @api
	 */
	public function getIndexNode(\F3\TYPO3\Domain\Service\ContentContext $contentContext);


}

?>