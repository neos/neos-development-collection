<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Domain\Repository;

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
 * The repository for nodes
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NodeRepository extends \F3\FLOW3\Persistence\Repository {

	/**
	 * Finds a node by its path and workspace.
	 *
	 * If the node does not exist in the specified workspace, this function will
	 * try to find one with the given path in one of the base workspaces (if any).
	 *
	 * Examples for valid paths:
	 *
	 *		/          the root node
	 *		/foo       node "foo" on the first level
	 *		/foo/bar   node "bar" on the second level
	 *		/foo/      first node on second level, below "foo"
	 *
	 * @param string $path Absolute path of the node
	 * @param \F3\TYPO3CR\Domain\Model\Workspace $workspace The containing workspace
	 * @return \F3\TYPO3CR\Domain\Model\Node The matching node if found, otherwise NULL
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findOneByPath($path, \F3\TYPO3CR\Domain\Model\Workspace $workspace) {
		if (strlen($path) === 0 || ($path !== '/' && ($path[0] !== '/' || substr($path, -1, 1) === '/'))) {
			throw new \InvalidArgumentException('"' . $path . '" is not a valid path: must start but not end with a slash.', 1284985489);
		}

		if ($path === '/') {
			return $workspace->getRootNode();
		}

		foreach ($this->addedObjects as $addedNode) {
			if ($addedNode->getPath() === $path) {
				return $addedNode;
			}
		}

		$depth = substr_count($path, '/');
		while($workspace !== NULL) {
			$query = $this->createQuery();
			$query->setOrderings(array('index' => \F3\FLOW3\Persistence\QueryInterface::ORDER_ASCENDING));
			$query->matching(
				$query->logicalAnd(
					$query->equals('workspace', $workspace),
					$query->equals('depth', $depth),
					$query->like('path', $path)
				)
			);

			$node = $query->execute()->getFirst();
			if ($node) {
				return $node;
			}
			$workspace = $workspace->getBaseWorkspace();
		}

		return NULL;
	}

	/**
	 * Finds nodes with an index higher than the one specified, below the node defined
	 * by the parent path.
	 *
	 * @param string $parentPath Path of the parent node
	 * @param integer $index Only nodes with an index higher than $index are returned
	 * @param \F3\TYPO3CR\Domain\Model\Workspace $workspace
	 * @return array<\F3\TYPO3\Domain\Model\Node> The nodes found
	 * @todo Check for workspace compliance
	 */
	public function findByHigherIndex($parentPath, $index, \F3\TYPO3CR\Domain\Model\Workspace $workspace) {
		$query = $this->createQueryForFindByParentAndContentType($parentPath, NULL, $workspace);
		$query->setOffset($index);
		return $query->execute()->toArray();
	}

	/**
	 * Counts the number of nodes specified by its parent and (optionally) by its
	 * content type
	 *
	 * @param string $parentPath Absolute path of the parent node
	 * @param string $contentType Content type - or NULL
	 * @param \F3\TYPO3CR\Domain\Model\Workspace $workspace The containing workspace
	 * @return integer The number of nodes found
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Check for workspace compliance
	 */
	public function countByParentAndContentType($parentPath, $contentType, \F3\TYPO3CR\Domain\Model\Workspace $workspace) {
		$result = $this->createQueryForFindByParentAndContentType($parentPath, $contentType, $workspace)->execute()->count();

		foreach ($this->addedObjects as $addedNode) {
			if (substr($addedNode->getPath(), 0, strlen($parentPath) + 1) === ($parentPath . '/')) {
				$result ++;
			}
		}
		return $result;
	}

	/**
	 * Finds nodes by its parent and (optionally) by its content type
	 *
	 * @param string $parentPath Absolute path of the parent node
	 * @param string $contentType Content type - or NULL
	 * @param \F3\TYPO3CR\Domain\Model\Workspace $workspace The containing workspace
	 * @return array<\F3\TYPO3\Domain\Model\Node> The nodes found on the given path
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findByParentAndContentType($parentPath, $contentType, \F3\TYPO3CR\Domain\Model\Workspace $workspace) {
		$foundNodes = array();

		while ($workspace !== NULL) {
			$query = $this->createQueryForFindByParentAndContentType($parentPath, $contentType, $workspace);
			$nodesInThisWorkspace = $query->execute()->toArray();
			foreach ($nodesInThisWorkspace as $node) {
				if (!isset($foundNodes[$node->getIndex()])) {
					$foundNodes[$node->getIndex()] = $node;
				}
			}
			$workspace = $workspace->getBaseWorkspace();
		}

		ksort($foundNodes);
		return $foundNodes;
	}

	/**
	 * Finds a single node by its parent and (optionally) by its content type
	 *
	 * @param string $parentPath Absolute path of the parent node
	 * @param string $contentType Content type - or NULL
	 * @param \F3\TYPO3CR\Domain\Model\Workspace $workspace The containing workspace
	 * @return \F3\TYPO3\Domain\Model\Node The node found or NULL
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Check for workspace compliance
	 */
	public function findFirstByParentAndContentType($parentPath, $contentType, \F3\TYPO3CR\Domain\Model\Workspace $workspace) {
		$query = $this->createQueryForFindByParentAndContentType($parentPath, $contentType, $workspace);
		return $query->execute()->getFirst();
	}

	/**
	 * Finds all nodes of the specified workspace lying on the path specified by
	 * (and including) the given starting point and end point.
	 *
	 * If some node does not exist in the specified workspace, this function will
	 * try to find a corresponding node in one of the base workspaces (if any).
	 *
	 * @param string $pathStartingPoint Absolute path specifying the starting point
	 * @param string $pathEndPoint Absolute path specifying the end point
	 * @param \F3\TYPO3CR\Domain\Model\Workspace $workspace The containing workspace
	 * @return array<\F3\TYPO3\Domain\Model\Node> The nodes found on the given path
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findOnPath($pathStartingPoint, $pathEndPoint, \F3\TYPO3CR\Domain\Model\Workspace $workspace) {
		if ($pathStartingPoint !== substr($pathEndPoint, 0, strlen($pathStartingPoint))) {
			throw new \InvalidArgumentException('Invalid paths: path of starting point must first part of end point path.', 1284391181);
		}

		$foundNodes = array();
		$pathSegments = explode('/', substr($pathEndPoint, strlen($pathStartingPoint)));

		while($workspace !== NULL && count($foundNodes) < count($pathSegments)) {
			$query = $this->createQuery();
			$pathConstraints = array();
			$constraintPath = $pathStartingPoint;

			foreach ($pathSegments as $pathSegment) {
				$constraintPath .= $pathSegment;
				$pathConstraints[] = $query->equals('path', $constraintPath);
				$constraintPath .= '/';
			}

			$query->matching(
				$query->logicalAnd(
					$query->logicalOr($pathConstraints),
					$query->equals('workspace', $workspace)
				)
			);

			$nodesInThisWorkspace = $query->execute()->toArray();
			foreach ($nodesInThisWorkspace as $node) {
				if (!isset($foundNodes[$node->getDepth()])) {
					$foundNodes[$node->getDepth()] = $node;
				}
			}
			$workspace = $workspace->getBaseWorkspace();
		}
		ksort($foundNodes);
		return (count($foundNodes) === count($pathSegments)) ? array_values($foundNodes) : array();
	}

	/**
	 * Creates a query for finding a single node by its parent and (optionally) by
	 * its content type.
	 *
	 * Does not traverse base workspaces, returns ary query only matching nodes of
	 * the given workspace.
	 *
	 * @param string $parentPath Absolute path of the parent node
	 * @param string $contentType Content type - or NULL
	 * @param \F3\TYPO3CR\Domain\Model\Workspace $workspace The containing workspace
	 * @return \F3\FLOW3\Peristence\QueryInterface The query
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function createQueryForFindByParentAndContentType($parentPath, $contentType, \F3\TYPO3CR\Domain\Model\Workspace $workspace) {
		if (strlen($parentPath) === 0 || ($parentPath !== '/' && ($parentPath[0] !== '/' || substr($parentPath, -1, 1) === '/'))) {
			throw new \InvalidArgumentException('"' . $parentPath . '" is not a valid path: must start but not end with a slash.', 1284985610);
		}
		$depth = ($parentPath === '/' ? 0 : substr_count($parentPath, '/')) + 1;

		$query = $this->createQuery();
		$constraints = array(
			$query->equals('workspace', $workspace),
			$query->equals('depth', $depth),
			$query->like('path', $parentPath . '/%'),
		);

		if ($contentType !== NULL) {
			$constraints[] = $query->equals('contentType', $contentType);
		}

		$query->matching($query->logicalAnd($constraints));
		$query->setOrderings(array('index' => \F3\FLOW3\Persistence\QueryInterface::ORDER_ASCENDING));
		return $query;
	}

}
?>