<?php
namespace Neos\ContentRepository\Domain\Repository;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\Repository;
use Neos\Utility\Arrays;
use Neos\Utility\Unicode\Functions as UnicodeFunctions;
use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Domain\Utility\NodePaths;
use Neos\ContentRepository\Exception;
use Psr\Log\LoggerInterface;

/**
 * A purely internal repository for NodeData storage
 *
 * DO NOT USE outside the ContentRepository package!
 *
 * The ContextFactory can be used to create a Context that allows to find Node instances that act as the
 * public API to the ContentRepository.
 *
 * @Flow\Scope("singleton")
 */
class NodeDataRepository extends Repository
{
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
     * Doctrine's Entity Manager.
     *
     * @Flow\Inject
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @Flow\Inject
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @var array
     */
    protected $highestIndexCache = [];

    /**
     * @var array
     */
    protected $defaultOrderings = [
        'index' => QueryInterface::ORDER_ASCENDING
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->addedNodes = new \SplObjectStorage();
        $this->removedNodes = new \SplObjectStorage();
        parent::__construct();
    }

    /**
     * Adds a NodeData object to this repository.
     *
     * This repository keeps track of added and removed nodes (additionally to the other Unit of Work)
     * in order to find in-memory nodes.
     *
     * @param object $object The object to add
     * @return void
     * @api
     */
    public function add($object): void
    {
        if ($this->removedNodes->contains($object)) {
            $this->removedNodes->detach($object);
        }
        if (!$this->addedNodes->contains($object)) {
            $this->addedNodes->attach($object);
        }
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
    public function remove($object): void
    {
        if ($object instanceof NodeInterface) {
            $object = $object->getNodeData();
        }
        if ($this->addedNodes->contains($object)) {
            $this->addedNodes->detach($object);
        }
        if (!$this->removedNodes->contains($object)) {
            $this->removedNodes->attach($object);
        }
        parent::remove($object);
    }

    public function findByNodeIdentifier($nodeIdentifier)
    {
        $query = $this->createQuery();
        return $query->matching($query->equals('identifier', $nodeIdentifier))->execute();
    }

    /**
     * Find a single node by exact path.
     *
     * @param string $path Absolute path of the node
     * @param Workspace $workspace The containing workspace
     * @param array $dimensions An array of dimensions with array of ordered values to use for fallback matching
     * @param boolean|NULL $removedNodes Include removed nodes, NULL (all), false (no removed nodes) or true (only removed nodes)
     * @throws \InvalidArgumentException
     * @return NodeData The matching node if found, otherwise NULL
     */
    public function findOneByPath($path, Workspace $workspace, array $dimensions = null, $removedNodes = false)
    {
        if ($path === '/') {
            return $workspace->getRootNodeData();
        }

        $workspaces = $this->collectWorkspaceAndAllBaseWorkspaces($workspace);
        $nodes = $this->findRawNodesByPath($path, $workspace, $dimensions);
        $dimensions = $dimensions === null ? [] : $dimensions;
        $foundNodes = $this->reduceNodeVariantsByWorkspacesAndDimensions($nodes, $workspaces, $dimensions);
        $foundNodes = $this->filterNodeDataByBestMatchInContext($foundNodes, $workspace, $dimensions, $removedNodes);

        if ($removedNodes === true) {
            $foundNodes = $this->onlyRemovedNodes($foundNodes);
        } elseif ($removedNodes === false) {
            $foundNodes = $this->withoutRemovedNodes($foundNodes);
        }

        if ($foundNodes !== []) {
            return reset($foundNodes);
        }

        return null;
    }

    /**
     * Find a shadow node by exact path
     *
     * @param string $path
     * @param Workspace $workspace
     * @param array|null $dimensions
     * @return NodeData|null
     */
    public function findShadowNodeByPath($path, Workspace $workspace, array $dimensions = null)
    {
        $workspaces = $this->collectWorkspaceAndAllBaseWorkspaces($workspace);
        $nodes = $this->findRawNodesByPath($path, $workspace, $dimensions, true);
        $dimensions = $dimensions === null ? [] : $dimensions;
        $foundNodes = $this->reduceNodeVariantsByWorkspacesAndDimensions($nodes, $workspaces, $dimensions);
        $foundNodes = $this->onlyRemovedNodes($foundNodes);

        if ($foundNodes !== []) {
            return reset($foundNodes);
        }

        return null;
    }

    /**
     * This finds nodes by path and delivers a raw, unfiltered result.
     *
     * To get a "usable" set of nodes, filtering by workspaces, dimensions and
     * removed nodes must be done on the result.
     *
     * @param string $path
     * @param Workspace $workspace
     * @param array|null $dimensions
     * @param boolean $onlyShadowNodes
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function findRawNodesByPath($path, Workspace $workspace, array $dimensions = null, $onlyShadowNodes = false)
    {
        $path = strtolower($path);
        if ($path === '' || ($path !== '/' && ($path[0] !== '/' || substr($path, -1, 1) === '/'))) {
            throw new \InvalidArgumentException('"' . $path . '" is not a valid path: must start but not end with a slash.', 1284985489);
        }

        if ($path === '/') {
            return [$workspace->getRootNodeData()];
        }

        $addedNodes = [];
        $workspaces = [];
        while ($workspace !== null) {
            /** @var $node NodeData */
            foreach ($this->addedNodes as $node) {
                if (($node->getPath() === $path && $node->matchesWorkspaceAndDimensions($workspace, $dimensions)) && ($onlyShadowNodes === false || $node->isInternal())) {
                    $addedNodes[] = $node;
                    // removed nodes don't matter here because due to the identity map the right object will be returned from the query and will have "removed" set.
                }
            }

            $workspaces[] = $workspace;
            $workspace = $workspace->getBaseWorkspace();
        }
        $queryBuilder = $this->createQueryBuilder($workspaces);
        if ($dimensions !== null) {
            $this->addDimensionJoinConstraintsToQueryBuilder($queryBuilder, $dimensions);
        }
        $this->addPathConstraintToQueryBuilder($queryBuilder, $path);
        if ($onlyShadowNodes) {
            $queryBuilder->andWhere('n.movedTo IS NOT NULL AND n.removed = TRUE');
        }

