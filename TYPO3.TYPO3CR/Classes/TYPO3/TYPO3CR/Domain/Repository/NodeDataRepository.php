<?php
namespace TYPO3\TYPO3CR\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\QueryInterface;
use TYPO3\Flow\Persistence\Repository;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Service\ContextInterface;
use TYPO3\TYPO3CR\Exception;

/**
 * The repository for node data
 *
 * @Flow\Scope("singleton")
 */
class NodeDataRepository extends Repository {

	/**
	 * Constants for setNewIndex()
	 */
	const POSITION_BEFORE = 1;
	const POSITION_AFTER = 2;
	const POSITION_LAST = 3;

	/**
	 * Maximum possible index
	 */
	const INDEX_MAXIMUM = 2147483647;

	/**
	 * @var \SplObjectStorage
	 */
	protected $addedNodes;

	/**
	 * @var \SplObjectStorage
	 */
	protected $removedNodes;

	/**
	 * Doctrine's Entity Manager. Note that "ObjectManager" is the name of the related
	 * interface ...
	 *
	 * @Flow\Inject
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	protected $entityManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Factory\NodeFactory
	 */
	protected $nodeFactory;

	/**
	 * @var array
	 */
	protected $defaultOrderings = array(
		'index' => QueryInterface::ORDER_ASCENDING
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->addedNodes = new \SplObjectStorage();
		$this->removedNodes = new \SplObjectStorage();
		parent::__construct();
	}

	/**
	 * Adds a Node to this repository.
	 *
	 * This repository keeps track of added and removed nodes (additionally to the
	 * other Unit of Work) in order to find in-memory nodes.
	 *
	 * @param object $object The object to add
	 * @return void
	 * @api
	 */
	public function add($object) {
		if ($this->removedNodes->contains($object)) {
			$this->removedNodes->detach($object);
		}
		$this->addedNodes->attach($object);
		parent::add($object);
	}

	/**
	 * Removes an object to the persistence.
	 *
	 * This repository keeps track of added and removed nodes (additionally to the
	 * other Unit of Work) in order to find in-memory nodes.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @api
	 */
	public function remove($object) {
		if ($object instanceof Node) {
			$object = $object->getNodeData();
		}
		if ($this->addedNodes->contains($object)) {
			$this->addedNodes->detach($object);
		}
		$this->removedNodes->attach($object);
		parent::remove($object);
	}

	/**
	 * Find a single node by exact path.
	 *
	 * @param string $path Absolute path of the node
	 * @param Workspace $workspace The containing workspace
	 * @return NodeData The matching node if found, otherwise NULL
	 * @throws \InvalidArgumentException
	 * @throws Exception
	 */
	public function findOneByPath($path, Workspace $workspace) {
		if (strlen($path) === 0 || ($path !== '/' && ($path[0] !== '/' || substr($path, -1, 1) === '/'))) {
			throw new \InvalidArgumentException('"' . $path . '" is not a valid path: must start but not end with a slash.', 1284985489);
		}

		if ($path === '/') {
			return $workspace->getRootNodeData();
		}

		$originalWorkspace = $workspace;
		while ($workspace !== NULL) {
			/** @var $node NodeData */
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

			/** @var $query \Doctrine\ORM\Query */
			$query = $this->entityManager->createQuery('SELECT n FROM TYPO3\TYPO3CR\Domain\Model\NodeData n WHERE n.path = :path AND n.workspace = :workspace');
			$query->setParameter('path', $path);
			$query->setParameter('workspace', $workspace);

			try {
				$node = $query->getOneOrNullResult();
				if ($node !== NULL) {
					if ($workspace !== $originalWorkspace) {
						$query = $this->createQuery();
						$query->matching(
							$query->logicalAnd(
								$query->equals('identifier', $node->getIdentifier()),
								$query->equals('workspace', $originalWorkspace)
							)
						);
						if ($query->count() > 0) {
							return NULL;
						}
					}
					return $node;
				}
			} catch (NonUniqueResultException $exception) {
				throw new Exception(sprintf('Non-unique result found for path "%s"', $path), 1328018972, $exception);
			}
			$workspace = $workspace->getBaseWorkspace();
		}

		return NULL;
	}

	/**
	 * Finds a node by its path and workspace.
	 *
	 * If the node does not exist in the specified workspace, this function will
	 * try to find one with the given path in one of the base workspaces (if any).
	 *
	 * Examples for valid paths:
	 *
	 * /          the root node
	 * /foo       node "foo" on the first level
	 * /foo/bar   node "bar" on the second level
	 * /foo/      first node on second level, below "foo"
	 *
	 * @param string $path Absolute path of the node
	 * @param ContextInterface $context The containing context
	 * @return NodeData The matching node if found, otherwise NULL
	 * @throws \InvalidArgumentException
	 * @throws Exception
	 */
	public function findOneByPathInContext($path, ContextInterface $context) {
		$node = $this->findOneByPath($path, $context->getWorkspace());
		if ($node !== NULL) {
			$node = $this->nodeFactory->createFromNodeData($node, $context);
		}

		return $node;
	}

