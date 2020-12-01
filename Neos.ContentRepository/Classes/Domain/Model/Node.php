<?php
namespace Neos\ContentRepository\Domain\Model;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodes;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraints;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Domain\Projection\Content\PropertyCollectionInterface;
use Neos\ContentRepository\Exception\NodeConfigurationException;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Exception\NodeMethodIsUnsupported;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;
use Neos\Flow\Annotations as Flow;
use Neos\Cache\CacheAwareInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Utility\ObjectAccess;
use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\NodeServiceInterface;
use Neos\ContentRepository\Domain\Utility\NodePaths;
use Neos\ContentRepository\Exception\NodeConstraintException;
use Neos\ContentRepository\Exception\NodeException;
use Neos\ContentRepository\Exception\NodeExistsException;
use Neos\ContentRepository\Utility;

/**
 * This is the main API for storing and retrieving content in the system.
 *
 * @Flow\Scope("prototype")
 * @api
 */
class Node implements NodeInterface, CacheAwareInterface, TraversableNodeInterface
{
    /**
     * The NodeData entity this version is for.
     *
     * @var NodeData
     */
    protected $nodeData;

    /**
     * @var Context
     */
    protected $context;

    /**
     * Defines if the NodeData represented by this Node is already
     * in the same context or if it is currently just "shining through".
     *
     * @var boolean
     */
    protected $nodeDataIsMatchingContext = null;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var NodeServiceInterface
     */
    protected $nodeService;

    /**
     * @param NodeData $nodeData
     * @param Context $context
     * @Flow\Autowiring(false)
     */
    public function __construct(NodeData $nodeData, Context $context)
    {
        $this->nodeData = $nodeData;
        $this->context = $context;
    }

    /**
     * Returns the absolute path of this node with additional context information (such as the workspace name).
     *
     * Example: /sites/mysitecom/homepage/about@user-admin
     *
     * NOTE: This method will probably be removed at some point. Code should never rely on the exact format of the context path
     *       since that might change in the future.
     *
     * @return string Node path with context information
     */
    public function getContextPath()
    {
        return NodePaths::generateContextPath($this->getPath(), $this->context->getWorkspaceName(), $this->context->getDimensions());
    }

    /**
     * Set the name of the node to $newName, keeping its position as it is.
     *
     * @param string $newName
     * @return void
     * @throws NodeException if you try to set the name of the root node.
     * @throws \InvalidArgumentException if $newName is invalid
     * @throws NodeTypeNotFoundException
     * @api
     */
    public function setName($newName): void
    {
        if ($this->getName() === $newName) {
            return;
        }

        if (!is_string($newName) || preg_match(NodeInterface::MATCH_PATTERN_NAME, $newName) !== 1) {
            throw new \InvalidArgumentException('Invalid node name "' . $newName . '" (a node name must only contain lowercase characters, numbers and the "-" sign).', 1364290748);
        }

        if ($this->isRoot()) {
            throw new NodeException('The root node cannot be renamed.', 1346778388);
        }

        $this->setPath(NodePaths::addNodePathSegment($this->getParentPath(), $newName));
        $this->nodeDataRepository->persistEntities();
        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitNodeUpdated($this);
    }

    /**
     * Sets the absolute path of this node.
     *
     * This method is only for internal use by the content repository or node methods. Changing
     * the path of a node manually may lead to unexpected behavior.
     *
     * To achieve a correct behavior when changing the path (moving the node) in a workspace, a shadow node data that will
     * hide the node data in the base workspace will be created. Thus queries do not need to worry about moved nodes.
     * Through a movedTo reference the shadow node data will be removed when publishing the moved node.
     *
     * @param string $path
     * @param boolean $checkForExistence Checks for existence at target path, internally used for recursions and shadow nodes.
     * @return void
     * @throws NodeException
     * @throws NodeTypeNotFoundException
     */
    protected function setPath(string $path, bool $checkForExistence = true): void
    {
        $originalPath = $this->nodeData->getPath();
        if ($originalPath === $path) {
            return;
        }

        $pathAvailable = $checkForExistence ? $this->isNodePathAvailable($path) : true;
        if (!$pathAvailable) {
            throw new NodeException(sprintf('Can not rename the node "%s" as a node already exists on path "%s"', $this->getPath(), $path), 1414436551);
        }

        $changedNodePathsCollection = $this->setPathInternal($path, !$checkForExistence);
        $this->nodeDataRepository->persistEntities();
        array_walk($changedNodePathsCollection, function ($changedNodePathInformation) {
            call_user_func_array([
                $this,
                'emitNodePathChanged'
            ], $changedNodePathInformation);
        });
    }

    /**
     * Checks if the given node path is available for this node, so either no node with this path exists or an existing node has the same identifier.
     *
     * @param string $path
     * @return boolean
     */
    protected function isNodePathAvailable(string $path): bool
    {
        $existingNodeDataArray = $this->nodeDataRepository->findByPathWithoutReduce($path, $this->context->getWorkspace());

        $nonMatchingNodeData = array_filter($existingNodeDataArray, function (NodeData $nodeData) {
            return ($nodeData->getIdentifier() !== $this->getIdentifier());
        });

        return ($nonMatchingNodeData === []);
    }

    /**
     * Moves a node and sub nodes to the new path.
     * This process is different depending on the fact if the node is an aggregate type or not.
     *
     * @param string $destinationPath the new node path
     * @param boolean $recursiveCall is this a recursive call
     * @return array NodeVariants and old and new paths
     * @throws NodeException
     * @throws NodeTypeNotFoundException
     */
    protected function setPathInternal(string $destinationPath, bool $recursiveCall): array
    {
        if ($this->getNodeType()->isAggregate()) {
            return $this->setPathInternalForAggregate($destinationPath, $recursiveCall);
        }

        $originalPath = $this->nodeData->getPath();

        /** @var Node $childNode */
        foreach ($this->getChildNodes() as $childNode) {
            $childNode->setPath(NodePaths::addNodePathSegment($destinationPath, $childNode->getName()), false);
        }

        $this->moveNodeToDestinationPath($this, $destinationPath);

        return [
            [$this, $originalPath, $this->getNodeData()->getPath(), $recursiveCall]
        ];
    }

    /**
     * Moves a node and sub nodes to the new path given with special logic for aggregate node types.
     *
     * @param string $destinationPath the new node path
     * @param boolean $recursiveCall is this a recursive call
     * @return array of arrays with NodeVariant and old and new path and if this was a recursive call
     */
    protected function setPathInternalForAggregate(string $destinationPath, bool $recursiveCall): array
    {
        $originalPath = $this->nodeData->getPath();
        $nodeDataVariantsAndChildren = $this->nodeDataRepository->findByPathWithoutReduce($originalPath, $this->context->getWorkspace(), true, true);

        $changedNodePathsCollection = array_map(function ($nodeData) use ($destinationPath, $originalPath, $recursiveCall) {
            return $this->moveNodeData($nodeData, $originalPath, $destinationPath, $recursiveCall);
        }, $nodeDataVariantsAndChildren);

        return array_filter($changedNodePathsCollection);
    }

    /**
     * Moves a NodeData object that is either a variant or child node to the given destination path.
     *
     * @param NodeData $nodeData
     * @param string $originalPath
     * @param string $destinationPath
     * @param boolean $recursiveCall
     * @return array|null
     * @throws NodeConfigurationException
     */
    protected function moveNodeData(NodeData $nodeData, string $originalPath, string $destinationPath, bool $recursiveCall)
    {
        $recursiveCall = $recursiveCall || ($this->nodeData !== $nodeData);
        $nodeVariant = null;
        // $nodeData at this point could contain *our own NodeData reference* ($this->nodeData), as we find all NodeData objects
        // (across all dimensions) with the same path.
        //
        // We need to ensure that our own Node object's nodeData reference ($this->nodeData) is also updated correctly if a new NodeData object
        // is returned; as we rely on the fact that $this->getPath() will return the new node path in all circumstances.
        //
        // However, $this->createNodeForVariant() only returns $this if the Context object is the same as $this->context; which is not
        // the case if $this->context contains dimension fallbacks such as "Language: EN, DE".
        //
        // The "if" statement below is actually a workaround to ensure that if the NodeData object is our own one, we update *ourselves* correctly,
        // and thus return the correct (new) Node Path when calling $this->getPath() afterwards.
        // FIXME: This is dangerous and probably the NodeFactory should take care of globally tracking usage of NodeData objects and replacing them in Node objects

        if ($this->nodeData === $nodeData) {
            $nodeVariant = $this;
        }

        if ($nodeVariant === null) {
            $nodeVariant = $this->createNodeForVariant($nodeData);
        }

        $moveVariantResult = $nodeVariant === null ? null : $this->moveVariantOrChild($originalPath, $destinationPath, $nodeVariant);
        if ($moveVariantResult !== null) {
            array_push($moveVariantResult, $recursiveCall);
        }

        return $moveVariantResult;
    }