        $query = $queryBuilder->getQuery();
        $nodes = $query->getResult();
        return array_merge($nodes, $addedNodes);
    }

    /**
     * Finds a node by its path and context.
     *
     * If the node does not exist in the specified context's workspace, this function will
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
     * @param Context $context The containing context
     * @return NodeInterface|NULL The matching node if found, otherwise NULL
     * @throws \InvalidArgumentException
     */
    public function findOneByPathInContext($path, Context $context)
    {
        $node = $this->findOneByPath($path, $context->getWorkspace(), $context->getDimensions(), ($context->isRemovedContentShown() ? null : false));
        if ($node !== null) {
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
     * @param array $dimensions An array of dimensions with array of ordered values to use for fallback matching
     * @param bool $removedNodes If shadow nodes should be considered while finding the specified node
     * @return NodeData The matching node if found, otherwise NULL
     */
    public function findOneByIdentifier($identifier, Workspace $workspace, array $dimensions = null, $removedNodes = false)
    {
        $workspaces = [];
        while ($workspace !== null) {
            /** @var $node NodeData */
            foreach ($this->addedNodes as $node) {
                if ($node->getIdentifier() === $identifier && $node->matchesWorkspaceAndDimensions($workspace, $dimensions)) {
                    return $node;
                }
            }

            /** @var $node NodeData */
            foreach ($this->removedNodes as $node) {
                if ($node->getIdentifier() === $identifier && $node->matchesWorkspaceAndDimensions($workspace, $dimensions)) {
                    return null;
                }
            }

            $workspaces[] = $workspace;
            $workspace = $workspace->getBaseWorkspace();
        }

        $queryBuilder = $this->createQueryBuilder($workspaces);
        if ($removedNodes === false) {
            $queryBuilder->andWhere('n.movedTo IS NULL OR n.removed = FALSE');
        } else {
            $queryBuilder->andWhere('n.movedTo IS NULL');
        }
        if ($dimensions !== null) {
            $this->addDimensionJoinConstraintsToQueryBuilder($queryBuilder, $dimensions);
        } else {
            $dimensions = [];
        }
        $this->addIdentifierConstraintToQueryBuilder($queryBuilder, $identifier);

        $query = $queryBuilder->getQuery();
        $nodes = $query->getResult();

        $foundNodes = $this->reduceNodeVariantsByWorkspacesAndDimensions($nodes, $workspaces, $dimensions);
        if ($removedNodes === false) {
            $foundNodes = $this->withoutRemovedNodes($foundNodes);
        }

        if ($foundNodes !== []) {
            return reset($foundNodes);
        }

        return null;
    }

    /**
     * Find all objects and return an IterableResult
     *
     * @return IterableResult
     */
    public function findAllIterator()
    {
        /** @var \Doctrine\ORM\QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();
        return $queryBuilder
            ->select('node')
            ->from($this->getEntityClassName(), 'node')
            ->getQuery()->iterate();
    }

    /**
     * Iterate over an IterableResult and return a Generator
     *
     * This method is useful for batch processing a huge result set.
     *
     * @param IterableResult $iterator
     * @param callable $callback
     * @return \Generator
     */
    public function iterate(IterableResult $iterator, callable $callback = null)
    {
        $iteration = 0;
        foreach ($iterator as $object) {
            $object = current($object);
            yield $object;
            if ($callback !== null) {
                call_user_func($callback, $iteration, $object);
            }

            $iteration++;
        }
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
     * @param NodeInterface $referenceNode The reference node. Mandatory for POSITION_BEFORE and POSITION_AFTER
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setNewIndex(NodeData $node, $position, NodeInterface $referenceNode = null)
    {
        $parentPath = $node->getParentPath();

        switch ($position) {
            case self::POSITION_BEFORE:
                if ($referenceNode === null) {
                    throw new \InvalidArgumentException('The reference node must be specified for POSITION_BEFORE.', 1317198857);
                }
                $referenceIndex = $referenceNode->getIndex();
                $nextLowerIndex = $this->findNextLowerIndex($parentPath, $referenceIndex);
                if ($nextLowerIndex === null) {
                    // FIXME: $nextLowerIndex returns 0 and not NULL in case no lower index is found. So this case seems to be
                    // never executed. We need to check that again!
                    $newIndex = (integer)round($referenceIndex / 2);
                } elseif ($nextLowerIndex < ($referenceIndex - 1)) {
                    // there is free space left between $referenceNode and preceding sibling.
                    $newIndex = (integer)round($nextLowerIndex + (($referenceIndex - $nextLowerIndex) / 2));
                } else {
                    // there is no free space left between $referenceNode and following sibling -> we have to make room!
                    $this->openIndexSpace($parentPath, $referenceIndex);
                    $referenceIndex = $referenceNode->getIndex();
                    $nextLowerIndex = $this->findNextLowerIndex($parentPath, $referenceIndex);
                    if ($nextLowerIndex === null) {
                        $newIndex = (integer)round($referenceIndex / 2);
                    } else {
                        $newIndex = (integer)round($nextLowerIndex + (($referenceIndex - $nextLowerIndex) / 2));
                    }
                }
                break;
            case self::POSITION_AFTER:
                if ($referenceNode === null) {
                    throw new \InvalidArgumentException('The reference node must be specified for POSITION_AFTER.', 1317198858);
                }
                $referenceIndex = $referenceNode->getIndex();
                $nextHigherIndex = $this->findNextHigherIndex($parentPath, $referenceIndex);
                if ($nextHigherIndex === null) {
                    // $referenceNode is last node, so we can safely add an index at the end by incrementing the reference index.
                    $newIndex = $referenceIndex + 100;
                    $this->setHighestIndexInParentPath($parentPath, $newIndex);
                } elseif ($nextHigherIndex > ($referenceIndex + 1)) {
                    // $referenceNode is not last node, but there is free space left between $referenceNode and following sibling.
                    $newIndex = (integer)round($referenceIndex + (($nextHigherIndex - $referenceIndex) / 2));
                } else {
                    // $referenceNode is not last node, and no free space is left -> we have to make room after the reference node!
                    $this->openIndexSpace($parentPath, $referenceIndex + 1);
                    $nextHigherIndex = $this->findNextHigherIndex($parentPath, $referenceIndex);
                    if ($nextHigherIndex === null) {
                        $newIndex = $referenceIndex + 100;
                        $this->setHighestIndexInParentPath($parentPath, $newIndex);
                    } else {
                        $newIndex = (integer)round($referenceIndex + (($nextHigherIndex - $referenceIndex) / 2));
                    }
                }
                break;
            case self::POSITION_LAST:
                $nextFreeIndex = $this->findNextFreeIndexInParentPath($parentPath);
                $newIndex = $nextFreeIndex;
                break;
            default:
                throw new \InvalidArgumentException('Invalid position for new node index given.', 1329729088);
        }

        $node->setIndex($newIndex);
    }

    /**
     * Finds recursively nodes by its parent and (optionally) by its node type.
     *
     * @see findByParentAndNodeType()
     *
     * @param string $parentPath Absolute path of the parent node
     * @param string $nodeTypeFilter Filter the node type of the nodes, allows complex expressions (e.g. "Neos.Neos:Page", "!Neos.Neos:Page,Neos.Neos:Text" or NULL)
     * @param Workspace $workspace The containing workspace
     * @param array $dimensions An array of dimensions to dimension values
     * @param boolean $removedNodes If true the result has ONLY removed nodes. If false removed nodes are NOT inside the result. If NULL the result contains BOTH removed and non-removed nodes. (defaults to false)
     * @return array<\Neos\ContentRepository\Domain\Model\NodeData> The nodes found on the given path
     */
    public function findByParentAndNodeTypeRecursively($parentPath, $nodeTypeFilter, Workspace $workspace, array $dimensions = null, $removedNodes = false)
    {
        return $this->findByParentAndNodeType($parentPath, $nodeTypeFilter, $workspace, $dimensions, $removedNodes, true);
    }

    /**
     * Finds nodes by its parent and (optionally) by its node type.
     * If the $recursive flag is set to true, all matching nodes underneath $parentPath will be returned
     *
     * Note: Filters out removed nodes.
     *
     * The primary sort key is the *index*, the secondary sort key (if indices are equal, which
     * only occurs in very rare cases) is the *identifier*.
     *
     * @param string $parentPath Absolute path of the parent node
     * @param string $nodeTypeFilter Filter the node type of the nodes, allows complex expressions (e.g. "Neos.Neos:Page", "!Neos.Neos:Page,Neos.Neos:Text" or NULL)
     * @param Workspace $workspace The containing workspace
     * @param array $dimensions An array of dimensions to dimension values
     * @param boolean $removedNodes If true the result has ONLY removed nodes. If false removed nodes are NOT inside the result. If NULL the result contains BOTH removed and non-removed nodes. (defaults to false)
     * @param boolean $recursive If true *all* matching nodes underneath the specified parent path are returned
     * @return array<\Neos\ContentRepository\Domain\Model\NodeData> The nodes found on the given path
     * @todo Improve implementation by using DQL
     */
    public function findByParentAndNodeType($parentPath, $nodeTypeFilter, Workspace $workspace, array $dimensions = null, $removedNodes = false, $recursive = false)
    {
        $parentPath = strtolower($parentPath);
        $foundNodes = $this->getNodeDataForParentAndNodeType($parentPath, $nodeTypeFilter, $workspace, $dimensions, $removedNodes, $recursive);

        $childNodeDepth = NodePaths::getPathDepth($parentPath) + 1;
        $constraints = $this->getNodeTypeFilterConstraintsForDql($nodeTypeFilter);
        /** @var $addedNode NodeData */
        foreach ($this->addedNodes as $addedNode) {
            if (
                (($recursive && $addedNode->getDepth() >= $childNodeDepth) || $addedNode->getDepth() === $childNodeDepth) &&
                (($recursive && NodePaths::isSubPathOf($addedNode->getPath(), $parentPath)) || NodePaths::getParentPath($addedNode->getPath()) === $parentPath) &&
                $addedNode->matchesWorkspaceAndDimensions($workspace, $dimensions)
            ) {
                $nodeType = $addedNode->getNodeType();
                $disallowed = false;
                foreach ($constraints['includeNodeTypes'] as $includeNodeType) {
                    if (!$nodeType->isOfType($includeNodeType)) {
                        $disallowed = true;
                    }
                }
                foreach ($constraints['excludeNodeTypes'] as $excludeNodeTypes) {
                    if ($nodeType->isOfType($excludeNodeTypes)) {
                        $disallowed = true;
                    }
                }
                if ($disallowed === false) {
                    $foundNodes[$addedNode->getIdentifier()] = $addedNode;
                }
            }
        }
        /** @var $removedNode NodeData */
        foreach ($this->removedNodes as $removedNode) {
            if (
                (($recursive && $removedNode->getDepth() >= $childNodeDepth) || $removedNode->getDepth() === $childNodeDepth) &&
                (($recursive && NodePaths::isSubPathOf($removedNode->getPath(), $parentPath)) || NodePaths::getParentPath($removedNode->getPath()) === $parentPath) &&
                $removedNode->matchesWorkspaceAndDimensions($workspace, $dimensions)
            ) {
                if (isset($foundNodes[$removedNode->getIdentifier()])) {
                    unset($foundNodes[$removedNode->getIdentifier()]);
                }
            }
        }

        $foundNodes = $this->sortNodesByIndex($foundNodes);
        return $foundNodes;
    }

    /**
     * Internal method
     *
     * @param string $parentPath
     * @param string $nodeTypeFilter
     * @param Workspace $workspace
     * @param array $dimensions
     * @param boolean|NULL $removedNodes
     * @param boolean $recursive
     * @return array
     */
    protected function getNodeDataForParentAndNodeType($parentPath, $nodeTypeFilter, Workspace $workspace, array $dimensions = null, $removedNodes = false, $recursive = false)
    {
        $workspaces = $this->collectWorkspaceAndAllBaseWorkspaces($workspace);

        $queryBuilder = $this->createQueryBuilder($workspaces);
        if ($dimensions !== null) {
            $this->addDimensionJoinConstraintsToQueryBuilder($queryBuilder, $dimensions);
        } else {
            $dimensions = [];
        }
        $this->addParentPathConstraintToQueryBuilder($queryBuilder, $parentPath, $recursive);
        if ($nodeTypeFilter !== null) {
            $this->addNodeTypeFilterConstraintsToQueryBuilder($queryBuilder, $nodeTypeFilter);
        }

        $query = $queryBuilder->getQuery();
        $nodes = $query->getResult();

        $foundNodes = $this->reduceNodeVariantsByWorkspacesAndDimensions($nodes, $workspaces, $dimensions);
        $foundNodes = $this->filterNodeDataByBestMatchInContext($foundNodes, $workspaces[0], $dimensions, $removedNodes);

        if ($removedNodes === true) {
            $foundNodes = $this->onlyRemovedNodes($foundNodes);
        } elseif ($removedNodes === false) {
            $foundNodes = $this->withoutRemovedNodes($foundNodes);
        }

        return $foundNodes;
    }

    /**
     * Find NodeData by parent path without any dimension reduction and grouping by identifier
     *
     * Only used internally for setting the path of all child nodes
     *
     * @param string $parentPath
     * @param Workspace $workspace
     * @return array<\Neos\ContentRepository\Domain\Model\NodeData> A unreduced array of NodeData
     */
    public function findByParentWithoutReduce($parentPath, Workspace $workspace)
    {
        $parentPath = strtolower($parentPath);
        $workspaces = $this->collectWorkspaceAndAllBaseWorkspaces($workspace);

        $queryBuilder = $this->createQueryBuilder($workspaces);
        $this->addParentPathConstraintToQueryBuilder($queryBuilder, $parentPath);

        $query = $queryBuilder->getQuery();
        $foundNodes = $query->getResult();

        $childNodeDepth = NodePaths::getPathDepth($parentPath) + 1;
        /** @var $addedNode NodeData */
        foreach ($this->addedNodes as $addedNode) {
            if ($addedNode->getDepth() === $childNodeDepth && NodePaths::getParentPath($addedNode->getPath()) === $parentPath && in_array($addedNode->getWorkspace(), $workspaces)) {
                $foundNodes[] = $addedNode;
            }
        }
        /** @var $removedNode NodeData */
        foreach ($this->removedNodes as $removedNode) {
            if ($removedNode->getDepth() === $childNodeDepth && NodePaths::getParentPath($removedNode->getPath()) === $parentPath && in_array($removedNode->getWorkspace(), $workspaces)) {
                $foundNodes = array_filter($foundNodes, function ($nodeData) use ($removedNode) {
                    return $nodeData !== $removedNode;
                });
            }
        }

        return $foundNodes;
    }

    /**
     * Find NodeData by identifier path without any dimension reduction
     *
     * Only used internally for finding whether the node exists in another dimension
     *
     * @param string $identifier
     * @param Workspace $workspace
     * @return array<\Neos\ContentRepository\Domain\Model\NodeData> A unreduced array of NodeData
     */
    public function findByIdentifierWithoutReduce($identifier, Workspace $workspace)
    {
        $workspaces = $this->collectWorkspaceAndAllBaseWorkspaces($workspace);

        $queryBuilder = $this->createQueryBuilder($workspaces);
        $this->addIdentifierConstraintToQueryBuilder($queryBuilder, $identifier);

        $query = $queryBuilder->getQuery();
        $foundNodes = $query->getResult();

        return $foundNodes;
    }

    /**
     * Finds nodes by its parent and (optionally) by its node type given a Context
     *
     * TODO Move to a new Node operation getDescendantNodes(...)
     *
     * @param string $parentPath Absolute path of the parent node
     * @param string $nodeTypeFilter Filter the node type of the nodes, allows complex expressions (e.g. "Neos.Neos:Page", "!Neos.Neos:Page,Neos.Neos:Text" or NULL)
     * @param Context $context The containing workspace
     * @param boolean $recursive If true *all* matching nodes underneath the specified parent path are returned
     * @return array<\Neos\ContentRepository\Domain\Model\NodeInterface> The nodes found on the given path
     */
    public function findByParentAndNodeTypeInContext($parentPath, $nodeTypeFilter, Context $context, $recursive = false)
    {
        $nodeDataElements = $this->findByParentAndNodeType($parentPath, $nodeTypeFilter, $context->getWorkspace(), $context->getDimensions(), ($context->isRemovedContentShown() ? null : false), $recursive);
        $finalNodes = [];
        foreach ($nodeDataElements as $nodeData) {
            $node = $this->nodeFactory->createFromNodeData($nodeData, $context);
            if ($node !== null) {
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
     * @param string $nodeTypeFilter Filter the node type of the nodes, allows complex expressions (e.g. "Neos.Neos:Page", "!Neos.Neos:Page,Neos.Neos:Text" or NULL)
     * @param Workspace $workspace The containing workspace
     * @param array $dimensions
     * @param boolean $includeRemovedNodes Should removed nodes be included in the result (defaults to false)
     * @return integer The number of nodes a similar call to findByParentAndNodeType() would return without any pending added nodes
     */
    public function countByParentAndNodeType($parentPath, $nodeTypeFilter, Workspace $workspace, array $dimensions = null, $includeRemovedNodes = false)
    {
        return count($this->findByParentAndNodeType($parentPath, $nodeTypeFilter, $workspace, $dimensions, $includeRemovedNodes));
    }

    /**
     * Make room in the sortindex-index space of a given path in preparation to inserting a node.
     * All indices that are greater or equal to the given referenceIndex are incremented by 100
     *
     * @param string $parentPath
     * @param integer $referenceIndex
     * @throws Exception\NodeException
     */
    protected function openIndexSpace($parentPath, $referenceIndex)
    {
        $this->systemLogger->info(sprintf('Opening sortindex space after index %s at path %s.', $referenceIndex, $parentPath), LogEnvironment::fromMethodName(__METHOD__));

        /** @var Query $query */
        $query = $this->entityManager->createQuery('SELECT n.Persistence_Object_Identifier identifier, n.index, n.path FROM Neos\ContentRepository\Domain\Model\NodeData n WHERE n.parentPathHash = :parentPathHash ORDER BY n.index ASC');
        $query->setParameter('parentPathHash', md5($parentPath));

        $nodesOnLevel = [];
        /** @var $node NodeData */
        foreach ($query->getArrayResult() as $node) {
            $nodesOnLevel[] = [
                'identifier' => $node['identifier'],
                'path' => $node['path'],
                'index' => $node['index']
            ];
        }

        /** @var $node NodeData */
        foreach ($this->addedNodes as $node) {
            if ($node->getParentPath() === $parentPath) {
                $nodesOnLevel[] = [
                    'addedNode' => $node,
                    'path' => $node->getPath(),
                    'index' => $node->getIndex()
                ];
            }
        }

        $query = $this->entityManager->createQuery('UPDATE Neos\ContentRepository\Domain\Model\NodeData n SET n.index = :index WHERE n.Persistence_Object_Identifier = :identifier');
        foreach ($nodesOnLevel as $node) {
            if ($node['index'] < $referenceIndex) {
                continue;
            }
            $newIndex = $node['index'] + 100;
            if ($newIndex > self::INDEX_MAXIMUM) {
                throw new Exception\NodeException(sprintf('Reached maximum node index of %s while setting index of node %s.', $newIndex, $node['path']), 1317140402);
            }
            if (isset($node['addedNode'])) {
                $node['addedNode']->setIndex($newIndex);
            } else {
                if ($entity = $this->entityManager->getUnitOfWork()->tryGetById($node['identifier'], NodeData::class)) {
                    $entity->setIndex($newIndex);
                }
                $query->setParameter('index', $newIndex);
                $query->setParameter('identifier', $node['identifier']);
                $query->execute();
            }
        }
    }

    /**
     * Finds the next free index on the level below the given parent path
     * across all workspaces.
     *
     * @param string $parentPath Path of the parent node specifying the level in the node tree
     * @return integer The next available index
     */
    protected function findNextFreeIndexInParentPath($parentPath)
    {
        if (!isset($this->highestIndexCache[$parentPath])) {
            /** @var \Doctrine\ORM\Query $query */
            $query = $this->entityManager->createQuery('SELECT MAX(n.index) FROM Neos\ContentRepository\Domain\Model\NodeData n WHERE n.parentPathHash = :parentPathHash');
            $query->setParameter('parentPathHash', md5($parentPath));
            $this->highestIndexCache[$parentPath] = $query->getSingleScalarResult() ?: 0;
        }

        $this->highestIndexCache[$parentPath] += 100;

        return $this->highestIndexCache[$parentPath];
    }

    /**
     * @param string $parentPath
     * @param integer $highestIndex
     * @return void
     */
    protected function setHighestIndexInParentPath($parentPath, $highestIndex)
    {
        $this->highestIndexCache[$parentPath] = $highestIndex;
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
    protected function findNextLowerIndex($parentPath, $referenceIndex)
    {
        $this->persistEntities();
        /** @var \Doctrine\ORM\Query $query */
        $query = $this->entityManager->createQuery('SELECT MAX(n.index) FROM Neos\ContentRepository\Domain\Model\NodeData n WHERE n.parentPathHash = :parentPathHash AND n.index < :referenceIndex');
        $query->setParameter('parentPathHash', md5($parentPath));
        $query->setParameter('referenceIndex', $referenceIndex);

        return $query->getSingleScalarResult() ?: 0;
    }

    /**
     * Returns the next-higher-index seen from the given reference index in the
     * level below the specified parent path. If no node with a higher than the
     * given index exists at that level, NULL is returned.
     *
     * The result is determined workspace-agnostic.
     *
     * @param string $parentPath Path of the parent node specifying the level in the node tree
     * @param integer $referenceIndex Index of a known node
     * @return integer The currently next higher index or NULL if no node with a higher index exists
     */
    protected function findNextHigherIndex($parentPath, $referenceIndex)
    {
        if (isset($this->highestIndexCache[$parentPath]) && $this->highestIndexCache[$parentPath] === $referenceIndex) {
            null;
        }
        $this->persistEntities();
        /** @var \Doctrine\ORM\Query $query */
        $query = $this->entityManager->createQuery('SELECT MIN(n.index) FROM Neos\ContentRepository\Domain\Model\NodeData n WHERE n.parentPathHash = :parentPathHash AND n.index > :referenceIndex');
        $query->setParameter('parentPathHash', md5($parentPath));
        $query->setParameter('referenceIndex', $referenceIndex);

        return $query->getSingleScalarResult() ?: null;
    }

    /**
     * Counts the number of nodes within the specified workspace
     *
     * Note: Also counts removed nodes
     *
     * @param Workspace $workspace The containing workspace
     * @return integer The number of nodes found
     */
    public function countByWorkspace(Workspace $workspace)
    {
        $query = $this->createQuery();
        $nodesInDatabase = $query->matching($query->equals('workspace', $workspace))->execute()->count();

        $nodesInMemory = 0;
        /** @var $node NodeData */
        foreach ($this->addedNodes as $node) {
            if ($node->getWorkspace()->getName() === $workspace->getName()) {
                $nodesInMemory++;
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
    protected function sortNodesByIndex(array $nodes)
    {
        usort($nodes, function (NodeData $node1, NodeData $node2) {
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
     * @param string $nodeTypeFilter Filter the node type of the nodes, allows complex expressions (e.g. "Neos.Neos:Page", "!Neos.Neos:Page,Neos.Neos:Text" or NULL)
     * @param array $dimensions
     * @param Workspace $workspace The containing workspace
     * @param boolean $removedNodes If true the result has ONLY removed nodes. If false removed nodes are NOT inside the result. If NULL the result contains BOTH removed and non-removed nodes. (defaults to false)
     * @return NodeData The node found or NULL
     */
    public function findFirstByParentAndNodeType($parentPath, $nodeTypeFilter, Workspace $workspace, array $dimensions, $removedNodes = false)
    {
        $nodes = $this->findByParentAndNodeType($parentPath, $nodeTypeFilter, $workspace, $dimensions, $removedNodes);

        if ($nodes !== []) {
            return reset($nodes);
        }

        return null;
    }

    /**
     * Finds a single node by its parent and (optionally) by its node type
     *
     * @param string $parentPath Absolute path of the parent node
     * @param string $nodeTypeFilter Filter the node type of the nodes, allows complex expressions (e.g. "Neos.Neos:Page", "!Neos.Neos:Page,Neos.Neos:Text" or NULL)
     * @param Context $context The containing context
     * @return NodeInterface The node found or NULL
     */
    public function findFirstByParentAndNodeTypeInContext($parentPath, $nodeTypeFilter, Context $context)
    {
        $firstNode = $this->findFirstByParentAndNodeType($parentPath, $nodeTypeFilter, $context->getWorkspace(), $context->getDimensions(), ($context->isRemovedContentShown() ? null : false));

        if ($firstNode !== null) {
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
     * @param array $dimensions Array of dimensions to array of dimension values
     * @param boolean $includeRemovedNodes Should removed nodes be included in the result (defaults to false)
     * @param string $nodeTypeFilter Optional filter for the node type of the nodes, supports complex expressions (e.g. "Neos.Neos:Page", "!Neos.Neos:Page,Neos.Neos:Text" or NULL)
     * @throws \InvalidArgumentException
     * @return array<\Neos\ContentRepository\Domain\Model\NodeData> The nodes found on the given path
     * @todo findOnPath should probably not return child nodes of removed nodes unless removed nodes are included.
     */
    public function findOnPath($pathStartingPoint, $pathEndPoint, Workspace $workspace, array $dimensions = null, $includeRemovedNodes = false, $nodeTypeFilter = null)
    {
        $pathStartingPoint = strtolower($pathStartingPoint);
        $pathEndPoint = strtolower($pathEndPoint);
        if (NodePaths::isSubPathOf($pathStartingPoint, $pathEndPoint) === false) {
            throw new \InvalidArgumentException('Invalid paths: path of starting point must be first part of end point path.', 1284391181);
        }

        $workspaces = $this->collectWorkspaceAndAllBaseWorkspaces($workspace);

        $queryBuilder = $this->createQueryBuilder($workspaces);

        if ($dimensions !== null) {
            $this->addDimensionJoinConstraintsToQueryBuilder($queryBuilder, $dimensions);
        } else {
            $dimensions = [];
        }

        if ($nodeTypeFilter !== null) {
            $this->addNodeTypeFilterConstraintsToQueryBuilder($queryBuilder, $nodeTypeFilter);
        }

        $pathConstraints = [];
        $constraintPath = $pathStartingPoint;
        $pathConstraints[] = md5($constraintPath);
        $pathSegments = explode('/', NodePaths::getRelativePathBetween($pathStartingPoint, $pathEndPoint));
        foreach ($pathSegments as $pathSegment) {
            $constraintPath = NodePaths::addNodePathSegment($constraintPath, $pathSegment);
            $pathConstraints[] = md5($constraintPath);
        }
        if (count($pathConstraints) > 0) {
            $queryBuilder->andWhere('n.pathHash IN (:paths)')
                ->setParameter('paths', $pathConstraints);
        }

        $query = $queryBuilder->getQuery();
        $foundNodes = $query->getResult();
        $foundNodes = $this->reduceNodeVariantsByWorkspacesAndDimensions($foundNodes, $workspaces, $dimensions);
        $foundNodes = $this->filterNodeDataByBestMatchInContext($foundNodes, $workspaces[0], $dimensions, $includeRemovedNodes);

        if ($includeRemovedNodes === false) {
            $foundNodes = $this->withoutRemovedNodes($foundNodes);
        }

        $nodesByDepth = [];
        /** @var NodeData $node */
        foreach ($foundNodes as $node) {
            $nodesByDepth[$node->getDepth()] = $node;
        }
        ksort($nodesByDepth);

        return array_values($nodesByDepth);
    }

    /**
     * Find nodes by a value in properties
     *
     * This method is internal and will be replaced with better search capabilities.
     *
     * @param string|array $term Search term
     * @param string $nodeTypeFilter Node type filter
     * @param Workspace $workspace
     * @param array $dimensions
     * @param string $pathStartingPoint
     * @return array<\Neos\ContentRepository\Domain\Model\NodeData>
     */
    public function findByProperties($term, $nodeTypeFilter, $workspace, $dimensions, $pathStartingPoint = null)
    {
        if (empty($term)) {
            throw new \InvalidArgumentException('"term" cannot be empty: provide a term to search for.', 1421329285);
        }
        $workspaces = $this->collectWorkspaceAndAllBaseWorkspaces($workspace);

        $queryBuilder = $this->createQueryBuilder($workspaces);
        $this->addDimensionJoinConstraintsToQueryBuilder($queryBuilder, $dimensions);
        $this->addNodeTypeFilterConstraintsToQueryBuilder($queryBuilder, $nodeTypeFilter);

        if (is_array($term)) {
            if (count($term) !== 1) {
                throw new \InvalidArgumentException('Currently only a 1-dimensional key => value array term is supported.', 1460437584);
            }

            // Build the like parameter as "key": "value" to search by a specific key and value
            $likeParameter = '%' . UnicodeFunctions::strtolower(trim(json_encode($term, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE), "{}\n\t ")) . '%';
        } else {
            // Convert to lowercase, then to json, and then trim quotes from json to have valid JSON escaping.
            $likeParameter = '%' . trim(json_encode(UnicodeFunctions::strtolower($term), JSON_UNESCAPED_UNICODE), '"') . '%';
        }

        $queryBuilder->andWhere("LOWER(NEOSCR_TOSTRING(n.properties)) LIKE :term")->setParameter('term', $likeParameter);

        if (strlen($pathStartingPoint) > 0) {
            $pathStartingPoint = strtolower($pathStartingPoint);
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orx()
                    ->add($queryBuilder->expr()->eq('n.parentPathHash', ':parentPathHash'))
                    ->add($queryBuilder->expr()->eq('n.pathHash', ':pathHash'))
                    ->add($queryBuilder->expr()->like('n.parentPath', ':parentPath'))
            )
                ->setParameter('parentPathHash', md5($pathStartingPoint))
                ->setParameter('pathHash', md5($pathStartingPoint))
                ->setParameter('parentPath', rtrim($pathStartingPoint, '/') . '/%');
        }

        $query = $queryBuilder->getQuery();
        $foundNodes = $query->getResult();
        $foundNodes = $this->reduceNodeVariantsByWorkspacesAndDimensions($foundNodes, $workspaces, $dimensions);
        $foundNodes = $this->withoutRemovedNodes($foundNodes);

        return $foundNodes;
    }

    /**
     * Flushes the addedNodes and removedNodes registry.
     *
     * This method is (and should only be) used as a slot to the allObjectsPersisted
     * signal.
     *
     * @return void
     */
    public function flushNodeRegistry()
    {
        $this->highestIndexCache = [];
        $this->addedNodes = new \SplObjectStorage();
        $this->removedNodes = new \SplObjectStorage();
    }

    /**
     * Add node type filter constraints to the query builder
     *
     * @param QueryBuilder $queryBuilder
     * @param string $nodeTypeFilter
     * @return void
     */
    public function addNodeTypeFilterConstraintsToQueryBuilder(QueryBuilder $queryBuilder, $nodeTypeFilter)
    {
        $constraints = $this->getNodeTypeFilterConstraintsForDql($nodeTypeFilter);
        if (count($constraints['includeNodeTypes']) > 0) {
            $queryBuilder->andWhere('n.nodeType IN (:includeNodeTypes)')
                ->setParameter('includeNodeTypes', $constraints['includeNodeTypes']);
        }
        if (count($constraints['excludeNodeTypes']) > 0) {
            $queryBuilder->andWhere('n.nodeType NOT IN (:excludeNodeTypes)')
                ->setParameter('excludeNodeTypes', $constraints['excludeNodeTypes']);
        }
    }

    /**
     * Generates a two dimensional array with the filters. First level is:
     * 'excludeNodeTypes'
     * 'includeNodeTypes'
     *
     * Both are numeric arrays with the respective node types that are included or excluded.
     *
     * @param string|null $nodeTypeFilter
     * @return array
     */
    protected function getNodeTypeFilterConstraintsForDql($nodeTypeFilter = '')
    {
        $nodeTypeFilter = empty($nodeTypeFilter) ? '' : $nodeTypeFilter;
        $constraints = [
            'excludeNodeTypes' => [],
            'includeNodeTypes' => []
        ];

        $nodeTypeFilterParts = Arrays::trimExplode(',', $nodeTypeFilter);
        foreach ($nodeTypeFilterParts as $nodeTypeFilterPart) {
            $nodeTypeFilterPart = trim($nodeTypeFilterPart);
            if (strpos($nodeTypeFilterPart, '!') === 0) {
                $negate = true;
                $nodeTypeFilterPart = substr($nodeTypeFilterPart, 1);
            } else {
                $negate = false;
            }
            $nodeTypeFilterPartSubTypes = array_merge([$this->nodeTypeManager->getNodeType($nodeTypeFilterPart)], $this->nodeTypeManager->getSubNodeTypes($nodeTypeFilterPart));

            foreach ($nodeTypeFilterPartSubTypes as $nodeTypeFilterPartSubType) {
                if ($negate === true) {
                    $constraints['excludeNodeTypes'][] = $nodeTypeFilterPartSubType->getName();
                } else {
                    $constraints['includeNodeTypes'][] = $nodeTypeFilterPartSubType->getName();
                }
            }
        }

        return $constraints;
    }

    /**
     * @param QueryInterface $query
     * @param $nodeTypeFilter
     * @return array
     */
    protected function getNodeTypeFilterConstraints(QueryInterface $query, $nodeTypeFilter)
    {
        $includeNodeTypeConstraints = [];
        $excludeNodeTypeConstraints = [];
        $nodeTypeFilterParts = Arrays::trimExplode(',', $nodeTypeFilter);
        foreach ($nodeTypeFilterParts as $nodeTypeFilterPart) {
            $nodeTypeFilterPart = trim($nodeTypeFilterPart);
            if (strpos($nodeTypeFilterPart, '!') === 0) {
                $negate = true;
                $nodeTypeFilterPart = substr($nodeTypeFilterPart, 1);
            } else {
                $negate = false;
            }
            $nodeTypeFilterPartSubTypes = array_merge([$this->nodeTypeManager->getNodeType($nodeTypeFilterPart)], $this->nodeTypeManager->getSubNodeTypes($nodeTypeFilterPart, false));

            foreach ($nodeTypeFilterPartSubTypes as $nodeTypeFilterPartSubType) {
                if ($negate === true) {
                    $excludeNodeTypeConstraints[] = $query->logicalNot($query->equals('nodeType', $nodeTypeFilterPartSubType->getName()));
                } else {
                    $includeNodeTypeConstraints[] = $query->equals('nodeType', $nodeTypeFilterPartSubType->getName());
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
    protected function filterOutRemovedObjects(array &$objects)
    {
        foreach ($objects as $index => $object) {
            if ($this->removedNodes->contains($object)) {
                unset($objects[$index]);
            }
        }
    }

    /**
     * Returns a subset of $nodes which are not flagged as removed.
     *
     * @param array $nodes NodeData including removed entries
     * @return array Only those NodeData instances which are not flagged as removed
     */
    protected function withoutRemovedNodes(array $nodes)
    {
        return array_filter($nodes, function (NodeData $node) {
            return !$node->isRemoved();
        });
    }

    /**
     * Returns a subset of $nodes which are flagged as removed.
     *
     * @param array $nodes NodeData including removed entries
     * @return array Only those NodeData instances which are flagged as removed
     */
    protected function onlyRemovedNodes(array $nodes)
    {
        return array_filter($nodes, function (NodeData $node) {
            return $node->isRemoved();
        });
    }

    /**
     * Persists all entities managed by the repository and all cascading dependencies
     *
     * @return void
     */
    public function persistEntities()
    {
        // Flush all entities to circumvent an issue in Doctrine 2.x which reevaluates all changes
        // for each change again when called individually leading to n^2 changeset calculations.
        $this->entityManager->flush();
        $this->emitRepositoryObjectsPersisted();
    }

    /**
     * Signals that persistEntities() in this repository finished correctly.
     *
     * @Flow\Signal
     * @return void
     */
    protected function emitRepositoryObjectsPersisted()
    {
    }

    /**
     * If $dimensions is not empty, adds join constraints to the given $queryBuilder
     * limiting the query result to matching hits.
     *
     * @param QueryBuilder $queryBuilder
     * @param array $dimensions
     * @return void
     */
    protected function addDimensionJoinConstraintsToQueryBuilder(QueryBuilder $queryBuilder, array $dimensions)
    {
        $count = 0;
        foreach ($dimensions as $dimensionName => $dimensionValues) {
            $dimensionAlias = 'd' . $count;
            $queryBuilder->andWhere(
                'EXISTS (SELECT ' . $dimensionAlias . ' FROM Neos\ContentRepository\Domain\Model\NodeDimension ' . $dimensionAlias . ' WHERE ' . $dimensionAlias . '.nodeData = n AND ' . $dimensionAlias . '.name = \'' . $dimensionName . '\' AND ' . $dimensionAlias . '.value IN (:' . $dimensionAlias . ')) ' .
                'OR NOT EXISTS (SELECT ' . $dimensionAlias . '_c FROM Neos\ContentRepository\Domain\Model\NodeDimension ' . $dimensionAlias . '_c WHERE ' . $dimensionAlias . '_c.nodeData = n AND ' . $dimensionAlias . '_c.name = \'' . $dimensionName . '\')'
            );
            $queryBuilder->setParameter($dimensionAlias, $dimensionValues);
            $count++;
        }
    }

    /**
     * Given an array with duplicate nodes (from different workspaces and dimensions) those are reduced to uniqueness (by node identifier)
     *
     * @param array $nodes NodeData result with multiple and duplicate identifiers (different nodes and redundant results for node variants with different dimensions)
     * @param array $workspaces
     * @param array $dimensions
     * @return array Array of unique node results indexed by identifier
     * @throws Exception\NodeException
     */
    protected function reduceNodeVariantsByWorkspacesAndDimensions(array $nodes, array $workspaces, array $dimensions)
    {
        $reducedNodes = [];

        $minimalDimensionPositionsByIdentifier = [];

        $workspaceNames = array_map(
            function (Workspace $workspace) {
                return $workspace->getName();
            },
            $workspaces
        );

        foreach ($nodes as $node) {
            /** @var NodeData $node */
            $nodeDimensions = $node->getDimensionValues();

            // Find the position of the workspace, a smaller value means more priority
            $workspacePosition = array_search($node->getWorkspace()->getName(), $workspaceNames);
            if ($workspacePosition === false) {
                throw new Exception\NodeException(sprintf('Node workspace "%s" not found in allowed workspaces (%s), this could result from a detached workspace entity in the context.', $node->getWorkspace()->getName(), implode($workspaceNames, ', ')), 1413902143);
            }

            // Find positions in dimensions, add workspace in front for highest priority
            $dimensionPositions = [];

            // Special case for no dimensions
            if ($dimensions === []) {
                // We can just decide if the given node has no dimensions.
                $dimensionPositions[] = ($nodeDimensions === []) ? 0 : 1;
            }

            foreach ($dimensions as $dimensionName => $dimensionValues) {
                if (isset($nodeDimensions[$dimensionName])) {
                    foreach ($nodeDimensions[$dimensionName] as $nodeDimensionValue) {
                        $position = array_search($nodeDimensionValue, $dimensionValues);
                        if ($position === false) {
                            $position = PHP_INT_MAX;
                        }
                        $dimensionPositions[$dimensionName] = isset($dimensionPositions[$dimensionName]) ? min(
                            $dimensionPositions[$dimensionName],
                            $position
                        ) : $position;
                    }
                } else {
                    $dimensionPositions[$dimensionName] = isset($dimensionPositions[$dimensionName]) ? min(
                        $dimensionPositions[$dimensionName],
                        PHP_INT_MAX
                    ) : PHP_INT_MAX;
                }
            }
            $dimensionPositions[] = $workspacePosition;

            $identifier = $node->getIdentifier();
            // Yes, it seems to work comparing arrays that way!
            if (!isset($minimalDimensionPositionsByIdentifier[$identifier]) || $dimensionPositions < $minimalDimensionPositionsByIdentifier[$identifier]) {
                $reducedNodes[$identifier] = $node;
                $minimalDimensionPositionsByIdentifier[$identifier] = $dimensionPositions;
            }
        }

        return $reducedNodes;
    }

    /**
     * Given an array with duplicate nodes (from different workspaces) those are reduced to uniqueness (by node identifier and dimensions hash)
     *
     * @param array $nodes NodeData
     * @param array $workspaces
     * @return array Array of unique node results indexed by identifier and dimensions hash
     */
    protected function reduceNodeVariantsByWorkspaces(array $nodes, array $workspaces)
    {
        $foundNodes = [];

        $minimalPositionByIdentifier = [];
        /** @var $node NodeData */
        foreach ($nodes as $node) {

            // Find the position of the workspace, a smaller value means more priority
            $workspaceNames = array_map(
                function (Workspace $workspace) {
                    return $workspace->getName();
                },
                $workspaces
            );
            $workspacePosition = array_search($node->getWorkspace()->getName(), $workspaceNames);

            $uniqueNodeDataIdentity = $node->getIdentifier() . '|' . $node->getDimensionsHash();
            if (!isset($minimalPositionByIdentifier[$uniqueNodeDataIdentity]) || $workspacePosition < $minimalPositionByIdentifier[$uniqueNodeDataIdentity]) {
                $foundNodes[$uniqueNodeDataIdentity] = $node;
                $minimalPositionByIdentifier[$uniqueNodeDataIdentity] = $workspacePosition;
            }
        }

        return $foundNodes;
    }

    /**
     * Find all NodeData objects inside a given workspace sorted by path to be used
     * in publishing. The order makes sure that parent nodes are published first.
     *
     * Shadow nodes are excluded, because they will be published when publishing the moved node.
     *
     * @param Workspace $workspace
     * @return array<NodeData>
     */
    public function findByWorkspace(Workspace $workspace)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $queryBuilder->select('n')
            ->from(NodeData::class, 'n')
            ->where('n.workspace = :workspace')
            ->andWhere('n.movedTo IS NULL OR n.removed = :removed')
            ->orderBy('n.path', 'ASC')
            ->setParameter('workspace', $workspace->getName())
            ->setParameter('removed', false, \PDO::PARAM_BOOL);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Find out if the given path exists anywhere in the CR. (internal)
     * If you need this functionality use \Neos\ContentRepository\Domain\Service\NodeService::nodePathExistsInAnyContext()
     *
     * @param string $nodePath
     * @return boolean
     */
    public function pathExists($nodePath)
    {
        $nodePath = strtolower($nodePath);
        $result = null;

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $this->securityContext->withoutAuthorizationChecks(function () use ($nodePath, $queryBuilder, &$result) {
            $queryBuilder->select('n.identifier')
                ->from(NodeData::class, 'n')
                ->where('n.pathHash = :pathHash')
                ->setParameter('pathHash', md5($nodePath));
            $result = count($queryBuilder->getQuery()->getResult()) > 0;
        });

        return $result;
    }

    /**
     * Find all node data in a path matching the given workspace hierarchy
     *
     * Internal method, used by Node::setPath
     *
     * @param string $path
     * @param Workspace $workspace
     * @param boolean $includeRemovedNodes Should removed nodes be included in the result (defaults to false)
     * @param boolean $recursive
     * @return array<NodeData> Node data reduced by workspace but with all existing content dimension variants, includes removed nodes
     */
    public function findByPathWithoutReduce($path, Workspace $workspace, $includeRemovedNodes = false, $recursive = false)
    {
        $path = strtolower($path);
        $workspaces = $this->collectWorkspaceAndAllBaseWorkspaces($workspace);

        $queryBuilder = $this->createQueryBuilder($workspaces);
        $this->addPathConstraintToQueryBuilder($queryBuilder, $path, $recursive);

        $query = $queryBuilder->getQuery();
        $foundNodes = $query->getResult();
        // Consider materialized, but not yet persisted nodes
        foreach ($this->addedNodes as $addedNode) {
            if (($addedNode->getPath() === $path || ($recursive && NodePaths::isSubPathOf($path, $addedNode->getPath()))) && in_array($addedNode->getWorkspace(), $workspaces)) {
                $foundNodes[] = $addedNode;
            }
        }

        $foundNodes = $this->reduceNodeVariantsByWorkspaces($foundNodes, $workspaces);
        if ($includeRemovedNodes === false) {
            $foundNodes = $this->withoutRemovedNodes($foundNodes);
        }

        return $foundNodes;
    }

    /**
     * Searches for possible relations to the given entity identifiers in NodeData.
     * Will return all possible NodeData objects that contain this identifiers.
     * See buildQueryBuilderForRelationMap for the relationMap definition.
     *
     * Note: This is an internal method that is likely to be replaced in the future.
     *
     * @param array $relationMap
     * @return array
     */
    public function findNodesByRelatedEntities($relationMap)
    {
        return $this->buildQueryBuilderForRelationMap($relationMap)->getQuery()->getResult();
    }

    /**
     * Searches for possible relations to the given entity identifiers in NodeData using a path prefix.
     * Will return all possible NodeData objects that contain this identifiers.
     * See buildQueryBuilderForRelationMap for the relationMap definition.
     *
     * Note: This is an internal method that is likely to be replaced in the future.
     *
     * @param string $pathPrefix
     * @param array $relationMap
     * @return array
     */
    public function findNodesByPathPrefixAndRelatedEntities($pathPrefix, $relationMap)
    {
        $queryBuilder = $this->buildQueryBuilderForRelationMap($relationMap);

        if (trim($pathPrefix) !== '') {
            $queryBuilder->andWhere('n.path LIKE :pathPrefix');
            $queryBuilder->setParameter('pathPrefix', trim($pathPrefix) . '%');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Remove all nodes below a given path. Does not care about workspaces and dimensions.
     *
     * @param string $path Starting point path underneath all nodes are to be removed.
     * @return void
     */
    public function removeAllInPath($path)
    {
        $path = strtolower($path);
        $query = $this->entityManager->createQuery('DELETE FROM Neos\ContentRepository\Domain\Model\NodeData n WHERE n.path LIKE :path');
        $query->setParameter('path', $path . '/%');
        $query->execute();
    }

    /**
     * Test if a given NodeData is in the set of removed node data objects
     *
     * @param NodeData $nodeData
     * @return boolean true If the NodeData was marked for removal
     */
    public function isInRemovedNodes(NodeData $nodeData)
    {
        return $this->removedNodes->contains($nodeData);
    }

    /**
     *
     * @param array $workspaces
     * @return QueryBuilder
     */
    protected function createQueryBuilder(array $workspaces)
    {
        $workspacesNames = array_map(static function (Workspace $workspace) {
            return $workspace->getName();
        }, $workspaces);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $queryBuilder->select('n')
            ->from(NodeData::class, 'n')
            ->where('n.workspace IN (:workspaces)')
            ->setParameter('workspaces', $workspacesNames);

        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $parentPath
     * @param boolean $recursive
     * @return void
     */
    protected function addParentPathConstraintToQueryBuilder(QueryBuilder $queryBuilder, $parentPath, $recursive = false)
    {
        if (!$recursive) {
            $queryBuilder->andWhere('n.parentPathHash = :parentPathHash')
                ->setParameter('parentPathHash', md5($parentPath));
        } else {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX()
                    ->add($queryBuilder->expr()->eq('n.parentPathHash', ':parentPathHash'))
                    ->add($queryBuilder->expr()->like('n.parentPath', ':parentPath'))
            )
                ->setParameter('parentPathHash', md5($parentPath))
                ->setParameter('parentPath', rtrim($parentPath, '/') . '/%');
        }
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $path
     * @param boolean $recursive
     * @return void
     */
    protected function addPathConstraintToQueryBuilder(QueryBuilder $queryBuilder, $path, $recursive = false)
    {
        if (!$recursive) {
            $queryBuilder->andWhere('n.pathHash = :pathHash')
                ->setParameter('pathHash', md5($path));
        } else {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX()
                    ->add($queryBuilder->expr()->eq('n.pathHash', ':pathHash'))
                    ->add($queryBuilder->expr()->like('n.path', ':path'))
            )
                ->setParameter('pathHash', md5($path))
                ->setParameter('path', rtrim($path, '/') . '/%');
        }
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $identifier
     * @return void
     */
    protected function addIdentifierConstraintToQueryBuilder(QueryBuilder $queryBuilder, $identifier)
    {
        // TODO: We should add type hints in next major because this query becomes really SLOW if you use an integer here.
        $identifier = (string)$identifier;
        $queryBuilder->andWhere('n.identifier = :identifier')
            ->setParameter('identifier', $identifier);
    }

    /**
     * @param array $nodeDataObjects
     * @param Workspace $workspace
     * @param array $dimensions
     * @param boolean $includeRemovedNodes Should removed nodes be included in the result (defaults to false)
     * @return array
     */
    protected function filterNodeDataByBestMatchInContext(array $nodeDataObjects, Workspace $workspace, array $dimensions, $includeRemovedNodes = false)
    {
        $workspaces = $this->collectWorkspaceAndAllBaseWorkspaces($workspace);
        $nonPersistedNodes = [];
        $nodeIdentifier = [];

        /** @var NodeData $nodeData */
        foreach ($nodeDataObjects as $nodeData) {
            $nodeIdentifier[] = $nodeData->getIdentifier();
            while ($workspace !== null) {
                /** @var $node NodeData */
                foreach ($this->addedNodes as $node) {
                    if ($node->getIdentifier() === $nodeData->getIdentifier() && $node->matchesWorkspaceAndDimensions($workspace, $dimensions) && $node->isInternal() === false) {
                        $nonPersistedNodes[] = $node;
                    }
                }

                $workspace = $workspace->getBaseWorkspace();
            }
        }

        $queryBuilder = $this->createQueryBuilder($workspaces);
        if ($dimensions !== null) {
            $this->addDimensionJoinConstraintsToQueryBuilder($queryBuilder, $dimensions);
        } else {
            $dimensions = [];
        }
        if ($includeRemovedNodes === false) {
            $queryBuilder->andWhere('n.movedTo IS NULL OR n.removed = FALSE');
        } else {
            $queryBuilder->andWhere('n.movedTo IS NULL');
        }
        $queryBuilder->andWhere('n.identifier IN (:identifier)')
            ->setParameter('identifier', $nodeIdentifier);
        $query = $queryBuilder->getQuery();
        $nodes = $query->getResult();
        $foundNodes = array_merge($nodes, $nonPersistedNodes);
        $foundNodes = $this->reduceNodeVariantsByWorkspacesAndDimensions($foundNodes, $workspaces, $dimensions);

        if ($includeRemovedNodes === true) {
            $foundNodes = $this->onlyRemovedNodes($foundNodes);
        } elseif ($includeRemovedNodes === false) {
            $foundNodes = $this->withoutRemovedNodes($foundNodes);
        }

        /** @var NodeData $nodeData */
        return array_filter($nodeDataObjects, function (NodeData $nodeData) use ($foundNodes) {
            return (isset($foundNodes[$nodeData->getIdentifier()]) && $foundNodes[$nodeData->getIdentifier()] === $nodeData);
        });
    }

    /**
     * Returns an array that contains the given workspace and all base (parent) workspaces of it.
     *
     * @param Workspace $workspace
     * @return array
     */
    protected function collectWorkspaceAndAllBaseWorkspaces(Workspace $workspace)
    {
        $workspaces = [];
        while ($workspace !== null) {
            $workspaces[] = $workspace;
            $workspace = $workspace->getBaseWorkspace();
        }

        return $workspaces;
    }

    /**
     * Returns a query builder for a query on node data using the given
     * relation map.
     *
     * $objectTypeMap = [
     *    'Neos\Media\Domain\Model\Asset' => ['some-uuid-here'],
     *    'Neos\Media\Domain\Model\ImageVariant' => ['some-uuid-here', 'another-uuid-here']
     * ]
     *
     * @param array $relationMap
     * @return QueryBuilder
     */
    protected function buildQueryBuilderForRelationMap($relationMap)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $queryBuilder->select('n')
            ->from(NodeData::class, 'n');

        $constraints = [];
        $parameters = [];

        foreach ($relationMap as $relatedObjectType => $relatedIdentifiers) {
            foreach ($relatedIdentifiers as $relatedIdentifier) {
                // entity references like "__identifier": "so-me-uu-id"
                $constraints[] = '(LOWER(NEOSCR_TOSTRING(n.properties)) LIKE :entity' . md5($relatedIdentifier) . ' )';
                $parameters['entity' . md5($relatedIdentifier)] = '%"__identifier": "' . strtolower($relatedIdentifier) . '"%';

                // asset references in text like "asset://so-me-uu-id"
                $constraints[] = '(LOWER(NEOSCR_TOSTRING(n.properties)) LIKE :asset' . md5($relatedIdentifier) . ' )';
                switch ($this->entityManager->getConnection()->getDatabasePlatform()->getName()) {
                    case 'postgresql':
                        $parameters['asset' . md5($relatedIdentifier)] = '%asset://' . strtolower($relatedIdentifier) . '%';
                    break;
                    case 'sqlite':
                        $parameters['asset' . md5($relatedIdentifier)] = '%asset:\/\/' . strtolower($relatedIdentifier) . '%';
                    break;
                    default:
                        $parameters['asset' . md5($relatedIdentifier)] = '%asset:\\\\/\\\\/' . strtolower($relatedIdentifier) . '%';
                }
            }
        }

        $queryBuilder->where(implode(' OR ', $constraints));
        $queryBuilder->setParameters($parameters);
        return $queryBuilder;
    }
}