	/**
	 * Finds a node by its identifier and workspace.
	 *
	 * If the node does not exist in the specified workspace, this function will
	 * try to find one with the given identifier in one of the base workspaces (if any).
	 *
	 * @param string $identifier Identifier of the node
	 * @param Workspace $workspace The containing workspace
	 * @return NodeData The matching node if found, otherwise NULL
	 * @throws Exception
	 */
	public function findOneByIdentifier($identifier, Workspace $workspace) {
		$originalWorkspace = $workspace;
		while ($workspace !== NULL) {
			/** @var $node NodeData */
			foreach ($this->addedNodes as $node) {
				if ($node->getIdentifier() === $identifier && $node->getWorkspace() === $workspace) {
					return $node;
				}
			}

			/** @var $node NodeData */
			foreach ($this->removedNodes as $node) {
				if ($node->getIdentifier() === $identifier && $node->getWorkspace() === $workspace) {
					return NULL;
				}
			}

			$query = $this->entityManager->createQuery('SELECT n FROM TYPO3\TYPO3CR\Domain\Model\NodeData n WHERE n.identifier = :identifier AND n.workspace = :workspace');
			$query->setParameter('identifier', $identifier);
			$query->setParameter('workspace', $workspace);

			try {
				$node = $query->getOneOrNullResult();
				if ($node !== NULL) {
					if ($workspace !== $originalWorkspace) {
						$query = $this->createQuery();
						$query->matching(
							$query->logicalAnd(
								$query->equals('identifier', $node->getIdentifier()),
								$query->equals('workspace', $originalWorkspace)
							)
						);
						if ($query->count() > 0) {
							return NULL;
						}
					}
					return $node;
				}
			} catch (NonUniqueResultException $exception) {
				throw new Exception(sprintf('Non-unique result found for identifier "%s"', $identifier), 1346947613, $exception);
			}
			$workspace = $workspace->getBaseWorkspace();
		}

		return NULL;
	}

	/**
	 * Assigns an index to the given node which reflects the specified position.
	 * If the position is "before" or "after", an index will be chosen which makes
	 * the given node the previous or next node of the given reference node.
	 * If the position "last" is specified, an index higher than any existing index
	 * will be chosen.
	 *
	 * If no free index is available between two nodes (for "before" and "after"),
	 * the whole index of the current node level will be renumbered.
	 *
	 * @param NodeData $node The node to set the new index for
	 * @param integer $position The position the new index should reflect, must be one of the POSITION_* constants
	 * @param NodeData $referenceNode The reference node. Mandatory for POSITION_BEFORE and POSITION_AFTER
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function setNewIndex(NodeData $node, $position, NodeData $referenceNode = NULL) {
		$parentPath = $node->getParentPath();

		switch ($position) {
			case self::POSITION_BEFORE:
				if ($referenceNode === NULL) {
					throw new \InvalidArgumentException('The reference node must be specified for POSITION_BEFORE.', 1317198857);
				}
				$referenceIndex = $referenceNode->getIndex();
				$nextLowerIndex = $this->findNextLowerIndex($parentPath, $referenceIndex);
				if ($nextLowerIndex === NULL) {
						// FIXME: $nextLowerIndex returns 0 and not NULL in case no lower index is found. So this case seems to be
						// never executed. We need to check that again!
					$newIndex = (integer)round($referenceIndex / 2);
				} elseif ($nextLowerIndex < ($referenceIndex - 1)) {
						// there is free space left between $referenceNode and preceeding sibling.
					$newIndex = (integer)round($nextLowerIndex + (($referenceIndex - $nextLowerIndex) / 2));
				} else {
						// there is no free space left between $referenceNode and following sibling -> we need to re-number!
					$this->renumberIndexesInLevel($parentPath);
					$referenceIndex = $referenceNode->getIndex();
					$nextLowerIndex = $this->findNextLowerIndex($parentPath, $referenceIndex);
					if ($nextLowerIndex === NULL) {
						$newIndex = (integer)round($referenceIndex / 2);
					} else {
						$newIndex = (integer)round($nextLowerIndex + (($referenceIndex - $nextLowerIndex) / 2));
					}
				}
			break;
			case self::POSITION_AFTER:
				if ($referenceNode === NULL) {
					throw new \InvalidArgumentException('The reference node must be specified for POSITION_AFTER.', 1317198858);
				}
				$referenceIndex = $referenceNode->getIndex();
				$nextHigherIndex = $this->findNextHigherIndex($parentPath, $referenceIndex);
				if ($nextHigherIndex === NULL) {
						// $referenceNode is last node, so we can safely add an index at the end by incrementing the reference index.
					$newIndex = $referenceIndex + 100;
				} elseif ($nextHigherIndex > ($referenceIndex + 1)) {
						// $referenceNode is not last node, but there is free space left between $referenceNode and following sibling.
					$newIndex = (integer)round($referenceIndex + (($nextHigherIndex - $referenceIndex) / 2));
				} else {
						// $referenceNode is not last node, and no free space is left -> we need to re-number!
					$this->renumberIndexesInLevel($parentPath);
					$referenceIndex = $referenceNode->getIndex();
					$nextHigherIndex = $this->findNextHigherIndex($parentPath, $referenceIndex);
					if ($nextHigherIndex === NULL) {
						$newIndex = $referenceIndex + 100;
					} else {
						$newIndex = (integer)round($referenceIndex + (($nextHigherIndex - $referenceIndex) / 2));
					}
				}
			break;
			case self::POSITION_LAST:
				$highestIndex = $this->findHighestIndexInLevel($parentPath);
				$newIndex = $highestIndex + 100;
			break;
			default:
				throw new \InvalidArgumentException('Invalid position for new node index given.', 1329729088);
		}

		$node->setIndex($newIndex);
	}

	/**
	 * Finds recursively nodes by its parent and (optionally) by its node type.
	 *
	 * @see this::findByParentAndNodeType
	 *
	 * @param string $parentPath Absolute path of the parent node
	 * @param string $nodeTypeFilter Filter the node type of the nodes, allows complex expressions (e.g. "TYPO3.Neos:Page", "!TYPO3.Neos:Page,TYPO3.Neos:Text" or NULL)
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace The containing workspace
	 * @param integer $limit An optional limit for the number of nodes to find. Added or removed nodes can still change the number nodes!
	 * @param integer $offset An optional offset for the query
	 * @param boolean $includeRemovedNodes Should removed nodes be included in the result (defaults to FALSE)
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface> The nodes found on the given path
	 */
	public function findByParentAndNodeTypeRecursively($parentPath, $nodeTypeFilter, \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace, $limit = NULL, $offset = NULL, $includeRemovedNodes = FALSE) {
		return $this->findByParentAndNodeType($parentPath, $nodeTypeFilter, $workspace, $limit, $offset, $includeRemovedNodes, TRUE);
	}