    /**
     * Create a node for the given NodeData, given that it is a variant of the current node
     *
     * @param NodeData $nodeData
     * @return NodeInterface|null
     * @throws NodeConfigurationException
     */
    protected function createNodeForVariant(NodeData $nodeData): ?NodeInterface
    {
        $contextProperties = $this->context->getProperties();
        $contextProperties['dimensions'] = $nodeData->getDimensionValues();
        unset($contextProperties['targetDimensions']);
        $adjustedContext = $this->contextFactory->create($contextProperties);

        return $this->nodeFactory->createFromNodeData($nodeData, $adjustedContext);
    }

    /**
     * Moves the given variant or child node to the destination defined by the given path which is
     * the new path for the originally moved (parent|variant) node
     *
     * @param string $aggregateOriginalPath
     * @param string $aggregateDestinationPath
     * @param NodeInterface $nodeToMove
     * @return array NodeVariant and old and new path
     */
    protected function moveVariantOrChild(string $aggregateOriginalPath, string $aggregateDestinationPath, NodeInterface $nodeToMove = null): ?array
    {
        if ($nodeToMove === null) {
            return null;
        }

        $variantOriginalPath = $nodeToMove->getPath();
        $relativePathSegment = NodePaths::getRelativePathBetween($aggregateOriginalPath, $variantOriginalPath);
        $variantDestinationPath = NodePaths::addNodePathSegment($aggregateDestinationPath, $relativePathSegment);
        $this->moveNodeToDestinationPath($nodeToMove, $variantDestinationPath);

        return [$nodeToMove, $variantOriginalPath, $nodeToMove->getPath()];
    }

