<?php
namespace TYPO3\TYPO3CR\Domain\Repository;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The repository for nodes
 *
 * @scope singleton
 */
class NodeRepository extends \TYPO3\FLOW3\Persistence\Repository {

	/**
	 * @var \SplObjectStorage
	 */
	protected $addedNodes;

	/**
	 * @var \SplObjectStorage
	 */
	protected $removedNodes;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->addedNodes = new \SplObjectStorage();
		$this->removedNodes = new \SplObjectStorage();
		parent::__construct();
	}

	/**
	 * Adds an object to the persistence.
	 *
	 * @param object $object The object to add
	 * @return void
	 * @api
	 */
	public function add($object) {
		$this->addedNodes->attach($object);
		$this->removedNodes->detach($object);
		parent::add($object);
	}

	/**
	 * Removes an object to the persistence.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @api
	 */
	public function remove($object) {
		if ($this->addedNodes->contains($object)) {
			$this->addedNodes->detach($object);
		}
		$this->removedNodes->attach($object);
		parent::remove($object);
	}

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
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace The containing workspace
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The matching node if found, otherwise NULL
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findOneByPath($path, \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace) {
		if (strlen($path) === 0 || ($path !== '/' && ($path[0] !== '/' || substr($path, -1, 1) === '/'))) {
			throw new \InvalidArgumentException('"' . $path . '" is not a valid path: must start but not end with a slash.', 1284985489);
		}

		if ($path === '/') {
			return $workspace->getRootNode();
		}

		$depth = substr_count($path, '/');
		while($workspace !== NULL) {

			foreach ($this->addedNodes as $node) {
				if ($node->getPath() === $path && $node->getWorkspace() === $workspace) {
					return $node;
				}
			}

			foreach ($this->removedNodes as $node) {
				if ($node->getPath() === $path && $node->getWorkspace() === $workspace) {
					return NULL;
				}
			}

			$query = $this->createQuery();
			$query->setOrderings(array('index' => \TYPO3\FLOW3\Persistence\QueryInterface::ORDER_ASCENDING));
			$query->matching(
				$query->logicalAnd(
					$query->equals('workspace', $workspace),
					$query->equals('depth', $depth),
					$query->like('path', $path)
				)
			);

			$node = $query->execute()->getFirst();
			if ($node !== NULL) {
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
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace
	 * @return array<\TYPO3\TYPO3\Domain\Model\Node> The nodes found
	 * @todo Check for workspace compliance
	 */
	public function findByHigherIndex($parentPath, $index, \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace) {
		$query = $this->createQueryForFindByParentAndContentType($parentPath, NULL, $workspace);
		$query->setOffset($index);
		return $query->execute()->toArray();
	}

	/**
	 * Counts the number of nodes within the specified workspace
	 *
	 * Note: Also counts removed nodes
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace The containing workspace
	 * @return integer The number of nodes found
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function countByWorkspace(\TYPO3\TYPO3CR\Domain\Model\Workspace $workspace) {
		$query = $this->createQuery();
		return $query->matching($query->equals('workspace', $workspace))->execute()->count();
	}

	/**
	 * Counts the number of nodes specified by its parent and (optionally) by its
	 * content type
	 *
	 * @param string $parentPath Absolute path of the parent node
	 * @param string $contentTypeFilter Filter the content type of the nodes, allows complex expressions (e.g. "TYPO3.TYPO3:Page", "!TYPO3.TYPO3:Page,TYPO3.TYPO3:Text" or NULL)
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace The containing workspace
	 * @return integer The number of nodes found
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Implement a count which also considers to not count removed nodes and does not actually loads nodes
	 */
	public function countByParentAndContentType($parentPath, $contentTypeFilter, \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace) {
		return count($this->findByParentAndContentType($parentPath, $contentTypeFilter,$workspace));
	}

	/**
	 * Finds nodes by its parent and (optionally) by its content type.
	 *
	 * Note: Filters out removed nodes.
	 *
	 * The primary sort key is the *index*, the secondary sort key (if indices are equal, which
	 * only occurs in very rare cases) is the *identifier*.
	 *
	 * @param string $parentPath Absolute path of the parent node
	 * @param string $contentTypeFilter Filter the content type of the nodes, allows complex expressions (e.g. "TYPO3.TYPO3:Page", "!TYPO3.TYPO3:Page,TYPO3.TYPO3:Text" or NULL)
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace The containing workspace
	 * @return array<\TYPO3\TYPO3\Domain\Model\Node> The nodes found on the given path
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findByParentAndContentType($parentPath, $contentTypeFilter, \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace) {
		$foundNodes = array();

		while ($workspace !== NULL) {
			$query = $this->createQueryForFindByParentAndContentType($parentPath, $contentTypeFilter, $workspace);
			$nodesFoundInThisWorkspace = $query->execute()->toArray();
			foreach ($nodesFoundInThisWorkspace as $node) {
				if (!isset($foundNodes[$node->getIdentifier()])) {
					$foundNodes[$node->getIdentifier()] = $node;
				}
			}
			$workspace = $workspace->getBaseWorkspace();
		}

		foreach ($this->addedNodes as $addedNode) {
			if (substr($addedNode->getPath(), 0, strlen($parentPath) + 1) === ($parentPath . '/')) {
				$foundNodes[$addedNode->getIdentifier()] = $addedNode;
			}
		}

		usort($foundNodes, function($element1, $element2) {
			if ($element1->getIndex() < $element2->getIndex()) {
				return -1;
			} elseif ($element1->getIndex() > $element2->getIndex()) {
				return 1;
			} else {
				return strcmp($element1->getIdentifier(), $element2->getIdentifier());
			}
		});

		return $foundNodes;
	}

	/**
	 * Finds a single node by its parent and (optionally) by its content type
	 *
	 * @param string $parentPath Absolute path of the parent node
	 * @param string $contentTypeFilter Filter the content type of the nodes, allows complex expressions (e.g. "TYPO3.TYPO3:Page", "!TYPO3.TYPO3:Page,TYPO3.TYPO3:Text" or NULL)
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace The containing workspace
	 * @return \TYPO3\TYPO3\Domain\Model\Node The node found or NULL
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Check for workspace compliance
	 */
	public function findFirstByParentAndContentType($parentPath, $contentTypeFilter, \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace) {
		while ($workspace !== NULL) {
			$query = $this->createQueryForFindByParentAndContentType($parentPath, $contentTypeFilter, $workspace);
			$firstNodeFoundInThisWorkspace = $query->execute()->getFirst();
			if ($firstNodeFoundInThisWorkspace !== NULL) {
				return $firstNodeFoundInThisWorkspace;
			}
			$workspace = $workspace->getBaseWorkspace();
		}
		return NULL;
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
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace The containing workspace
	 * @return array<\TYPO3\TYPO3\Domain\Model\Node> The nodes found on the given path
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findOnPath($pathStartingPoint, $pathEndPoint, \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace) {
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
	 * Creates a query for findinnodes by its parent and (optionally) by its content type.
	 *
	 * Does not traverse base workspaces, returns ary query only matching nodes of
	 * the given workspace.
	 *
	 * @param string $parentPath Absolute path of the parent node
	 * @param string $contentTypeFilter Filter the content type of the nodes, allows complex expressions (e.g. "TYPO3.TYPO3:Page", "!TYPO3.TYPO3:Page,TYPO3.TYPO3:Text" or NULL)
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace The containing workspace
	 * @return \TYPO3\FLOW3\Peristence\QueryInterface The query
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function createQueryForFindByParentAndContentType($parentPath, $contentTypeFilter, \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace) {
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

		if ($contentTypeFilter !== NULL) {
			$includeContentTypeConstraints = array();
			$excludeContentTypeConstraints = array();
			$contentTypeFilterParts = explode(',', $contentTypeFilter);
			foreach ($contentTypeFilterParts as $contentTypeFilterPart) {
				if (strpos($contentTypeFilterPart, '!') === 0) {
					$excludeContentTypeConstraints[] = $query->logicalNot($query->equals('contentType', substr($contentTypeFilterPart, 1)));
				} else {
					$includeContentTypeConstraints[] = $query->equals('contentType', $contentTypeFilterPart);
				}
			}
			if (count($excludeContentTypeConstraints) > 0) {
				$constraints = array_merge($excludeContentTypeConstraints, $constraints);
			}
			if (count($includeContentTypeConstraints) > 0) {
				$constraints[] = $query->logicalOr($includeContentTypeConstraints);
			}
		}

		$query->matching($query->logicalAnd($constraints));
		$query->setOrderings(array('index' => \TYPO3\FLOW3\Persistence\QueryInterface::ORDER_ASCENDING));
		return $query;
	}

	/**
	 * Iterates of the array of objects and removes all those which have recently been removed from the repository,
	 * but whose removal has not yet been persisted.
	 *
	 * Technically this is a check of the given array against $this->removedNodes.
	 *
	 * @param array &$objects An array of objects to filter, passed by reference.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function filterOutRemovedObjects(array &$objects) {
		foreach ($objects as $index => $object) {
			if ($this->removedNodes->contains($object)) {
				unset($objects[$index]);
			}
		}
	}
}
?>