	/**
	 * Finds nodes by its parent and (optionally) by its node type.
	 * If the $recursive flag is set to TRUE, all matching nodes underneath $parentPath will be returned
	 *
	 * Note: Filters out removed nodes.
	 *
	 * The primary sort key is the *index*, the secondary sort key (if indices are equal, which
	 * only occurs in very rare cases) is the *identifier*.
	 *
	 * @param string $parentPath Absolute path of the parent node
	 * @param string $nodeTypeFilter Filter the node type of the nodes, allows complex expressions (e.g. "TYPO3.Neos:Page", "!TYPO3.Neos:Page,TYPO3.Neos:Text" or NULL)
	 * @param Workspace $workspace The containing workspace
	 * @param integer $limit An optional limit for the number of nodes to find. Added or removed nodes can still change the number nodes!
	 * @param integer $offset An optional offset for the query
	 * @param boolean $includeRemovedNodes Should removed nodes be included in the result (defaults to FALSE)
	 * @param boolean $recursive If TRUE *all* matching nodes underneath the specified parent path are returned
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeData> The nodes found on the given path
	 * @todo Improve implementation by using DQL
	 */
	public function findByParentAndNodeType($parentPath, $nodeTypeFilter, Workspace $workspace, $limit = NULL, $offset = NULL, $includeRemovedNodes = FALSE, $recursive = FALSE) {
		$baseWorkspace = $workspace;
		$foundNodes = array();
		while ($workspace !== NULL) {
			$query = $this->createQueryForFindByParentAndNodeType($parentPath, $nodeTypeFilter, $workspace, TRUE, $recursive);
			$nodesFoundInThisWorkspace = $query->execute()->toArray();
			/** @var $node NodeData */
			foreach ($nodesFoundInThisWorkspace as $node) {
				if (!isset($foundNodes[$node->getIdentifier()])) {
					$foundNodes[$node->getIdentifier()] = $node;
				}
			}
			$workspace = $workspace->getBaseWorkspace();
		}

		if ($parentPath === '/') {
			/** @var $addedNode NodeData */
			foreach ($this->addedNodes as $addedNode) {
				if ($addedNode->getDepth() === 1) {
					$foundNodes[$addedNode->getIdentifier()] = $addedNode;
				}
			}
			/** @var $removedNode NodeData */
			foreach ($this->removedNodes as $removedNode) {
				if (isset($foundNodes[$removedNode->getIdentifier()])) {
					unset($foundNodes[$removedNode->getIdentifier()]);
				}
			}
		} else {
			$childNodeDepth = substr_count($parentPath, '/') + 1;
			/** @var $addedNode NodeData */
			foreach ($this->addedNodes as $addedNode) {
				if ($addedNode->getDepth() === $childNodeDepth && substr($addedNode->getPath(), 0, strlen($parentPath) + 1) === ($parentPath . '/')) {
					$foundNodes[$addedNode->getIdentifier()] = $addedNode;
				}
			}
			/** @var $removedNode NodeData */
			foreach ($this->removedNodes as $removedNode) {
				if ($removedNode->getDepth() === $childNodeDepth && substr($removedNode->getPath(), 0, strlen($parentPath) + 1) === ($parentPath . '/')) {
					if (isset($foundNodes[$removedNode->getIdentifier()])) {
						unset($foundNodes[$removedNode->getIdentifier()]);
					};
				}
			}
		}

		$foundNodes = $this->sortNodesByIndex($foundNodes);
		if (!$includeRemovedNodes) {
			$foundNodes = $this->filterRemovedNodes($foundNodes);
		}
		$foundNodes = $this->filterNodesOverlaidInBaseWorkspace($foundNodes, $baseWorkspace);
		if ($limit !== NULL || $offset !== NULL) {
			$foundNodes = $this->applyLimitAndOffset($foundNodes, $limit, ($offset === NULL ? 0 : $offset));
		}

		return $foundNodes;
	}