    /**
     * Moves the given node to the destination path by modifying the underlaying NodeData object.
     *
     * @param NodeInterface $node
     * @param string $destinationPath
     * @return void
     */
    protected function moveNodeToDestinationPath(NodeInterface $node, $destinationPath)
    {
        $nodeData = $node->getNodeData();
        $possibleShadowedNodeData = $nodeData->move($destinationPath, $this->context->getWorkspace());
        if ($node instanceof Node) {
            $node->setNodeData($possibleShadowedNodeData);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOtherNodeVariants(): array
    {
        return array_filter(
            $this->context->getNodeVariantsByIdentifier($this->getIdentifier()),
            function ($node) {
                return ($node instanceof NodeInterface && $node->getNodeData() !== $this->nodeData);
            }
        );
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreationDateTime(): \DateTimeInterface
    {
        return $this->nodeData->getCreationDateTime();
    }

    /**
     * @return \DateTimeInterface
     */
    public function getLastModificationDateTime(): \DateTimeInterface
    {
        return $this->nodeData->getLastModificationDateTime();
    }

    /**
     * @param \DateTimeInterface $lastModificationDateTime
     * @return void
     */
    public function setLastPublicationDateTime(\DateTimeInterface $lastModificationDateTime)
    {
        $this->nodeData->setLastPublicationDateTime($lastModificationDateTime);
    }

    /**
     * @return \DateTimeInterface|null Date of last publication or null if the node was not published yet
     */
    public function getLastPublicationDateTime(): ?\DateTimeInterface
    {
        return $this->nodeData->getLastPublicationDateTime();
    }

    /**
     * Returns the path of this node
     *
     * @return string
     * @deprecated with version 4.3, use TraversableNodeInterface::findNodePath() instead.
     */
    public function getPath()
    {
        return $this->nodeData->getPath();
    }

    /**
     * Returns the level at which this node is located.
     * Counting starts with 0 for "/", 1 for "/foo", 2 for "/foo/bar" etc.
     *
     * @return integer
     * @deprecated with version 4.3 - Use TraversableNodeInterface::findNodePath()->getDepth() instead
     */
    public function getDepth()
    {
        return $this->nodeData->getDepth();
    }

    /**
     * Returns the name of this node
     *
     * @return string
     * @deprecated with version 4.3, use TraversableNodeInterface::getNodeName() instead.
     */
    public function getName()
    {
        return $this->nodeData->getName();
    }

    /**
     * Returns the node label as generated by the configured node label generator
     *
     * @return string
     * @throws NodeTypeNotFoundException
     */
    public function getLabel(): string
    {
        return $this->getNodeType()->getNodeLabelGenerator()->getLabel($this);
    }

    /**
     * Sets the workspace of this node.
     *
     * This method is only for internal use by the content repository. Changing
     * the workspace of a node manually may lead to unexpected behavior.
     *
     * @param Workspace $workspace
     * @return void
     * @throws NodeException
     * @throws NodeTypeNotFoundException
     */
    public function setWorkspace(Workspace $workspace): void
    {
        if ($this->getWorkspace()->getName() === $workspace->getName()) {
            return;
        }
        if (!$this->isNodeDataMatchingContext()) {
            $this->materializeNodeData();
        }
        $this->nodeData->setWorkspace($workspace);
        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitNodeUpdated($this);
    }

    /**
     * Returns the workspace this node is contained in
     *
     * @return Workspace
     */
    public function getWorkspace()
    {
        return $this->nodeData->getWorkspace();
    }

    /**
     * Returns the identifier of this node
     *
     * @return string the node's UUID (unique within the workspace)
     * @deprecated with version 4.3, use getNodeAggregateIdentifier() instead.
     */
    public function getIdentifier()
    {
        return $this->nodeData->getIdentifier();
    }

    /**
     * Sets the index of this node
     *
     * NOTE: This method is meant for internal use and must only be used by other nodes.
     *
     * @param integer $index The new index
     * @return void
     * @throws NodeException
     * @throws NodeTypeNotFoundException
     */
    public function setIndex($index): void
    {
        if ($this->getIndex() === $index) {
            return;
        }
        if (!$this->isNodeDataMatchingContext()) {
            $this->materializeNodeData();
        }
        $this->nodeData->setIndex($index);
        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitNodeUpdated($this);
    }

    /**
     * Returns the index of this node which determines the order among siblings
     * with the same parent node.
     *
     * @return integer
     */
    public function getIndex()
    {
        return $this->nodeData->getIndex();
    }

    /**
     * Returns the parent node of this node
     *
     * @return NodeInterface|null The parent node or NULL if this is the root node
     * @deprecated with version 4.3, use TraversableNodeInterface::findParentNode() instead.
     *  Beware that findParentNode() is not fully equivalent to this method.
     *  It has a different root node handling:
     *    - findParentNode() throws an exception for the root node
     *    - getParent() returns <code>null</code> for the root node
     */
    public function getParent()
    {
        if ($this->isRoot()) {
            return null;
        }

        $parentPath = $this->getParentPath();
        $node = $this->context->getFirstLevelNodeCache()->getByPath($parentPath);
        if ($node !== false) {
            return $node;
        }
        $node = $this->nodeDataRepository->findOneByPathInContext($parentPath, $this->context);
        $this->context->getFirstLevelNodeCache()->setByPath($parentPath, $node);

        return $node;
    }

    /**
     * Returns the parent node path
     *
     * @return string Absolute node path of the parent node
     * @deprecated with version 4.3, use TraversableNodeInterface::findParentNode()->findNodePath() instead.
     */
    public function getParentPath(): string
    {
        return $this->nodeData->getParentPath();
    }


    /**
     * Whether or not the node is the root node (i.e. has no parent node)
     *
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->getPath() === '/';
    }

    /**
     * Moves this node before the given node
     *
     * @param NodeInterface $referenceNode
     * @param string $newName
     * @throws NodeConstraintException if a node constraint prevents moving the node
     * @throws NodeException if you try to move the root node
     * @throws NodeExistsException
     * @throws NodeTypeNotFoundException
     * @api
     */
    public function moveBefore(NodeInterface $referenceNode, string $newName = null): void
    {
        if ($referenceNode === $this) {
            return;
        }

        if ($this->isRoot()) {
            throw new NodeException('The root node cannot be moved.', 1285005924);
        }

        $name = $newName !== null ? $newName : $this->getName();
        $referenceParentNode = $referenceNode->getParent();

        if ($referenceParentNode !== $this->getParent() && $referenceParentNode->getNode($name) !== null) {
            throw new NodeExistsException(sprintf('Node with path "%s" already exists.', $name), 1292503468);
        }

        if (($referenceParentNode instanceof Node && !$referenceParentNode->willChildNodeBeAutoCreated($name)) && !$referenceParentNode->isNodeTypeAllowedAsChildNode($this->getNodeType())) {
            throw new NodeConstraintException(sprintf('Cannot move %s before %s', $this, $referenceNode), 1400782413);
        }

        $this->emitBeforeNodeMove($this, $referenceNode, NodeDataRepository::POSITION_BEFORE);
        if ($referenceNode->getParentPath() !== $this->getParentPath()) {
            $this->setPath(NodePaths::addNodePathSegment($referenceNode->getParentPath(), $name));
        } else {
            if (!$this->isNodeDataMatchingContext()) {
                $this->materializeNodeData();
            }
        }

        $this->nodeDataRepository->setNewIndex($this->nodeData, NodeDataRepository::POSITION_BEFORE, $referenceNode);
        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitAfterNodeMove($this, $referenceNode, NodeDataRepository::POSITION_BEFORE);
        $this->emitNodeUpdated($this);
    }

    /**
     * Moves this node after the given node
     *
     * @param NodeInterface $referenceNode
     * @param string $newName
     * @throws NodeConstraintException if a node constraint prevents moving the node
     * @throws NodeException
     * @throws NodeExistsException
     * @throws NodeTypeNotFoundException
     * @api
     */
    public function moveAfter(NodeInterface $referenceNode, string $newName = null): void
    {
        if ($referenceNode === $this) {
            return;
        }

        if ($this->isRoot()) {
            throw new NodeException('The root node cannot be moved.', 1316361483);
        }

        $name = $newName !== null ? $newName : $this->getName();
        $referenceParentNode = $referenceNode->getParent();

        if ($referenceParentNode !== $this->getParent() && $referenceParentNode->getNode($name) !== null) {
            throw new NodeExistsException(sprintf('Node with path "%s" already exists.', $name), 1292503469);
        }

        if (($referenceParentNode instanceof Node && !$referenceParentNode->willChildNodeBeAutoCreated($name)) && !$referenceParentNode->isNodeTypeAllowedAsChildNode($this->getNodeType())) {
            throw new NodeConstraintException(sprintf('Cannot move %s after %s', $this, $referenceNode), 1404648100);
        }

        $this->emitBeforeNodeMove($this, $referenceNode, NodeDataRepository::POSITION_AFTER);
        if ($referenceNode->getParentPath() !== $this->getParentPath()) {
            $this->setPath(NodePaths::addNodePathSegment($referenceNode->getParentPath(), $name));
        } else {
            if (!$this->isNodeDataMatchingContext()) {
                $this->materializeNodeData();
            }
        }

        $this->nodeDataRepository->setNewIndex($this->nodeData, NodeDataRepository::POSITION_AFTER, $referenceNode);
        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitAfterNodeMove($this, $referenceNode, NodeDataRepository::POSITION_AFTER);
        $this->emitNodeUpdated($this);
    }

    /**
     * Moves this node into the given node
     *
     * @param NodeInterface $referenceNode
     * @param string $newName
     * @throws NodeConstraintException
     * @throws NodeException
     * @throws NodeExistsException
     * @throws NodeTypeNotFoundException
     * @api
     */
    public function moveInto(NodeInterface $referenceNode, string $newName = null): void
    {
        if ($referenceNode === $this || $referenceNode === $this->getParent()) {
            return;
        }

        if ($this->isRoot()) {
            throw new NodeException('The root node cannot be moved.', 1346769001);
        }

        $name = $newName !== null ? $newName : $this->getName();

        if ($referenceNode !== $this->getParent() && $referenceNode->getNode($name) !== null) {
            throw new NodeExistsException(sprintf('Node with path "%s" already exists.', $name), 1292503470);
        }

        if (($referenceNode instanceof Node && !$referenceNode->willChildNodeBeAutoCreated($name)) && !$referenceNode->isNodeTypeAllowedAsChildNode($this->getNodeType())) {
            throw new NodeConstraintException(sprintf('Cannot move %s into %s', $this, $referenceNode), 1404648124);
        }

        $this->emitBeforeNodeMove($this, $referenceNode, NodeDataRepository::POSITION_LAST);
        $this->setPath(NodePaths::addNodePathSegment($referenceNode->getPath(), $name));

        $this->nodeDataRepository->setNewIndex($this->nodeData, NodeDataRepository::POSITION_LAST);
        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitAfterNodeMove($this, $referenceNode, NodeDataRepository::POSITION_LAST);
        $this->emitNodeUpdated($this);
    }

    /**
     * Copies this node before the given node
     *
     * @param NodeInterface $referenceNode
     * @param string $nodeName
     * @return NodeInterface
     * @throws NodeConstraintException
     * @throws NodeException
     * @throws NodeExistsException
     * @throws NodeTypeNotFoundException
     * @api
     */
    public function copyBefore(NodeInterface $referenceNode, $nodeName): NodeInterface
    {
        if ($referenceNode->getParent()->getNode($nodeName) !== null) {
            throw new NodeExistsException(sprintf('Node with path "%s/%s" already exists.', $referenceNode->getParent()->getPath(), $nodeName), 1292503465);
        }

        if (!$referenceNode->getParent()->isNodeTypeAllowedAsChildNode($this->getNodeType())) {
            throw new NodeConstraintException(sprintf('Cannot copy %s before %s', $this, $referenceNode), 1402050232);
        }

        $this->emitBeforeNodeCopy($this, $referenceNode->getParent());
        $copiedNode = $this->createRecursiveCopy($referenceNode->getParent(), $nodeName, $this->getNodeType()->isAggregate());
        $copiedNode->moveBefore($referenceNode);

        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitNodeAdded($copiedNode);
        $this->emitAfterNodeCopy($copiedNode, $referenceNode->getParent());

        return $copiedNode;
    }

    /**
     * Copies this node after the given node
     *
     * @param NodeInterface $referenceNode
     * @param string $nodeName
     * @return NodeInterface
     * @throws NodeConstraintException
     * @throws NodeException
     * @throws NodeExistsException
     * @throws NodeTypeNotFoundException
     * @api
     */
    public function copyAfter(NodeInterface $referenceNode, $nodeName): NodeInterface
    {
        if ($referenceNode->getParent()->getNode($nodeName) !== null) {
            throw new NodeExistsException(sprintf('Node with path "%s/%s" already exists.', $referenceNode->getParent()->getPath(), $nodeName), 1292503466);
        }

        if (!$referenceNode->getParent()->isNodeTypeAllowedAsChildNode($this->getNodeType())) {
            throw new NodeConstraintException(sprintf('Cannot copy %s after %s', $this, $referenceNode), 1404648170);
        }

        $this->emitBeforeNodeCopy($this, $referenceNode->getParent());
        $copiedNode = $this->createRecursiveCopy($referenceNode->getParent(), $nodeName, $this->getNodeType()->isAggregate());
        $copiedNode->moveAfter($referenceNode);

        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitNodeAdded($copiedNode);
        $this->emitAfterNodeCopy($copiedNode, $referenceNode->getParent());

        return $copiedNode;
    }

    /**
     * Copies this node into the given node
     *
     * @param NodeInterface $referenceNode
     * @param string $nodeName
     * @return NodeInterface
     * @throws NodeConstraintException
     * @throws NodeException
     * @throws NodeExistsException
     * @throws NodeTypeNotFoundException
     * @api
     */
    public function copyInto(NodeInterface $referenceNode, $nodeName): NodeInterface
    {
        $this->emitBeforeNodeCopy($this, $referenceNode);
        $copiedNode = $this->copyIntoInternal($referenceNode, $nodeName, $this->getNodeType()->isAggregate());
        $this->emitAfterNodeCopy($copiedNode, $referenceNode);

        return $copiedNode;
    }

    /**
     * Internal method to do the actual copying.
     *
     * For behavior of the $detachedCopy parameter, see method Node::createRecursiveCopy().
     *
     * @param NodeInterface $referenceNode
     * @param string $nodeName
     * @param boolean $detachedCopy
     * @return NodeInterface
     * @throws NodeConstraintException
     * @throws NodeException
     * @throws NodeExistsException
     * @throws NodeTypeNotFoundException
     */
    protected function copyIntoInternal(NodeInterface $referenceNode, string $nodeName, bool $detachedCopy): NodeInterface
    {
        if ($referenceNode->getNode($nodeName) !== null) {
            throw new NodeExistsException('Node with path "' . $referenceNode->getPath() . '/' . $nodeName . '" already exists.', 1292503467);
        }

        // On copy we basically re-recreate an existing node on a new location. As we skip the constraints check on
        // node creation we should do the same while writing the node on the new location.
        if (($referenceNode instanceof Node && !$referenceNode->willChildNodeBeAutoCreated($nodeName)) && !$referenceNode->isNodeTypeAllowedAsChildNode($this->getNodeType())) {
            throw new NodeConstraintException(sprintf('Cannot copy "%s" into "%s" due to node type constraints.', $this->__toString(), $referenceNode->__toString()), 1404648177);
        }

        $copiedNode = $this->createRecursiveCopy($referenceNode, $nodeName, $detachedCopy);

        $this->context->getFirstLevelNodeCache()->flush();

        $this->emitNodeAdded($copiedNode);

        return $copiedNode;
    }

    /**
     * Sets the specified property.
     *
     * If the node has a content object attached, the property will be set there
     * if it is settable.
     *
     * @param string $propertyName Name of the property
     * @param mixed $value Value of the property
     * @return mixed
     * @throws NodeException
     * @throws NodeTypeNotFoundException
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     * @api
     */
    public function setProperty($propertyName, $value): void
    {
        $this->materializeNodeDataAsNeeded();
        // Arrays could potentially contain entities and objects could be entities. In that case even if the object is the same it needs to be persisted in NodeData.
        if (!is_object($value) && !is_array($value) && $this->getProperty($propertyName) === $value) {
            return;
        }
        $oldValue = $this->hasProperty($propertyName) ? $this->getProperty($propertyName) : null;
        $this->emitBeforeNodePropertyChange($this, $propertyName, $oldValue, $value);
        $this->nodeData->setProperty($propertyName, $value);

        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitNodePropertyChanged($this, $propertyName, $oldValue, $value);
        $this->emitNodeUpdated($this);
    }

    /**
     * If this node has a property with the given name.
     *
     * If the node has a content object attached, the property will be checked
     * there.
     *
     * @param string $propertyName
     * @return boolean
     * @api
     */
    public function hasProperty($propertyName): bool
    {
        return $this->nodeData->hasProperty($propertyName);
    }

    /**
     * Returns the specified property.
     *
     * If the node has a content object attached, the property will be fetched
     * there if it is gettable.
     *
     * @param string $propertyName Name of the property
     * @param boolean $returnNodesAsIdentifiers If enabled, references to nodes are returned as node identifiers instead of NodeInterface instances
     * @return mixed value of the property
     * @throws NodeException
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     * @throws NodeTypeNotFoundException
     * @api
     */
    public function getProperty($propertyName, bool $returnNodesAsIdentifiers = false)
    {
        $value = $this->nodeData->getProperty($propertyName);
        $nodeType = $this->getNodeType();
        $expectedPropertyType = null;

        if ($nodeType !== null) {
            $expectedPropertyType = $nodeType->getPropertyType($propertyName);
        }

        if (
            isset($expectedPropertyType) &&
            $expectedPropertyType === 'Neos\Media\Domain\Model\ImageInterface' &&
            empty($value)
        ) {
            return null;
        }

        if (empty($value)) {
            return $value;
        }

        if (!$nodeType->hasConfiguration('properties.' . $propertyName)) {
            return $value;
        }

        if ($expectedPropertyType === 'references') {
            return ($returnNodesAsIdentifiers ? $value : $this->resolvePropertyReferences($value));
        }

        if ($expectedPropertyType === 'reference') {
            return ($returnNodesAsIdentifiers ? $value : $this->context->getNodeByIdentifier($value));
        }

        return $this->propertyMapper->convert($value, $expectedPropertyType);
    }

    /**
     * Maps the property value (an array of node identifiers) to the Node objects if needed.
     *
     * @param array $value
     * @return array
     */
    protected function resolvePropertyReferences(array $value = []): array
    {
        $nodes = array_map(function ($nodeIdentifier) {
            return $this->context->getNodeByIdentifier($nodeIdentifier);
        }, $value);

        return array_filter($nodes);
    }

    /**
     * Removes the specified property.
     *
     * If the node has a content object attached, the property will not be removed on
     * that object if it exists.
     *
     * @param string $propertyName Name of the property
     * @return void
     * @throws NodeException if the node does not contain the specified property
     * @throws NodeTypeNotFoundException
     */
    public function removeProperty($propertyName): void
    {
        if (!$this->hasProperty($propertyName)) {
            return;
        }
        $this->materializeNodeDataAsNeeded();
        $this->nodeData->removeProperty($propertyName);

        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitNodeUpdated($this);
    }

    /**
     * Returns all properties of this node.
     *
     * If the node has a content object attached, the properties will be fetched
     * there.
     *
     * @param boolean $returnNodesAsIdentifiers If enabled, references to nodes are returned as node identifiers instead of NodeData objects
     * @return PropertyCollectionInterface Property values, indexed by their name
     * @throws NodeException
     * @throws NodeTypeNotFoundException
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     * @api
     */
    public function getProperties(bool $returnNodesAsIdentifiers = false): PropertyCollectionInterface
    {
        $properties = [];
        foreach ($this->getPropertyNames() as $propertyName) {
            $properties[$propertyName] = $this->getProperty($propertyName, $returnNodesAsIdentifiers);
        }

        return new ArrayPropertyCollection($properties);
    }

    /**
     * Returns the names of all properties of this node.
     *
     * @return string[] Property names
     * @api
     */
    public function getPropertyNames()
    {
        return $this->nodeData->getPropertyNames();
    }

    /**
     * Sets a content object for this node.
     *
     * @param object $contentObject The content object
     * @return void
     * @deprecated with version 4.3. Attaching entities to nodes never really worked. Instead you can reference objects as node properties via their identifier
     * @throws NodeTypeNotFoundException
     * @throws NodeException
     */
    public function setContentObject($contentObject): void
    {
        if ($this->getContentObject() === $contentObject) {
            return;
        }
        $this->materializeNodeDataAsNeeded();
        $this->nodeData->setContentObject($contentObject);

        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitNodeUpdated($this);
    }

    /**
     * Returns the content object of this node (if any).
     *
     * @return object
     * @deprecated with version 4.3. Attaching entities to nodes never really worked. Instead you can reference objects as node properties via their identifier
     */
    public function getContentObject()
    {
        return $this->nodeData->getContentObject();
    }

    /**
     * Unsets the content object of this node.
     *
     * @return void
     * @throws NodeTypeNotFoundException
     * @throws NodeException
     * @deprecated with version 4.3. Attaching entities to nodes never really worked. Instead you can reference objects as node properties via their identifier
     */
    public function unsetContentObject(): void
    {
        if ($this->getContentObject() === null) {
            return;
        }
        $this->materializeNodeDataAsNeeded();
        $this->nodeData->unsetContentObject();

        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitNodeUpdated($this);
    }

    /**
     * Sets the node type of this node.
     *
     * @param NodeType $nodeType
     * @return void
     * @throws NodeException
     * @throws NodeTypeNotFoundException
     * @api
     */
    public function setNodeType(NodeType $nodeType): void
    {
        if ($this->getNodeType() === $nodeType) {
            return;
        }
        if (!$this->isNodeDataMatchingContext()) {
            $this->materializeNodeData();
        }
        $this->nodeData->setNodeType($nodeType);

        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitNodeUpdated($this);
    }

    /**
     * Returns the node type of this node.
     *
     * @return NodeType
     * @api
     * @throws NodeTypeNotFoundException
     */
    public function getNodeType(): NodeType
    {
        return $this->nodeData->getNodeType();
    }

    /**
     * Creates, adds and returns a child node of this node. Also sets default
     * properties and creates default subnodes.
     *
     * @param string $name Name of the new node
     * @param NodeType $nodeType Node type of the new node (optional)
     * @param string $identifier The identifier of the node, unique within the workspace, optional(!)
     * @return NodeInterface
     * @throws NodeConfigurationException
     * @throws NodeConstraintException
     * @throws NodeException
     * @throws NodeExistsException
     * @throws NodeTypeNotFoundException
     * @api
     */
    public function createNode($name, NodeType $nodeType = null, $identifier = null): NodeInterface
    {
        $this->emitBeforeNodeCreate($this, $name, $nodeType, $identifier);
        $newNode = $this->createSingleNode($name, $nodeType, $identifier);
        if ($nodeType !== null) {
            foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $propertyValue) {
                if (substr($propertyName, 0, 1) === '_') {
                    ObjectAccess::setProperty($newNode, substr($propertyName, 1), $propertyValue);
                } else {
                    $newNode->setProperty($propertyName, $propertyValue);
                }
            }

            foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeName => $childNodeType) {
                $childNodeIdentifier = Utility::buildAutoCreatedChildNodeIdentifier($childNodeName, $newNode->getIdentifier());
                $alreadyPresentChildNode = $newNode->getNode($childNodeName);
                if ($alreadyPresentChildNode === null) {
                    $newNode->createNode($childNodeName, $childNodeType, $childNodeIdentifier);
                }
            }
        }

        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitNodeAdded($newNode);
        $this->emitAfterNodeCreate($newNode);

        return $newNode;
    }

    /**
     * Creates, adds and returns a child node of this node, without setting default
     * properties or creating subnodes. Only used internally.
     *
     * For internal use only!
     * TODO: New SiteImportService uses createNode() and DQL. When we drop the LegagcySiteImportService we can change this to protected.
     *
     * @param string $name Name of the new node
     * @param NodeType $nodeType Node type of the new node (optional)
     * @param string $identifier The identifier of the node, unique within the workspace, optional(!)
     * @return NodeInterface
     * @throws NodeConfigurationException
     * @throws NodeConstraintException
     * @throws NodeException
     * @throws NodeExistsException
     * @throws NodeTypeNotFoundException
     */
    public function createSingleNode($name, NodeType $nodeType = null, $identifier = null): NodeInterface
    {
        if ($nodeType !== null && !$this->willChildNodeBeAutoCreated($name) && !$this->isNodeTypeAllowedAsChildNode($nodeType)) {
            throw new NodeConstraintException('Cannot create new node "' . $name . '" of Type "' . $nodeType->getName() . '" in ' . $this->__toString(), 1400782413);
        }

        $dimensions = $this->context->getTargetDimensionValues();

        $nodeData = $this->nodeData->createSingleNodeData($name, $nodeType, $identifier, $this->context->getWorkspace(), $dimensions);
        $node = $this->nodeFactory->createFromNodeData($nodeData, $this->context);

        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitNodeAdded($node);

        return $node;
    }

    /**
     * Checks if the given Node $name is configured as auto-created childNode in the NodeType configuration.
     *
     * @param string $name The node name to check.
     * @return boolean true if the given nodeName is configured as auto-created child node.
     * @throws NodeTypeNotFoundException
     */
    protected function willChildNodeBeAutoCreated(string $name): bool
    {
        $autoCreatedChildNodes = $this->getNodeType()->getAutoCreatedChildNodes();

        return isset($autoCreatedChildNodes[$name]);
    }

    /**
     * Creates and persists a node from the given $nodeTemplate as child node
     *
     * @param NodeTemplate $nodeTemplate
     * @param string $nodeName name of the new node. If not specified the name of the nodeTemplate will be used.
     * @return NodeInterface the freshly generated node
     * @api
     * @throws NodeConfigurationException
     */
    public function createNodeFromTemplate(NodeTemplate $nodeTemplate, $nodeName = null): NodeInterface
    {
        $nodeData = $this->nodeData->createNodeDataFromTemplate($nodeTemplate, $nodeName, $this->context->getWorkspace(), $this->context->getDimensions());
        $node = $this->nodeFactory->createFromNodeData($nodeData, $this->context);

        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitNodeAdded($node);

        return $node;
    }

    /**
     * Returns a node specified by the given relative path.
     *
     * @param string $path Path specifying the node, relative to this node
     * @return NodeInterface|null The specified node or NULL if no such node exists
     * @deprecated with version 4.3 - use TraversableNodeInterface::findNamedChildNode() instead
     */
    public function getNode($path): ?NodeInterface
    {
        $absolutePath = $this->nodeService->normalizePath($path, $this->getPath());
        $node = $this->context->getFirstLevelNodeCache()->getByPath($absolutePath);
        if ($node !== false) {
            return $node;
        }
        $node = $this->nodeDataRepository->findOneByPathInContext($absolutePath, $this->context);
        $this->context->getFirstLevelNodeCache()->setByPath($absolutePath, $node);
        return $node;
    }

    /**
     * Returns the primary child node of this node.
     *
     * Which node acts as a primary child node will in the future depend on the
     * node type. For now it is just the first child node.
     *
     * @return NodeInterface|null The primary child node or NULL if no such node exists
     * @deprecated with version 4.3. use TraversableNodeInterface::findChildNodes() instead, the first result is considered the "primary child node"
     */
    public function getPrimaryChildNode(): ?NodeInterface
    {
        return $this->nodeDataRepository->findFirstByParentAndNodeTypeInContext($this->getPath(), null, $this->context);
    }

    /**
     * Returns all direct child nodes of this node.
     * If a node type is specified, only nodes of that type are returned.
     *
     * @param string $nodeTypeFilter If specified, only nodes with that node type are considered
     * @param integer $limit An optional limit for the number of nodes to find. Added or removed nodes can still change the number nodes!
     * @param integer $offset An optional offset for the query
     * @return array<\Neos\ContentRepository\Domain\Model\NodeInterface> An array of nodes or an empty array if no child nodes matched
     * @deprecated with version 4.3, use TraversableNodeInterface::findChildNodes() instead.
     */
    public function getChildNodes($nodeTypeFilter = null, $limit = null, $offset = null): array
    {
        $nodes = $this->context->getFirstLevelNodeCache()->getChildNodesByPathAndNodeTypeFilter($this->getPath(), $nodeTypeFilter);
        if ($nodes === false) {
            $nodes = $this->nodeDataRepository->findByParentAndNodeTypeInContext($this->getPath(), $nodeTypeFilter, $this->context, false);
            $this->context->getFirstLevelNodeCache()->setChildNodesByPathAndNodeTypeFilter($this->getPath(), $nodeTypeFilter, $nodes);
        }

        if ($offset !== null || $limit !== null) {
            $offset = ($offset === null) ? 0 : $offset;

            return array_slice($nodes, $offset, $limit);
        }

        return $nodes;
    }

    /**
     * Returns the number of child nodes a similar getChildNodes() call would return.
     *
     * @param string $nodeTypeFilter If specified, only nodes with that node type are considered
     * @return integer The number of child nodes
     * @api
     */
    public function getNumberOfChildNodes($nodeTypeFilter = null): int
    {
        return $this->nodeData->getNumberOfChildNodes($nodeTypeFilter, $this->context->getWorkspace(), $this->context->getDimensions());
    }

    /**
     * Checks if this node has any child nodes.
     *
     * @param string $nodeTypeFilter If specified, only nodes with that node type are considered
     * @return boolean true if this node has child nodes, otherwise false
     * @deprecated with version 4.3, use TraversableNodeInterface::findChildNodes() instead and count the result
     */
    public function hasChildNodes($nodeTypeFilter = null): bool
    {
        return ($this->getNumberOfChildNodes($nodeTypeFilter) > 0);
    }

    /**
     * Removes this node and all its child nodes. This is an alias for setRemoved(true)
     *
     * @return void
     * @api
     * @throws NodeTypeNotFoundException
     * @throws NodeException
     */
    public function remove(): void
    {
        $this->setRemoved(true);
    }

    /**
     * Enables using the remove method when only setters are available
     *
     * @param boolean $removed If true, this node and it's child nodes will be removed. If it is false only this node will be restored.
     * @return void
     * @throws NodeException
     * @throws NodeTypeNotFoundException
     * @api
     */
    public function setRemoved($removed): void
    {
        if (!$this->isNodeDataMatchingContext()) {
            $this->materializeNodeData();
        }

        if ((boolean)$removed === true) {
            /** @var $childNode Node */
            foreach ($this->getChildNodes() as $childNode) {
                $childNode->setRemoved(true);
            }

            $this->nodeData->setRemoved(true);
            $this->emitNodeRemoved($this);
        } else {
            $this->nodeData->setRemoved(false);
            $this->emitNodeUpdated($this);
        }

        $this->context->getFirstLevelNodeCache()->flush();
    }

    /**
     * If this node is a removed node.
     *
     * @return boolean
     */
    public function isRemoved(): bool
    {
        return $this->nodeData->isRemoved();
    }

    /**
     * Sets the "hidden" flag for this node.
     *
     * @param boolean $hidden If true, this Node will be hidden
     * @return void
     * @api
     * @throws NodeTypeNotFoundException
     * @throws NodeException
     */
    public function setHidden($hidden): void
    {
        if ($this->isHidden() === $hidden) {
            return;
        }
        $this->materializeNodeDataAsNeeded();
        $this->nodeData->setHidden($hidden);

        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitNodeUpdated($this);
    }

    /**
     * Returns the current state of the hidden flag
     *
     * @return boolean
     * @api
     */
    public function isHidden(): bool
    {
        return $this->nodeData->isHidden();
    }

    /**
     * Sets the date and time when this node becomes potentially visible.
     *
     * @param \DateTimeInterface $dateTime Date before this node should be hidden
     * @return void
     * @api
     * @throws NodeTypeNotFoundException
     * @throws NodeException
     */
    public function setHiddenBeforeDateTime(\DateTimeInterface $dateTime = null): void
    {
        if ($this->getHiddenBeforeDateTime() instanceof \DateTime && $dateTime instanceof \DateTime && $this->getHiddenBeforeDateTime()->format(\DateTime::W3C) === $dateTime->format(\DateTime::W3C)) {
            return;
        }
        $this->materializeNodeDataAsNeeded();
        $this->nodeData->setHiddenBeforeDateTime($dateTime);

        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitNodeUpdated($this);
    }

    /**
     * Returns the date and time before which this node will be automatically hidden.
     *
     * @return \DateTimeInterface Date before this node will be hidden
     */
    public function getHiddenBeforeDateTime(): ?\DateTimeInterface
    {
        return $this->nodeData->getHiddenBeforeDateTime();
    }

    /**
     * Sets the date and time when this node should be automatically hidden
     *
     * @param \DateTimeInterface $dateTime Date after which this node should be hidden
     * @return void
     * @api
     * @throws NodeTypeNotFoundException
     * @throws NodeException
     */
    public function setHiddenAfterDateTime(\DateTimeInterface $dateTime = null): void
    {
        if ($this->getHiddenAfterDateTime() instanceof \DateTimeInterface && $dateTime instanceof \DateTimeInterface && $this->getHiddenAfterDateTime()->format(\DateTime::W3C) === $dateTime->format(\DateTime::W3C)) {
            return;
        }
        $this->materializeNodeDataAsNeeded();
        $this->nodeData->setHiddenAfterDateTime($dateTime);

        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitNodeUpdated($this);
    }

    /**
     * Returns the date and time after which this node will be automatically hidden.
     *
     * @return \DateTimeInterface Date after which this node will be hidden
     */
    public function getHiddenAfterDateTime(): ?\DateTimeInterface
    {
        return $this->nodeData->getHiddenAfterDateTime();
    }

    /**
     * Sets if this node should be hidden in indexes, such as a site navigation.
     *
     * @param boolean $hidden true if it should be hidden, otherwise false
     * @return void
     * @api
     * @throws NodeTypeNotFoundException
     * @throws NodeException
     */
    public function setHiddenInIndex($hidden): void
    {
        if ($this->isHiddenInIndex() === $hidden) {
            return;
        }
        $this->materializeNodeDataAsNeeded();
        $this->nodeData->setHiddenInIndex($hidden);

        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitNodeUpdated($this);
    }

    /**
     * If this node should be hidden in indexes
     *
     * @return boolean
     * @api
     */
    public function isHiddenInIndex(): bool
    {
        return $this->nodeData->isHiddenInIndex();
    }

    /**
     * Sets the roles which are required to access this node
     *
     * @param array $accessRoles
     * @return void
     * @api
     * @throws NodeTypeNotFoundException
     * @throws NodeException
     * @deprecated with version 4.3. Use a Policy to restrict access to nodes
     */
    public function setAccessRoles(array $accessRoles): void
    {
        if ($this->getAccessRoles() === $accessRoles) {
            return;
        }
        if (!$this->isNodeDataMatchingContext()) {
            $this->materializeNodeData();
        }
        $this->nodeData->setAccessRoles($accessRoles);

        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitNodeUpdated($this);
    }

    /**
     * Returns the names of defined access roles
     *
     * @return array
     * @deprecated with version 4.3. Use a Policy to restrict access to nodes
     */
    public function getAccessRoles(): array
    {
        return $this->nodeData->getAccessRoles();
    }

    /**
     * Tells if a node, in general,  has access restrictions, independent of the
     * current security context.
     *
     * @return boolean
     * @deprecated with version 4.3. Use a Policy to restrict access to nodes
     */
    public function hasAccessRestrictions(): bool
    {
        return $this->nodeData->hasAccessRestrictions();
    }

    /**
     * Tells if this node is "visible".
     *
     * For this the "hidden" flag and the "hiddenBeforeDateTime" and "hiddenAfterDateTime" dates are
     * taken into account.
     *
     * @return boolean
     */
    public function isVisible(): bool
    {
        if ($this->nodeData->isVisible() === false) {
            return false;
        }
        $currentDateTime = $this->context->getCurrentDateTime();
        if ($this->getHiddenBeforeDateTime() !== null && $this->getHiddenBeforeDateTime() > $currentDateTime) {
            return false;
        }
        if ($this->getHiddenAfterDateTime() !== null && $this->getHiddenAfterDateTime() < $currentDateTime) {
            return false;
        }

        return true;
    }

    /**
     * Tells if this node may be accessed according to the current security context.
     *
     * @return boolean
     * @deprecated with version 4.3. Use a Policy to restrict access to nodes
     */
    public function isAccessible(): bool
    {
        return $this->nodeData->isAccessible();
    }

    /**
     * Returns the context this node operates in.
     *
     * @return Context
     * @internal This method is not meant to be called in userland code
     */
    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * Materialize the node data either shallow or with child nodes depending
     * on how we materialize (workspace or dimensions).
     * A workspace materialize doesn't necessarily need the child nodes materialized as well
     * unless we do structural changes in which case "materializeNodeData" should be used directly.
     * For dimensional materialization we always want child nodes though.
     *
     * @return void
     * @throws NodeTypeNotFoundException
     * @throws NodeException
     */
    protected function materializeNodeDataAsNeeded(): void
    {
        $dimensionsMatching = $this->dimensionsAreMatchingTargetDimensionValues();
        $workspaceMatching = $this->workspaceIsMatchingContext();

        // If we need to materialize across dimensions we should always take child nodes into consideration
        if (!$dimensionsMatching) {
            $this->materializeNodeData();
            return;
        }

        if (!$workspaceMatching) {
            $this->shallowMaterializeNodeData();
        }
    }

    /**
     * Materializes the original node data (of a different workspace) into the current
     * workspace. And unlike the shallow counterpart does that for all auto-created
     * child nodes as well.
     *
     * @return void
     * @throws NodeException
     * @throws NodeTypeNotFoundException
     * @see shallowMaterializeNodeData
     */
    protected function materializeNodeData(): void
    {
        $this->shallowMaterializeNodeData();
        $nodeType = $this->getNodeType();
        foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeName => $childNodeConfiguration) {
            $childNode = $this->getNode($childNodeName);
            if ($childNode instanceof Node) {
                $childNode->materializeNodeData();
            }
        }
    }

