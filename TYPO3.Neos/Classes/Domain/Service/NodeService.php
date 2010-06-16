<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Service;

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
 * The Node Service
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class NodeService {

	/**
	 * @var \F3\TYPO3\Domain\Service\ContentContext
	 */
	protected $contentContext;

	/**
	 * Constructs this service
	 *
	 * @param \F3\TYPO3\Domain\Service\ContentContext $contentContext The context for this service
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(\F3\TYPO3\Domain\Service\ContentContext $contentContext) {
		$this->contentContext = $contentContext;
	}

	/**
	 * Finds a node in the structure tree of the current site by its path.
	 *
	 * @param string $path Path to the searched content node where the path segements are node names, separated by forward slashes. Example: /home/products/foo
	 * @return \F3\TYPO3\Domain\Model\Structure\NodeInterface The node found at the given path or NULL of none was found
	 * @throws \F3\TYPO3\Domain\InvalidPathException if the path is not well formed
	 */
	public function getNode($path) {
		if ($path{0} !== '/') throw new \F3\TYPO3\Domain\Exception\InvalidPathException('"' . $path . '" is not a valid node path: Only absolute paths are supported.', 1254924207);

		$nodesOnPath = $this->getNodesOnPath($path);
		return (is_array($nodesOnPath) && count($nodesOnPath) > 0) ? end($nodesOnPath) : NULL;
	}

	/**
	 * Finds all nodes of the current site lying on the specified path.
	 *
	 * @param string $path Valid content node path. Path segements are node names, separated by forward slashes. Example: /home/products/foo
	 * @return array<\F3\TYPO3\Domain\Model\Structure\NodeInterface> The nodes found on the given path or NULL if the path did not point to a node
	 * @throws \F3\TYPO3\Domain\InvalidPathException if the path is not well formed
	 */
	public function getNodesOnPath($path) {
		if ($path{0} !== '/') throw new \F3\TYPO3\Domain\Exception\InvalidPathException('"' . $path . '" is not a valid node path: Only absolute paths are supported.', 1255430851);

		$currentSite = $this->contentContext->getCurrentSite();
		if ($path === '/') {
			$indexNode = $currentSite->getIndexNode($this->contentContext);
			return ($indexNode !== NULL) ? array($indexNode) : NULL;
		}

		$pathSegments = explode('/', substr($path, 1));
		$nodesOnPath = array();
		$nextReferenceNode = $currentSite;
		foreach ($pathSegments as $pathSegment) {
			if ($nextReferenceNode->hasChildNodes() === FALSE) {
				return NULL;
			}
			$childNodes = $nextReferenceNode->getChildNodes($this->contentContext);
			$nextReferenceNode = NULL;
			foreach ($childNodes as $childNode) {
				if ($childNode->getNodeName() === $pathSegment) {
					$nextReferenceNode = $childNode;
					$nodesOnPath[] = $childNode;
				}
			}
			if ($nextReferenceNode === NULL) {
				return NULL;
			}
		}
		return $nodesOnPath;
	}
}
?>