	/**
	 * When moving nodes which are inside live workspace to a personal workspace *across levels* (i.e. with different
	 * parent node before and after), the system returned *both* the "new" node from the personal workspace (correct!),
	 * and the "shined-through" version of the node from the "live" workspace (WRONG!).
	 *
	 * For all nodes not being in our base workspace, we need to check whether it is overlaid by a node in our base workspace
	 * with the same identifier. If that's the case, we do not show the node.
	 *
	 * This is a bugfix for #48214.
	 *
	 * @param array $foundNodes
	 * @param Workspace $baseWorkspace
	 * @return array
	 */
	protected function filterNodesOverlaidInBaseWorkspace(array $foundNodes, Workspace $baseWorkspace) {
		$identifiersOfNodesNotInBaseWorkspace = array();

		/** @var $foundNode NodeData */
		foreach ($foundNodes as $i => $foundNode) {
			if ($foundNode->getWorkspace() !== $baseWorkspace) {
				$identifiersOfNodesNotInBaseWorkspace[$foundNode->getIdentifier()] = $i;
			}
		}
		if (count($identifiersOfNodesNotInBaseWorkspace) === 0) {
			return $foundNodes;
		}

		$query = $this->entityManager->createQuery('SELECT n.identifier FROM TYPO3\TYPO3CR\DOMAIN\MODEL\NodeData n WHERE n.identifier IN (:identifierList) AND n.workspace = :baseWorkspace');

		$query->setParameter('identifierList', array_keys($identifiersOfNodesNotInBaseWorkspace));
		$query->setParameter('baseWorkspace', $baseWorkspace);
		$results = $query->getScalarResult();

		foreach ($results as $result) {
			$nodeIdentifierOfNodeInBaseWorkspace = $result['identifier'];
			$indexOfNodeNotInBaseWorkspaceWhichShouldBeRemoved = $identifiersOfNodesNotInBaseWorkspace[$nodeIdentifierOfNodeInBaseWorkspace];
			unset($foundNodes[$indexOfNodeNotInBaseWorkspaceWhichShouldBeRemoved]);
		}

		return $foundNodes;
	}

	/**
	 * Finds nodes by its parent and (optionally) by its node type.
	 *
	 * Note: Filters out removed nodes.
	 *
	 * The primary sort key is the *index*, the secondary sort key (if indices are equal, which
	 * only occurs in very rare cases) is the *identifier*.
	 *
	 * @param string $parentPath Absolute path of the parent node
	 * @param string $nodeTypeFilter Filter the node type of the nodes, allows complex expressions (e.g. "TYPO3.Neos:Page", "!TYPO3.Neos:Page,TYPO3.Neos:Text" or NULL)
	 * @param ContextInterface $context The containing workspace
	 * @param integer $limit An optional limit for the number of nodes to find. Added or removed nodes can still change the number nodes!
	 * @param integer $offset An optional offset for the query
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeData> The nodes found on the given path
	 */
	public function findByParentAndNodeTypeInContext($parentPath, $nodeTypeFilter, ContextInterface $context, $limit = NULL, $offset = NULL) {
		$nodeDataElements = $this->findByParentAndNodeType($parentPath, $nodeTypeFilter, $context->getWorkspace(), $limit, $offset, $context->isRemovedContentShown());
		$finalNodes = array();
		foreach ($nodeDataElements as $nodeData) {
			$node =$this->nodeFactory->createFromNodeData($nodeData, $context);
			if ($node !== NULL) {
				$finalNodes[] = $node;
			}
		}

		return $finalNodes;
	}