    /**
     * Materializes the original node data (of a different workspace) into the current
     * workspace.
     *
     * @return void
     */
    protected function shallowMaterializeNodeData(): void
    {
        if ($this->isNodeDataMatchingContext()) {
            return;
        }

        $dimensions = $this->context->getTargetDimensionValues();

        $newNodeData = new NodeData($this->nodeData->getPath(), $this->context->getWorkspace(), $this->nodeData->getIdentifier(), $dimensions);
        $this->nodeDataRepository->add($newNodeData);

        $newNodeData->similarize($this->nodeData);

        $this->nodeData = $newNodeData;
        $this->nodeDataIsMatchingContext = true;
    }

    /**
     * Create a recursive copy of this node below $referenceNode with $nodeName.
     *
     * $detachedCopy only has an influence if we are copying from one dimension to the other, possibly creating a new
     * node variant:
     *
     * - If $detachedCopy is true, the whole (recursive) copy is done without connecting original and copied node,
     *   so NOT CREATING a new node variant.
     * - If $detachedCopy is false, and the node does not yet have a variant in the target dimension, we are CREATING
     *   a new node variant.
     *
     * As a caller of this method, $detachedCopy should be true if $this->getNodeType()->isAggregate() is true, and false
     * otherwise.
     *
     * @param NodeInterface $referenceNode
     * @param string $nodeName
     * @param boolean $detachedCopy
     * @return NodeInterface
     * @throws NodeConstraintException
     * @throws NodeException
     * @throws NodeExistsException
     * @throws NodeTypeNotFoundException
     */
    protected function createRecursiveCopy(NodeInterface $referenceNode, string $nodeName, bool $detachedCopy): NodeInterface
    {
        $identifier = null;

        $referenceNodeDimensions = $referenceNode->getDimensions();
        $referenceNodeDimensionsHash = Utility::sortDimensionValueArrayAndReturnDimensionsHash($referenceNodeDimensions);
        $thisDimensions = $this->getDimensions();
        $thisNodeDimensionsHash = Utility::sortDimensionValueArrayAndReturnDimensionsHash($thisDimensions);
        if ($detachedCopy === false && $referenceNodeDimensionsHash !== $thisNodeDimensionsHash && $referenceNode->getContext()->getNodeByIdentifier($this->getIdentifier()) === null) {
            // If the target dimensions are different than this one, and there is no node shadowing this one in the target dimension yet, we use the same
            // node identifier, effectively creating a new node variant.
            $identifier = $this->getIdentifier();
        }

        $copiedNode = $referenceNode->createSingleNode($nodeName, null, $identifier);

        if ($copiedNode instanceof Node) {
            $copiedNode->similarize($this, true);
        }
        /** @var $childNode Node */
        foreach ($this->getChildNodes() as $childNode) {
            // Prevent recursive copy when copying into itself
            if ($childNode->getIdentifier() !== $copiedNode->getIdentifier()) {
                $childNode->copyIntoInternal($copiedNode, $childNode->getName(), $detachedCopy);
            }
        }

        return $copiedNode;
    }