	/**
	 * Counts nodes by its parent and (optionally) by its node type.
	 *
	 * NOTE: Only considers persisted nodes!
	 *
	 * @param string $parentPath Absolute path of the parent node
	 * @param string $nodeTypeFilter Filter the node type of the nodes, allows complex expressions (e.g. "TYPO3.Neos:Page", "!TYPO3.Neos:Page,TYPO3.Neos:Text" or NULL)
	 * @param Workspace $workspace The containing workspace
	 * @param boolean $includeRemovedNodes Should removed nodes be included in the result (defaults to FALSE)
	 * @return integer The number of nodes a similar call to findByParentAndNodeType() would return without any pending added nodes
	 */
	public function countByParentAndNodeType($parentPath, $nodeTypeFilter, Workspace $workspace, $includeRemovedNodes = FALSE) {
		return count($this->findByParentAndNodeType($parentPath, $nodeTypeFilter, $workspace, NULL, NULL, $includeRemovedNodes));
	}

	/**
	 * Renumbers the indexes of all nodes directly below the node specified by the
	 * given path.
	 *
	 * Note that renumbering must happen in-memory and can't be optimized by a clever
	 * query executed directly by the database because sorting indexes of new or
	 * modified nodes need to be considered.
	 *
	 * @param string $parentPath Path to the parent node
	 * @return void
	 * @throws Exception\NodeException
	 */
	protected function renumberIndexesInLevel($parentPath) {
		$this->systemLogger->log(sprintf('Renumbering nodes in level below %s.', $parentPath), LOG_INFO);

		$query = $this->entityManager->createQuery('SELECT n FROM TYPO3\TYPO3CR\Domain\Model\NodeData n WHERE n.parentPath = :parentPath ORDER BY n.index ASC');
		$query->setParameter('parentPath', $parentPath);

		$nodesOnLevel = array();
		/** @var $node NodeData */
		foreach ($query->getResult() as $node) {
			$nodesOnLevel[$node->getIndex()] = $node;
		}

		/** @var $node NodeData */
		foreach ($this->addedNodes as $node) {
			if ($node->getParentPath() === $parentPath) {
				$index = $node->getIndex();
				if (isset($nodesOnLevel[$index])) {
					throw new Exception\NodeException(sprintf('Index conflict for nodes %s and %s: both have index %s', $nodesOnLevel[$index]->getPath(), $node->getPath(), $index), 1317140401);
				}
				$nodesOnLevel[$index] = $node;
			}
		}

			// We need to sort the nodes now, to take unpersisted node orderings into account.
			// This fixes bug #34291
		ksort($nodesOnLevel);

		$newIndex = 100;
		foreach ($nodesOnLevel as $node) {
			if ($newIndex > self::INDEX_MAXIMUM) {
				throw new Exception\NodeException(sprintf('Reached maximum node index of %s while setting index of node %s.', $newIndex, $node->getPath()), 1317140402);
			}
			$node->setIndex($newIndex);
			$newIndex += 100;
		}
	}

	/**
	 * Finds the currently highest index in the level below the given parent path
	 * across all workspaces.
	 *
	 * @param string $parentPath Path of the parent node specifying the level in the node tree
	 * @return integer The currently highest index
	 */
	protected function findHighestIndexInLevel($parentPath) {
		$this->persistEntities();
		$query = $this->entityManager->createQuery('SELECT MAX(n.index) FROM TYPO3\TYPO3CR\Domain\Model\NodeData n WHERE n.parentPath = :parentPath');
		$query->setParameter('parentPath', $parentPath);
		return $query->getSingleScalarResult() ?: 0;
	}

	/**
	 * Returns the next-lower-index seen from the given reference index in the
	 * level below the specified parent path. If no node with a lower than the
	 * given index exists at that level, the reference index is returned.
	 *
	 * The result is determined workspace-agnostic.
	 *
	 * @param string $parentPath Path of the parent node specifying the level in the node tree
	 * @param integer $referenceIndex Index of a known node
	 * @return integer The currently next lower index
	 */
	protected function findNextLowerIndex($parentPath, $referenceIndex) {
		$this->persistEntities();
		$query = $this->entityManager->createQuery('SELECT MAX(n.index) FROM TYPO3\TYPO3CR\Domain\Model\NodeData n WHERE n.parentPath = :parentPath AND n.index < :referenceIndex');
		$query->setParameter('parentPath', $parentPath);
		$query->setParameter('referenceIndex', $referenceIndex);
		return $query->getSingleScalarResult() ?: 0;
	}

	/**
	 * Returns the next-higher-index seen from the given reference index in the
	 * level below the specified parent path. If no node with a higher than the
	 * given index exists at that level, the reference index is returned.
	 *
	 * The result is determined workspace-agnostic.
	 *
	 * @param string $parentPath Path of the parent node specifying the level in the node tree
	 * @param integer $referenceIndex Index of a known node
	 * @return integer The currently next higher index
	 */
	protected function findNextHigherIndex($parentPath, $referenceIndex) {
		$this->persistEntities();
		$query = $this->entityManager->createQuery('SELECT MIN(n.index) FROM TYPO3\TYPO3CR\Domain\Model\NodeData n WHERE n.parentPath = :parentPath AND n.index > :referenceIndex');
		$query->setParameter('parentPath', $parentPath);
		$query->setParameter('referenceIndex', $referenceIndex);
		return $query->getSingleScalarResult() ?: NULL;
	}

	/**
	 * Counts the number of nodes within the specified workspace
	 *
	 * Note: Also counts removed nodes
	 *
	 * @param Workspace $workspace The containing workspace
	 * @return integer The number of nodes found
	 */
	public function countByWorkspace(Workspace $workspace) {
		$query = $this->createQuery();
		$nodesInDatabase = $query->matching($query->equals('workspace', $workspace))->execute()->count();

		$nodesInMemory = 0;
		/** @var $node NodeData */
		foreach ($this->addedNodes as $node) {
			if ($node->getWorkspace() === $workspace) {
				$nodesInMemory ++;
			}
		}

		return $nodesInDatabase + $nodesInMemory;
	}

	/**
	 * Sorts the given nodes by their index
	 *
	 * @param array $nodes Nodes
	 * @return array Nodes sorted by index
	 */
	protected function sortNodesByIndex(array $nodes) {
		usort($nodes, function(NodeData $node1, NodeData $node2)
			{
				if ($node1->getIndex() < $node2->getIndex()) {
					return -1;
				} elseif ($node1->getIndex() > $node2->getIndex()) {
					return 1;
				} else {
					return strcmp($node1->getIdentifier(), $node2->getIdentifier());
				}
			});
		return $nodes;
	}

	/**
	 * Finds a single node by its parent and (optionally) by its node type
	 *
	 * @param string $parentPath Absolute path of the parent node
	 * @param string $nodeTypeFilter Filter the node type of the nodes, allows complex expressions (e.g. "TYPO3.Neos:Page", "!TYPO3.Neos:Page,TYPO3.Neos:Text" or NULL)
	 * @param Workspace $workspace The containing workspace
	 * @param boolean $includeRemovedNodes Should removed nodes be included in the result (defaults to FALSE)
	 * @return NodeData The node found or NULL
	 * @todo Check for workspace compliance
	 */
	public function findFirstByParentAndNodeType($parentPath, $nodeTypeFilter, Workspace $workspace, $includeRemovedNodes = FALSE) {
		$baseWorkspace = $workspace;
		while ($workspace !== NULL) {
			$query = $this->createQueryForFindByParentAndNodeType($parentPath, $nodeTypeFilter, $workspace, $includeRemovedNodes);
			$firstNodeFoundInThisWorkspace = $query->execute()->getFirst();
			if ($firstNodeFoundInThisWorkspace !== NULL) {
				$resultingNodeArray = $this->filterNodesOverlaidInBaseWorkspace(array($firstNodeFoundInThisWorkspace), $baseWorkspace);

				if (count($resultingNodeArray) > 0) {
					return current($resultingNodeArray);
				}
			}

			$workspace = $workspace->getBaseWorkspace();
		}
		return NULL;
	}

	/**
	 * Finds a single node by its parent and (optionally) by its node type
	 *
	 * @param string $parentPath Absolute path of the parent node
	 * @param string $nodeTypeFilter Filter the node type of the nodes, allows complex expressions (e.g. "TYPO3.Neos:Page", "!TYPO3.Neos:Page,TYPO3.Neos:Text" or NULL)
	 * @param ContextInterface $context The containing context
	 * @return NodeData The node found or NULL
	 */
	public function findFirstByParentAndNodeTypeInContext($parentPath, $nodeTypeFilter, ContextInterface $context) {
		$firstNode = $this->findFirstByParentAndNodeType($parentPath, $nodeTypeFilter, $context->getWorkspace(), $context->isRemovedContentShown());

		if ($firstNode !== NULL) {
			$firstNode = $this->nodeFactory->createFromNodeData($firstNode, $context);
		}

		return $firstNode;
	}