    /**
     * The NodeData matches the context if the workspace matches exactly.
     * Needs to be adjusted for further context dimensions.
     *
     * @return boolean
     */
    protected function isNodeDataMatchingContext(): bool
    {
        if ($this->nodeDataIsMatchingContext === null) {
            $workspacesMatch = $this->workspaceIsMatchingContext();
            $this->nodeDataIsMatchingContext = $workspacesMatch && $this->dimensionsAreMatchingTargetDimensionValues();
        }

        return $this->nodeDataIsMatchingContext;
    }

    /**
     * @return bool
     */
    protected function workspaceIsMatchingContext(): bool
    {
        return ($this->nodeData->getWorkspace() !== null && $this->context->getWorkspace() !== null && $this->nodeData->getWorkspace()->getName() === $this->context->getWorkspace()->getName());
    }

    /**
     * For internal use in createRecursiveCopy.
     *
     * @param NodeInterface $sourceNode
     * @param boolean $isCopy
     * @return void
     */
    public function similarize(NodeInterface $sourceNode, $isCopy = false): void
    {
        $this->nodeData->similarize($sourceNode->getNodeData(), $isCopy);
    }

    /**
     * @return NodeData
     * @internal This is not meant to be used in userland code
     */
    public function getNodeData(): NodeData
    {
        return $this->nodeData;
    }