	/**
	 * Finds all nodes of the specified workspace lying on the path specified by
	 * (and including) the given starting point and end point and (optionally) a node type filter.
	 *
	 * If some node does not exist in the specified workspace, this function will
	 * try to find a corresponding node in one of the base workspaces (if any).
	 *
	 * @param string $pathStartingPoint Absolute path specifying the starting point
	 * @param string $pathEndPoint Absolute path specifying the end point
	 * @param Workspace $workspace The containing workspace
	 * @param boolean $includeRemovedNodes Should removed nodes be included in the result (defaults to FALSE)
	 * @param string $nodeTypeFilter Optional filter for the node type of the nodes, supports complex expressions (e.g. "TYPO3.Neos:Page", "!TYPO3.Neos:Page,TYPO3.Neos:Text" or NULL)
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeData> The nodes found on the given path
	 * @throws \InvalidArgumentException
	 * @todo findOnPath should probably not return child nodes of removed nodes unless removed nodes are included.
	 */
	public function findOnPath($pathStartingPoint, $pathEndPoint, Workspace $workspace, $includeRemovedNodes = FALSE, $nodeTypeFilter = NULL) {
		if ($pathStartingPoint !== substr($pathEndPoint, 0, strlen($pathStartingPoint))) {
			throw new \InvalidArgumentException('Invalid paths: path of starting point must first part of end point path.', 1284391181);
		}

		$foundNodes = array();
		$pathSegments = explode('/', substr($pathEndPoint, strlen($pathStartingPoint)));

		while ($workspace !== NULL && count($foundNodes) < count($pathSegments)) {
			$query = $this->createQuery();
			$pathConstraints = array();
			$constraintPath = $pathStartingPoint;

			foreach ($pathSegments as $pathSegment) {
				$constraintPath .= $pathSegment;
				$pathConstraints[] = $query->equals('path', $constraintPath);
				$constraintPath .= '/';
			}
			$constraints = array(
				$query->logicalOr($pathConstraints),
				$query->equals('workspace', $workspace)
			);
			if ($includeRemovedNodes === FALSE) {
				$constraints[] = $query->equals('removed', 0);
			}
			if ($nodeTypeFilter !== NULL) {
				$constraints = array_merge($constraints, $this->getNodeTypeFilterConstraints($query, $nodeTypeFilter));
			}
			$query->matching(
				$query->logicalAnd($constraints)
			);

			$nodesInThisWorkspace = $query->execute()->toArray();
			/** @var $node NodeData */
			foreach ($nodesInThisWorkspace as $node) {
				if (!isset($foundNodes[$node->getDepth()])) {
					$foundNodes[$node->getDepth()] = $node;
				}
			}
			$workspace = $workspace->getBaseWorkspace();
		}
		ksort($foundNodes);
		return array_values($foundNodes);
	}

	/**
	 * Find node data on a certain path and return them as Node objects in a given context.
	 *
	 * @param string $pathStartingPoint
	 * @param string $pathEndPoint
	 * @param ContextInterface $context
	 * @param string $nodeTypeFilter Optional filter for the type of the nodes, supports complex expressions (e.g. "TYPO3.Neos:Page", "!TYPO3.Neos:Page,TYPO3.Neos:Text" or NULL)
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeData>
	 * @see findOnPath
	 */
	public function findOnPathInContext($pathStartingPoint, $pathEndPoint, ContextInterface $context, $nodeTypeFilter = NULL) {
		$nodeDataElements = $this->findOnPath($pathStartingPoint, $pathEndPoint, $context->getWorkspace(), $context->isRemovedContentShown(), $nodeTypeFilter);
		$finalNodes = array();
		foreach ($nodeDataElements as $nodeData) {
			$node = $this->nodeFactory->createFromNodeData($nodeData, $context);
			if ($node !== NULL) {
				$finalNodes[] = $node;
			}
		}

		return $finalNodes;
	}

	/**
	 * Flushes the addedNodes and removedNodes registry.
	 *
	 * This method is (and should only be) used as a slot to the allObjectsPersisted
	 * signal.
	 *
	 * @return void
	 */
	public function flushNodeRegistry() {
		$this->addedNodes = new \SplObjectStorage();
		$this->removedNodes = new \SplObjectStorage();
	}

	/**
	 * Creates a query for finding nodes by their parent and (optionally) node type.
	 *
	 * The node type filter syntax is simple: allowed node type names are listed,
	 * separated by comma. An exclamation mark as first character of a node type name
	 * excludes it. Inheritance is taken into account, all sub-types of a given type are
	 * allowed as well.
	 *
	 * Does not traverse base workspaces, returns a query only matching nodes of
	 * the given workspace.
	 *
	 * @param string $parentPath Absolute path of the parent node
	 * @param string $nodeTypeFilter Filter the node type of the nodes, allows complex expressions (e.g. "TYPO3.Neos:Page", "!TYPO3.Neos:Page,TYPO3.Neos:Text" or NULL)
	 * @param Workspace $workspace The containing workspace
	 * @param boolean $includeRemovedNodes Should removed nodes be included in the result (defaults to FALSE)
	 * @param boolean $recursive Switch to make the Query recursive
	 * @throws \InvalidArgumentException
	 * @return QueryInterface The query
	 */
	protected function createQueryForFindByParentAndNodeType($parentPath, $nodeTypeFilter, \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace, $includeRemovedNodes = FALSE, $recursive = FALSE) {
		if (strlen($parentPath) === 0 || ($parentPath !== '/' && ($parentPath[0] !== '/' || substr($parentPath, -1, 1) === '/'))) {
			throw new \InvalidArgumentException('"' . $parentPath . '" is not a valid path: must start but not end with a slash.', 1284985610);
		}

		$query = $this->createQuery();
		$constraints = array(
			$query->equals('workspace', $workspace),
		);
		if ($recursive !== TRUE) {
			$constraints[] = $query->equals('parentPath', $parentPath);
		} else {
			$constraints[] = $query->like('parentPath', $parentPath . '%');
		}

		if ($includeRemovedNodes === FALSE) {
			$constraints[] = $query->equals('removed', (integer)$includeRemovedNodes);
		}

		if ($nodeTypeFilter !== NULL) {
			$constraints = array_merge($constraints, $this->getNodeTypeFilterConstraints($query, $nodeTypeFilter));
		}

		$query->matching($query->logicalAnd($constraints));
		return $query;
	}

	/**
	 * @param QueryInterface $query
	 * @param $nodeTypeFilter
	 * @return array
	 */
	protected function getNodeTypeFilterConstraints(QueryInterface $query, $nodeTypeFilter) {
		$includeNodeTypeConstraints = array();
		$excludeNodeTypeConstraints = array();
		$nodeTypeFilterParts = Arrays::trimExplode(',', $nodeTypeFilter);
		foreach ($nodeTypeFilterParts as $nodeTypeFilterPart) {
			$nodeTypeFilterPart = trim($nodeTypeFilterPart);
			if (strpos($nodeTypeFilterPart, '!') === 0) {
				$negate = TRUE;
				$nodeTypeFilterPart = substr($nodeTypeFilterPart, 1);
			} else {
				$negate = FALSE;
			}
			$nodeTypeFilterPartSubTypes = array_merge(array($nodeTypeFilterPart), $this->nodeTypeManager->getSubNodeTypes($nodeTypeFilterPart));

			foreach ($nodeTypeFilterPartSubTypes as $nodeTypeFilterPartSubType) {
				if ($negate === TRUE) {
					$excludeNodeTypeConstraints[] = $query->logicalNot($query->equals('nodeType', $nodeTypeFilterPartSubType));
				} else {
					$includeNodeTypeConstraints[] = $query->equals('nodeType', $nodeTypeFilterPartSubType);
				}
			}
		}

		$constraints = $excludeNodeTypeConstraints;
		if (count($includeNodeTypeConstraints) > 0) {
			$constraints[] = $query->logicalOr($includeNodeTypeConstraints);
		}

		return $constraints;
	}

	/**
	 * Iterates of the array of objects and removes all those which have recently been removed from the repository,
	 * but whose removal has not yet been persisted.
	 *
	 * Technically this is a check of the given array against $this->removedNodes.
	 *
	 * @param array &$objects An array of objects to filter, passed by reference.
	 * @return void
	 */
	protected function filterOutRemovedObjects(array &$objects) {
		foreach ($objects as $index => $object) {
			if ($this->removedNodes->contains($object)) {
				unset($objects[$index]);
			}
		}
	}

	/**
	 * Removes NodeData with the removed property set from the given array.
	 *
	 * @param array $nodes NodeData including removed entries
	 * @return array NodeData with removed entries removed
	 */
	protected function filterRemovedNodes($nodes) {
		return array_filter($nodes, function(NodeData $node) {
			return !$node->isRemoved();
		});
	}

	/**
	 * Apply limit and offset to the array of nodes.
	 *
	 * @param array $nodes
	 * @param integer $limit
	 * @param integer $offset
	 * @return array
	 */
	protected function applyLimitAndOffset(array $nodes, $limit = NULL, $offset = 0) {
		return array_slice($nodes, $offset, $limit);
	}

	/**
	 * Persists all entities managed by the repository and all cascading dependencies
	 *
	 * @return void
	 */
	public function persistEntities() {
		foreach ($this->entityManager->getUnitOfWork()->getIdentityMap() as $className => $entities) {
			if ($className === $this->entityClassName) {
				foreach ($entities as $entityToPersist) {
					$this->entityManager->flush($entityToPersist);
				}
				$this->emitRepositoryObjectsPersisted();
				break;
			}
		}
	}

	/**
	 * Signals that persistEntities() in this repository finished correctly.
	 *
	 * @Flow\Signal
	 * @return void
	 */
	protected function emitRepositoryObjectsPersisted() {
	}
}
?>