    /**
     * Returns a string which distinctly identifies this object and thus can be used as an identifier for cache entries
     * related to this object.
     *
     * @return string
     */
    public function getCacheEntryIdentifier(): string
    {
        return $this->getContextPath();
    }

    /**
     * Return the assigned content dimensions of the node.
     *
     * @return array
     */
    public function getDimensions(): array
    {
        return $this->nodeData->getDimensionValues();
    }

    /**
     * Given a context a new node is returned that is like this node, but
     * lives in the new context.
     *
     * @param Context $context
     * @return NodeInterface
     * @throws NodeConfigurationException
     * @throws NodeTypeNotFoundException
     */
    public function createVariantForContext($context): NodeInterface
    {
        $autoCreatedChildNodes = [];
        $nodeType = $this->getNodeType();
        foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeName => $childNodeConfiguration) {
            $childNode = $this->getNode($childNodeName);
            if ($childNode !== null) {
                $autoCreatedChildNodes[$childNodeName] = $childNode;
            }
        }

        $nodeData = new NodeData($this->nodeData->getPath(), $context->getWorkspace(), $this->nodeData->getIdentifier(), $context->getTargetDimensionValues());
        $nodeData->similarize($this->nodeData);

        if ($this->context !== $context) {
            $node = $this->nodeFactory->createFromNodeData($nodeData, $context);
        } else {
            $this->setNodeData($nodeData);
            $node = $this;
        }

        $this->context->getFirstLevelNodeCache()->flush();
        $this->emitNodeAdded($node);

        /**
         * @var $autoCreatedChildNode NodeInterface
         */
        foreach ($autoCreatedChildNodes as $autoCreatedChildNode) {
            $existingChildNode = $node->getNode($autoCreatedChildNode->getName());
            if ($existingChildNode === null || !$existingChildNode->dimensionsAreMatchingTargetDimensionValues()) {
                // only if needed, see https://github.com/neos/neos-development-collection/issues/782
                $autoCreatedChildNode->createVariantForContext($context);
            }
        }

        return $node;
    }

    /**
     * Internal method
     *
     * The dimension value of this node has to match the current target dimension value (must be higher in priority or equal)
     *
     * @return boolean
     */
    public function dimensionsAreMatchingTargetDimensionValues(): bool
    {
        $dimensions = $this->getDimensions();
        $contextDimensions = $this->context->getDimensions();
        foreach ($this->context->getTargetDimensions() as $dimensionName => $targetDimensionValue) {
            if (!isset($dimensions[$dimensionName])) {
                if ($targetDimensionValue === null) {
                    continue;
                } else {
                    return false;
                }
            } elseif ($targetDimensionValue === null && $dimensions[$dimensionName] === []) {
                continue;
            } elseif (!in_array($targetDimensionValue, $dimensions[$dimensionName], true)) {
                $contextDimensionValues = $contextDimensions[$dimensionName];
                $targetPositionInContext = array_search($targetDimensionValue, $contextDimensionValues, true);
                $nodePositionInContext = min(array_map(function ($value) use ($contextDimensionValues) {
                    return array_search($value, $contextDimensionValues, true);
                }, $dimensions[$dimensionName]));

                $val = $targetPositionInContext !== false && $nodePositionInContext !== false && $targetPositionInContext >= $nodePositionInContext;
                if ($val === false) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Set the associated NodeData in regards to the Context.
     *
     * NOTE: This is internal only and should not be used outside of the ContentRepository.
     *
     * @param NodeData $nodeData
     * @return void
     * @internal This method is not meant to be called from userland
     */
    public function setNodeData(NodeData $nodeData): void
    {
        $this->nodeData = $nodeData;
        $this->nodeDataIsMatchingContext = null;
    }

    /**
     * Checks if the given $nodeType would be allowed as a child node of this node according to the configured constraints.
     *
     * @param NodeType $nodeType
     * @return boolean true if the passed $nodeType is allowed as child node
     * @throws NodeTypeNotFoundException
     */
    public function isNodeTypeAllowedAsChildNode(NodeType $nodeType): bool
    {
        if ($this->isAutoCreated()) {
            return $this->getParent()->getNodeType()->allowsGrandchildNodeType($this->getName(), $nodeType);
        } else {
            return $this->getNodeType()->allowsChildNodeType($nodeType);
        }
    }

    /**
     * Determine if this node is configured as auto-created childNode of the parent node. If that is the case, it
     * should not be deleted.
     *
     * @return boolean true if this node is auto-created by the parent.
     * @deprecated with version 4.3. This information should not be required usually. Otherwise it can be determined via:
     * if (array_key_exists((string)$node->getNodeName(), $parent->getNodeType()->getAutoCreatedChildNodes()))
     */
    public function isAutoCreated(): bool
    {
        return $this->isTethered();
    }

    /**
     * Whether or not this node is tethered to its parent, fka auto created child node
     *
     * @return bool
     */
    public function isTethered(): bool
    {
        $parent = $this->getParent();
        if ($parent === null) {
            return false;
        }
        if (array_key_exists($this->getName(), $parent->getNodeType()->getAutoCreatedChildNodes())) {
            return true;
        }

        return false;
    }

    /**
     * Set the status of the associated NodeData in regards to the Context.
     *
     * NOTE: This is internal only and should not be used outside of the ContentRepository.
     *
     * @param boolean $status
     * @return void
     */
    public function setNodeDataIsMatchingContext(bool $status = null): void
    {
        $this->nodeDataIsMatchingContext = $status;
    }

    /**
     * @return ContentStreamIdentifier
     * @throws NodeMethodIsUnsupported
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        throw new NodeMethodIsUnsupported('getContentStreamIdentifier is unsupported in the legacy Node API.', 1542893545);
    }

    /**
     * @return NodeAggregateIdentifier
     * @throws \Exception
     */
    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return NodeAggregateIdentifier::fromString($this->getIdentifier());
    }

    /**
     * @return NodeTypeName
     * @throws NodeTypeNotFoundException
     */
    public function getNodeTypeName(): NodeTypeName
    {
        return NodeTypeName::fromString($this->getNodeType()->getName());
    }

    /**
     * @return NodeName|null
     */
    public function getNodeName(): ?NodeName
    {
        return NodeName::fromString($this->getName());
    }

    /**
     * @return DimensionSpacePoint
     * @throws NodeMethodIsUnsupported
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        throw new NodeMethodIsUnsupported('getDimensionSpacePoint is unsupported in the legacy Node API.', 1542893558);
    }

    /**
     * @return OriginDimensionSpacePoint
     * @throws NodeMethodIsUnsupported
     */
    public function getOriginDimensionSpacePoint(): OriginDimensionSpacePoint
    {
        throw new NodeMethodIsUnsupported('getOriginDimensionSpacePoint is unsupported in the legacy Node API.', 1542893562);
    }

    /**
     * @return TraversableNodeInterface
     * @throws NodeException if no parent node was found (= this is the root node)
     */
    public function findParentNode(): TraversableNodeInterface
    {
        /** @var TraversableNodeInterface $parentNode It's safe to return the old NodeInterface as TraversableNodeInterface; as the base implementation "Node" (this class) implements both interfaces at the same time. */
        $parentNode = $this->getParent();
        if ($parentNode === null) {
            throw new NodeException('Parent node not found', 1542983610);
        }
        return $parentNode;
    }

    public function findNodePath(): NodePath
    {
        return NodePath::fromString($this->getPath());
    }

    /**
     * @param NodeName $nodeName
     * @return TraversableNodeInterface
     * @throws NodeException
     */
    public function findNamedChildNode(NodeName $nodeName): TraversableNodeInterface
    {
        /** @var TraversableNodeInterface $childNode It's safe to return the old NodeInterface as TraversableNodeInterface; as the base implementation "Node" (this class) implements both interfaces at the same time. */
        $childNode = $this->getNode((string)$nodeName);
        if ($childNode === null) {
            throw new NodeException(sprintf('Child node named "%s" not found', $nodeName), 1543406006);
        }
        return $childNode;
    }

    /**
     * Returns all direct child nodes of this node.
     * If a node type is specified, only nodes of that type are returned.
     *
     * @param NodeTypeConstraints $nodeTypeConstraints If specified, only nodes with that node type are considered
     * @param int $limit An optional limit for the number of nodes to find. Added or removed nodes can still change the number nodes!
     * @param int $offset An optional offset for the query
     * @return TraversableNodes
     * @api
     */
    public function findChildNodes(NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): TraversableNodes
    {
        /** @noinspection PhpDeprecationInspection */
        $filter = $nodeTypeConstraints !== null ? $nodeTypeConstraints->asLegacyNodeTypeFilterString() : null;
        // It's safe to return the old NodeInterface as TraversableNodeInterface; as the base implementation "Node" (this class) implements both interfaces at the same time.
        return TraversableNodes::fromArray($this->getChildNodes($filter, $limit, $offset));
    }

    /**
     * Returns the number of direct child nodes of this node from its subgraph.
     *
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @return int
     */
    public function countChildNodes(NodeTypeConstraints $nodeTypeConstraints = null): int
    {
        return count($this->getChildNodes($nodeTypeConstraints));
    }

    /**
     * Retrieves and returns all nodes referenced by this node from its subgraph.
     *
     * @return TraversableNodes
     * @throws NodeException
     * @throws NodeTypeNotFoundException
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function findReferencedNodes(): TraversableNodes
    {
        $referencedNodes = [];
        foreach ($this->getNodeType()->getProperties() as $propertyName => $property) {
            $propertyType = $this->getNodeType()->getPropertyType($propertyName);
            if ($propertyType === 'reference' && $this->getProperty($propertyName) instanceof TraversableNodeInterface) {
                $referencedNodes[] = $this->getProperty($propertyName);
            } elseif ($propertyName === 'references' && !empty($this->getProperty($propertyName))) {
                foreach ($this->getProperty($propertyName) as $node) {
                    if ($node instanceof TraversableNodeInterface) {
                        $referencedNodes[] = $node;
                    }
                }
            }
        }

        return TraversableNodes::fromArray($referencedNodes);
    }

    /**
     * Retrieves and returns nodes referenced by this node by name from its subgraph.
     *
     * @param PropertyName $edgeName
     * @return TraversableNodes
     * @throws NodeException
     * @throws NodeTypeNotFoundException
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function findNamedReferencedNodes(PropertyName $edgeName): TraversableNodes
    {
        $referencedNodes = [];
        $propertyName = (string) $edgeName;
        $propertyType = $this->getNodeType()->getPropertyType($propertyName);
        if ($propertyType === 'reference' && $this->getProperty($propertyName) instanceof TraversableNodeInterface) {
            $referencedNodes = [$this->getProperty($propertyName)];
        } elseif ($propertyName === 'references' && !empty($this->getProperty($propertyName))) {
            $referencedNodes = $this->getProperty($propertyName);
        }

        return TraversableNodes::fromArray($referencedNodes);
    }

    /**
     * Retrieves and returns nodes referencing this node from its subgraph.
     *
     * @return TraversableNodes
     * @throws NodeMethodIsUnsupported
     */
    public function findReferencingNodes(): TraversableNodes
    {
        throw new NodeMethodIsUnsupported('findReferencingNodes is unsupported in the legacy Node API.', 1542893575);
    }


    /**
     * Retrieves and returns nodes referencing this node by name from its subgraph.
     *
     * @param PropertyName $edgeName
     * @return TraversableNodes
     * @throws NodeMethodIsUnsupported
     */
    public function findNamedReferencingNodes(PropertyName $edgeName): TraversableNodes
    {
        throw new NodeMethodIsUnsupported('findNamedReferencingNodes is unsupported in the legacy Node API.', 1542893577);
    }

    /**
     * @Flow\Signal
     * @param NodeInterface $movedNode
     * @param NodeInterface $referenceNode
     * @param integer $movePosition
     * @return void
     */
    protected function emitBeforeNodeMove(NodeInterface $movedNode, NodeInterface $referenceNode, int $movePosition): void
    {
    }

    /**
     * @Flow\Signal
     * @param NodeInterface $movedNode
     * @param NodeInterface $referenceNode
     * @param integer $movePosition
     * @return void
     */
    protected function emitAfterNodeMove(NodeInterface $movedNode, NodeInterface $referenceNode, int $movePosition): void
    {
    }

    /**
     * @Flow\Signal
     * @param NodeInterface $sourceNode
     * @param NodeInterface $targetParentNode
     * @return void
     */
    protected function emitBeforeNodeCopy(NodeInterface $sourceNode, NodeInterface $targetParentNode): void
    {
    }

    /**
     * @Flow\Signal
     * @param NodeInterface $copiedNode
     * @param NodeInterface $targetParentNode
     * @return void
     */
    protected function emitAfterNodeCopy(NodeInterface $copiedNode, NodeInterface $targetParentNode): void
    {
    }

    /**
     * Signals that the node path has been changed.
     *
     * @Flow\Signal
     * @param NodeInterface $node
     * @param string $oldPath
     * @param string $newPath
     * @param boolean $recursion true if the node path change was caused because a parent node path was changed
     */
    protected function emitNodePathChanged(NodeInterface $node, $oldPath, $newPath, $recursion)
    {
    }

    /**
     * Signals that a node will be created.
     *
     * @Flow\Signal
     * @param NodeInterface $node
     * @param string $name
     * @param string $nodeType
     * @param string $identifier
     * @return void
     */
    protected function emitBeforeNodeCreate(NodeInterface $node, $name, $nodeType, $identifier)
    {
    }

    /**
     * Signals that a node was created.
     *
     * @Flow\Signal
     * @param NodeInterface $node
     * @return void
     */
    protected function emitAfterNodeCreate(NodeInterface $node)
    {
    }

    /**
     * Signals that a node was added.
     *
     * @Flow\Signal
     * @param NodeInterface $node
     * @return void
     */
    protected function emitNodeAdded(NodeInterface $node)
    {
    }

    /**
     * Signals that a node was updated.
     *
     * @Flow\Signal
     * @param NodeInterface $node
     * @return void
     */
    protected function emitNodeUpdated(NodeInterface $node)
    {
    }

    /**
     * Signals that a node was removed.
     *
     * @Flow\Signal
     * @param NodeInterface $node
     * @return void
     */
    protected function emitNodeRemoved(NodeInterface $node)
    {
    }

    /**
     * Signals that the property of a node will be changed.
     *
     * @Flow\Signal
     * @param NodeInterface $node
     * @param string $propertyName name of the property that has been changed/added
     * @param mixed $oldValue the property value before it was changed or NULL if the property is new
     * @param mixed $newValue the new property value
     * @return void
     */
    protected function emitBeforeNodePropertyChange(NodeInterface $node, $propertyName, $oldValue, $newValue)
    {
    }

    /**
     * Signals that the property of a node was changed.
     *
     * @Flow\Signal
     * @param NodeInterface $node
     * @param string $propertyName name of the property that has been changed/added
     * @param mixed $oldValue the property value before it was changed or NULL if the property is new
     * @param mixed $newValue the new property value
     * @return void
     */
    protected function emitNodePropertyChanged(NodeInterface $node, $propertyName, $oldValue, $newValue)
    {
    }

    /**
     * For debugging purposes, the node can be converted to a string.
     *
     * @return string
     * @throws NodeTypeNotFoundException
     */
    public function __toString(): string
    {
        return 'Node ' . $this->getContextPath() . '[' . $this->getNodeType()->getName() . ']';
    }

    public function equals(TraversableNodeInterface $other): bool
    {
        if ($other instanceof NodeInterface) {
            return $this->getContextPath() === $other->getContextPath();
        }

        // if $other is not a Legacy NodeInterface, they are always different.
        return false;
    }
}
