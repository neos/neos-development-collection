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

use Neos\ContentRepository\Domain\Context\Node\RelationDistributionStrategyIsInvalid;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\PropertyValue;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Context\Node\Command;
use Neos\ContentRepository\Domain\Context\Node\NodeCommandHandler;
use Neos\ContentRepository\Domain\Projection\Content\NodePropertyCollection;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\ContentRepository\Domain;
use Neos\ContentRepository\Domain\ValueObject\ReferencePosition;
use Neos\ContentRepository\Exception;
use Neos\Cache\CacheAwareInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\NodeServiceInterface;
use Neos\ContentRepository\Exception\NodeConstraintException;
use Neos\ContentRepository\Exception\NodeException;
use Neos\ContentRepository\Exception\NodeExistsException;
use Neos\Flow\Utility\Now;

/**
 * This is the main API for storing and retrieving content in the system.
 *
 * @Flow\Scope("prototype")
 * @api
 * @deprecated
 */
class Node implements NodeInterface, CacheAwareInterface
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
     * @var NodeIdentifier
     */
    protected $nodeIdentifier;

    /**
     * @var Domain\ValueObject\NodeAggregateIdentifier
     */
    protected $nodeAggregateIdentifier;

    /**
     * @var int
     */
    protected $index = 0;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var NodeType
     */
    protected $nodeType;

    /**
     * @var bool
     */
    protected $hidden;

    /**
     * @var NodeTypeName
     */
    protected $nodeTypeName;

    /**
     * @var NodePropertyCollection
     */
    protected $properties;

    /**
     * @var Workspace
     */
    protected $workspace;

    /**
     * @var Domain\ValueObject\DimensionSpacePoint
     */
    protected $dimensionSpacePoint;

    /**
     * @var \Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier
     */
    protected $contentStreamIdentifier;


    /**
     * @var NodeName
     */
    protected $nodeName;

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
     * @Flow\Inject
     * @var NodeCommandHandler
     */
    protected $nodeCommandHandler;

    /**
     * @Flow\Inject
     * @var Domain\Service\NodeTypeConstraintService
     * Dependency to be removed once the dependency on the context is removed as well
     */
    protected $nodeTypeConstraintService;

    /**
     * @Flow\Inject
     * @var Domain\Projection\Content\ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @Flow\Inject(lazy=false)
     * @var Now
     */
    protected $now;


    /**
     * Node constructor.
     * @param NodeIdentifier $nodeIdentifier
     * @param NodeTypeName $nodeTypeName
     * @param NodeType $nodeType
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param Domain\ValueObject\NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param \Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier $contentStreamIdentifier
     * @param NodePropertyCollection $properties
     * @param NodeName $nodeName
     * @param bool $hidden
     * @param Context|null $context
     * @Flow\Autowiring(false)
     */
    public function __construct(NodeIdentifier $nodeIdentifier, NodeTypeName $nodeTypeName, NodeType $nodeType, ?DimensionSpacePoint $dimensionSpacePoint, ?Domain\ValueObject\NodeAggregateIdentifier $nodeAggregateIdentifier, ?Domain\Context\ContentStream\ContentStreamIdentifier $contentStreamIdentifier, ?NodePropertyCollection $properties, ?NodeName $nodeName, bool $hidden = false, Context $context = null)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->nodeTypeName = $nodeTypeName;
        $this->nodeType = $nodeType;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->properties = $properties;
        $this->nodeName = $nodeName;
        $this->hidden = $hidden;

        // NodeData is OLD code; so we do not set it anymore to make the system crash if one tries to access it
        //$this->nodeData = $nodeData;
        $this->context = $context;
    }

    /**
     * Returns the absolute path of this node with additional context information (such as the workspace name).
     *
     * Example: /sites/mysitecom/homepage/about@user-admin
     *
     * @return string Node path with context information
     * @api
     * @throws Exception
     */
    public function getContextPath()
    {
        return $this->getNodeIdentifier() . '@' . $this->getContentStreamIdentifier() . '@' . ($this->context ? $this->context->getContentSubgraph()->getDimensionSpacePoint()->serializeForUri() : '');
    }

    /**
     * Set the name of the node to $newName, keeping its position as it is.
     *
     * @param string $newName
     * @return void
     * @throws NodeException if you try to set the name of the root node.
     * @throws \InvalidArgumentException if $newName is invalid
     * @api
     */
    public function setName($newName)
    {
        if ($this->getName() === $newName) {
            return;
        }

        $newNodeName = new NodeName($newName);

        $command = new Command\ChangeNodeName(
            $this->getContentStreamIdentifier(),
            $this->getNodeIdentifier(),
            $newNodeName
        );

        $this->nodeCommandHandler->handleChangeNodeName($command);

        $this->emitNodeUpdated($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getOtherNodeVariants()
    {
        return array_filter(
            $this->contentGraph->findNodesByNodeAggregateIdentifier($this->context->getContentSubgraph()->getContentStreamIdentifier(), $this->getNodeAggregateIdentifier()),
            function (Node $node) {
                return $node->getNodeIdentifier() !== $this->getNodeIdentifier();
            }
        );
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreationDateTime()
    {
        // TODO!!!
        return new \DateTimeImmutable();
        // FIXME CR rewrite: Read from DTO
        return $this->nodeData->getCreationDateTime();
    }

    /**
     * @return \DateTimeInterface
     */
    public function getLastModificationDateTime()
    {
        // TODO!!!
        return new \DateTimeImmutable();

        // FIXME CR rewrite: Read from DTO
        return $this->nodeData->getLastModificationDateTime();
    }

    /**
     * @param \DateTimeInterface $lastModificationDateTime
     * @return void
     */
    public function setLastPublicationDateTime(\DateTimeInterface $lastModificationDateTime)
    {
        // FIXME CR rewrite: Read from DTO
        $this->nodeData->setLastPublicationDateTime($lastModificationDateTime);
    }

    /**
     * @return \DateTime
     */
    public function getLastPublicationDateTime()
    {
        // TODO!!!
        return new \DateTimeImmutable();

        // FIXME CR rewrite: Read from DTO
        return $this->nodeData->getLastPublicationDateTime();
    }

    /**
     * Returns the path of this node
     *
     * @return string
     * @api
     * @throws Exception
     */
    public function getPath()
    {
        // TODO: is a CTE safe to use?? It's quite efficient, though :)
        return $this->context->getContentSubgraph() ? (string)$this->context->getContentSubgraph()->findNodePath($this->getNodeIdentifier()) : '/' . $this->getName();
    }

    /**
     * Returns the level at which this node is located.
     * Counting starts with 0 for "/", 1 for "/foo", 2 for "/foo/bar" etc.
     *
     * @return integer
     * @api
     * @throws Exception
     */
    public function getDepth()
    {
        // FIXME CR rewrite: Implement more efficient implementation to get path in subgraph (loop with stored routine?)
        $depth = 0;
        $currentNode = $this;
        while ($currentNode = $currentNode->getParent()) {
            $depth += 1;
        }
        return $depth;
    }

    /**
     * Returns the name of this node
     *
     * @return string
     * @api
     * @deprecated
     */
    public function getName(): string
    {
        return (string)$this->getNodeName();
    }

    /**
     * Returns the node label as generated by the configured node label generator
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->getNodeType()->getNodeLabelGenerator()->getLabel($this);
    }

    /**
     * Unimplemented method
     *
     * @param Workspace $workspace
     * @return void
     * @throws Exception
     */
    public function setWorkspace(Workspace $workspace)
    {
        throw new Exception('Method setWorkspace not implemented', 1506096523);
    }

    /**
     * Returns the workspace this node is contained in
     *
     * @return Workspace
     * @api
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }

    /**
     * Returns the identifier of this node
     *
     * Note: (this is the node aggregate identifier in the event sourced CR)
     *
     * @return string
     * @api
     * @deprecated
     */
    public function getIdentifier()
    {
        return (string)$this->getNodeAggregateIdentifier();
    }

    /**
     * @return \Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): Domain\Context\ContentStream\ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return Domain\ValueObject\NodeAggregateIdentifier
     */
    public function getNodeAggregateIdentifier(): Domain\ValueObject\NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    /**
     * Unimplemented method
     *
     * @param integer $index The new index
     * @return void
     * @throws Exception
     */
    public function setIndex($index)
    {
        throw new Exception('Method setIndex not implemented', 1506096523);
    }

    /**
     * Returns the index of this node which determines the order among siblings
     * with the same parent node.
     *
     * @return integer
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Returns the parent node of this node
     *
     * @return NodeInterface|null The parent node or NULL if this is the root node
     * @api
     * @throws Exception
     */
    public function getParent(): ?NodeInterface
    {
        return $this->context->getContentSubgraph() ? $this->context->getContentSubgraph()->findParentNode($this->getNodeIdentifier(), $this->context) : null;
    }

    /**
     * Returns the parent node path
     *
     * @return string Absolute node path of the parent node
     * @api
     * @throws Exception
     */
    public function getParentPath()
    {
        // FIXME CR rewrite: Ask subgraph about parent path
        return $this->getParent()->getPath();
    }

    /**
     * Moves this node before the given node (always used except for moving to the last position)
     *
     * @param NodeInterface $referenceNode
     * @param string $newName
     * @throws Exception
     * @throws RelationDistributionStrategyIsInvalid
     * @api
     */
    public function moveBefore(NodeInterface $referenceNode, $newName = null)
    {
        if ($referenceNode === $this) {
            return;
        }

        $newParentAggregateIdentifier = ($referenceNode->getParent() === $this->getParent() ? null : $referenceNode->getParent()->getNodeAggregateIdentifier());
        $newSucceedingSiblingIdentifier = $referenceNode->getNodeAggregateIdentifier();

        if ($newName !== null) {
            throw new \InvalidArgumentException('Setting new node name while moving not supported', 1505840321);
        }

        if (!$referenceNode instanceof Node) {
            throw new Exception(sprintf('Unexpected NodeInterface implementation: %s', get_class($this)), 1506067144);
        }

        $this->emitBeforeNodeMove($this, $referenceNode, NodeDataRepository::POSITION_BEFORE);

        $this->executeMoveCommand($this->getNodeAggregateIdentifier(), $newParentAggregateIdentifier, $newSucceedingSiblingIdentifier);

        $this->emitAfterNodeMove($this, $referenceNode, NodeDataRepository::POSITION_BEFORE);
        $this->emitNodeUpdated($this);
    }

    /**
     * Moves this node after the given node (only used for moving to the last position)
     *
     * @param NodeInterface $referenceNode
     * @param string $newName
     * @throws Exception
     * @throws RelationDistributionStrategyIsInvalid
     * @api
     */
    public function moveAfter(NodeInterface $referenceNode, $newName = null)
    {
        if ($referenceNode === $this) {
            return;
        }

        $newParentAggregateIdentifier = ($referenceNode->getParent() === $this->getParent() ? null : $referenceNode->getParent()->getNodeAggregateIdentifier());

        if ($newName !== null) {
            throw new \InvalidArgumentException('Setting new node name while moving not supported', 1505809714);
        }

        if (!$referenceNode instanceof Node) {
            throw new Exception(sprintf('Unexpected NodeInterface implementation: %s', get_class($this)), 1506067144);
        }

        $this->emitBeforeNodeMove($this, $referenceNode, NodeDataRepository::POSITION_AFTER);

        $this->executeMoveCommand(
            $this->getNodeAggregateIdentifier(),
            $newParentAggregateIdentifier,
            null
        );

        $this->emitAfterNodeMove($this, $referenceNode, NodeDataRepository::POSITION_AFTER);
        $this->emitNodeUpdated($this);
    }

    /**
     * Moves this node into the given node (at last position)
     *
     * @param NodeInterface $referenceNode
     * @param string $newName
     * @throws Exception
     * @throws RelationDistributionStrategyIsInvalid
     * @api
     */
    public function moveInto(NodeInterface $referenceNode, $newName = null)
    {
        if ($referenceNode === $this || $referenceNode === $this->getParent()) {
            return;
        }

        $newParentAggregateIdentifier = $referenceNode->getNodeAggregateIdentifier();

        if ($newName !== null) {
            throw new \InvalidArgumentException('Setting new node name while moving not supported', 1505809714);
        }

        if (!$referenceNode instanceof Node) {
            throw new Exception(sprintf('Unexpected NodeInterface implementation: %s', get_class($this)), 1506067144);
        }

        $this->emitBeforeNodeMove($this, $referenceNode, NodeDataRepository::POSITION_LAST);

        $this->executeMoveCommand(
            $this->getNodeAggregateIdentifier(),
            $newParentAggregateIdentifier,
            null
        );

        $this->emitAfterNodeMove($this, $referenceNode, NodeDataRepository::POSITION_LAST);
        $this->emitNodeUpdated($this);
    }

    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param NodeAggregateIdentifier|null $newParentAggregateIdentifier
     * @param NodeAggregateIdentifier|null $newSucceedingSiblingAggregateIdentifier
     * @throws Exception
     * @throws RelationDistributionStrategyIsInvalid
     */
    private function executeMoveCommand(NodeAggregateIdentifier $nodeAggregateIdentifier, ?NodeAggregateIdentifier $newParentAggregateIdentifier, ?NodeAggregateIdentifier $newSucceedingSiblingAggregateIdentifier): void
    {
        $moveNode = new Command\MoveNode(
            $this->context->getContentSubgraph()->getContentStreamIdentifier(),
            $this->context->getContentSubgraph()->getDimensionSpacePoint(),
            $nodeAggregateIdentifier,
            $newParentAggregateIdentifier,
            $newSucceedingSiblingAggregateIdentifier,
            $this->nodeType->getRelationDistributionStrategy()
        );

        $this->nodeCommandHandler->handleMoveNode($moveNode);
    }

    /**
     * @Flow\Signal
     * @param NodeInterface $movedNode
     * @param NodeInterface $referenceNode
     * @param integer $movePosition
     * @return void
     */
    protected function emitBeforeNodeMove($movedNode, $referenceNode, $movePosition)
    {
    }

    /**
     * @Flow\Signal
     * @param NodeInterface $movedNode
     * @param NodeInterface $referenceNode
     * @param integer $movePosition
     * @return void
     */
    protected function emitAfterNodeMove($movedNode, $referenceNode, $movePosition)
    {
    }

    /**
     * Copies this node before the given node
     *
     * @param NodeInterface $referenceNode
     * @param string $nodeName
     * @return NodeInterface
     * @throws NodeExistsException
     * @throws NodeConstraintException
     * @api
     * @throws Exception
     */
    public function copyBefore(NodeInterface $referenceNode, $nodeName)
    {
        if ($referenceNode->getParent()->getNode($nodeName) !== null) {
            throw new NodeExistsException('Node with path "' . $referenceNode->getParent()->getPath() . '/' . $nodeName . '" already exists.', 1292503465);
        }

        if (!$referenceNode->getParent()->isNodeTypeAllowedAsChildNode($this->getNodeType())) {
            throw new NodeConstraintException('Cannot copy ' . $this->__toString() . ' before ' . $referenceNode->__toString(), 1402050232);
        }

        $name = new NodeName($nodeName);

        $this->emitBeforeNodeCopy($this, $referenceNode->getParent());

        // TODO CR rewrite: Execute command CopyNodeBefore
        // TODO CR rewrite: Separate commands depending on isAggregate (maybe rename to isDocument)?

        // TODO CR rewrite: Get copied node instance from subgraph and return that
        // $this->emitNodeAdded($copiedNode);
        // $this->emitAfterNodeCopy($copiedNode, $referenceNode->getParent());
        // return $copiedNode;

        return $this;
    }

    /**
     * @Flow\Signal
     * @param NodeInterface $sourceNode
     * @param NodeInterface $targetParentNode
     * @return void
     */
    protected function emitBeforeNodeCopy(NodeInterface $sourceNode, NodeInterface $targetParentNode)
    {
    }

    /**
     * @Flow\Signal
     * @param NodeInterface $copiedNode
     * @param NodeInterface $targetParentNode
     * @return void
     */
    protected function emitAfterNodeCopy(NodeInterface $copiedNode, NodeInterface $targetParentNode)
    {
    }

    /**
     * Copies this node after the given node
     *
     * @param NodeInterface $referenceNode
     * @param string $nodeName
     * @return NodeInterface
     * @throws NodeExistsException
     * @throws NodeConstraintException
     * @api
     * @throws Exception
     */
    public function copyAfter(NodeInterface $referenceNode, $nodeName)
    {
        if ($referenceNode->getParent()->getNode($nodeName) !== null) {
            throw new NodeExistsException('Node with path "' . $referenceNode->getParent()->getPath() . '/' . $nodeName . '" already exists.', 1292503466);
        }

        if (!$referenceNode->getParent()->isNodeTypeAllowedAsChildNode($this->getNodeType())) {
            throw new NodeConstraintException('Cannot copy ' . $this->__toString() . ' after ' . $referenceNode->__toString(), 1404648170);
        }

        $name = new NodeName($nodeName);

        $this->emitBeforeNodeCopy($this, $referenceNode->getParent());

        // TODO CR rewrite: Execute command CopyNodeAfter
        // TODO CR rewrite: Separate commands depending on isAggregate (maybe rename to isDocument)?

        // TODO CR rewrite: Get copied node instance from subgraph and return that
        // $this->emitNodeAdded($copiedNode);
        // $this->emitAfterNodeCopy($copiedNode, $referenceNode->getParent());
        // return $copiedNode;

        return $this;
    }

    /**
     * Copies this node into the given node
     *
     * @param NodeInterface $referenceNode
     * @param string $nodeName
     * @return NodeInterface
     * @api
     */
    public function copyInto(NodeInterface $referenceNode, $nodeName)
    {
        $name = new NodeName($nodeName);

        $this->emitBeforeNodeCopy($this, $referenceNode->getParent());

        // TODO CR rewrite: Execute command CopyNodeInto
        // TODO CR rewrite: Separate commands depending on isAggregate (maybe rename to isDocument)?

        // TODO CR rewrite: Get copied node instance from subgraph and return that, maybe also all recursively copied nodes!!!
        // $this->emitNodeAdded($copiedNode);
        // $this->emitAfterNodeCopy($copiedNode, $referenceNode->getParent());
        // return $copiedNode;

        return $this;
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
     * @api
     * @throws Exception
     */
    public function setProperty($propertyName, $value)
    {
        $propertyType = $this->getNodeType()->getPropertyType($propertyName);
        $oldValue = $this->hasProperty($propertyName) ? $this->getProperty($propertyName) : null;
        $this->emitBeforeNodePropertyChange($this, $propertyName, $oldValue, $value);

        switch ($propertyType) {
            case 'reference':
            case 'references':

                if ($propertyType == 'reference') {
                    $nodes = [$value];
                } else {
                    $nodes = $value;
                }

                $destinationNodeAggregateIdentifiers = array_filter(array_map(
                    function($node) {
                        if ($node instanceof NodeInterface) {
                            return $node->getNodeAggregateIdentifier();
                        }
                    },
                    $nodes
                ));

                $command = new Command\SetNodeReferences(
                    $this->context->getContentSubgraph()->getContentStreamIdentifier(),
                    $this->getNodeIdentifier(),
                    new PropertyName($propertyName),
                    $destinationNodeAggregateIdentifiers
                );
                $this->nodeCommandHandler->handleSetNodeReferences($command);
                break;
            default:
                $command = new Command\SetNodeProperty(
                    $this->context->getContentSubgraph()->getContentStreamIdentifier(),
                    $this->getNodeIdentifier(),
                    $propertyName,
                    new PropertyValue($value, $propertyType)
                );
                $this->nodeCommandHandler->handleSetNodeProperty($command);
        }

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
    public function hasProperty($propertyName)
    {
        return $this->properties->offsetExists($propertyName);
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
     * @api
     */
    public function getProperty($propertyName, $returnNodesAsIdentifiers = false)
    {
        return $this->properties[$propertyName];
    }

    /**
     * Removes the specified property.
     *
     * If the node has a content object attached, the property will not be removed on
     * that object if it exists.
     *
     * @param string $propertyName Name of the property
     * @return void
     */
    public function removeProperty($propertyName)
    {
        if (!$this->hasProperty($propertyName)) {
            return;
        }

        // TODO CR rewrite: Execute command RemoveNodeProperty

        $this->emitNodeUpdated($this);
    }

    /**
     * Returns all properties of this node.
     *
     * If the node has a content object attached, the properties will be fetched
     * there.
     *
     * @return PropertyCollection Property values, indexed by their name
     * @api
     */
    public function getProperties(): NodePropertyCollection
    {
        return $this->properties;
    }

    /**
     * Returns the names of all properties of this node.
     *
     * @return array Property names
     * @api
     */
    public function getPropertyNames()
    {
        return $this->properties->getPropertyNames();
    }

    /**
     * Sets a content object for this node.
     *
     * @param object $contentObject The content object
     * @return void
     * @api
     */
    public function setContentObject($contentObject)
    {
        if ($this->getContentObject() === $contentObject) {
            return;
        }

        // TODO CR rewrite: Execute command SetNodeContentObject

        $this->emitNodeUpdated($this);
    }

    /**
     * Returns the content object of this node (if any).
     *
     * @return object|null
     * @api
     */
    public function getContentObject()
    {
        // TODO CR rewrite: Get content object
        return null;
    }

    /**
     * Unsets the content object of this node.
     *
     * @return void
     * @api
     */
    public function unsetContentObject()
    {
        // TODO CR rewrite: Execute command UnsetNodeContentObject

        $this->emitNodeUpdated($this);
    }

    /**
     * Sets the node type of this node.
     *
     * @param NodeType $nodeType
     * @return void
     * @api
     */
    public function setNodeType(NodeType $nodeType)
    {
        if ($this->getNodeType() === $nodeType) {
            return;
        }

        // TODO CR rewrite: Execute command SetNodeAggregateType

        $this->emitNodeUpdated($this);
    }

    /**
     * Returns the node type of this node.
     *
     * @return NodeType
     * @api
     */
    public function getNodeType()
    {
        // TODO CR rewrite: Lazily init the node type and only store node type name by default?
        return $this->nodeType;
    }

    /**
     * Creates, adds and returns a child node of this node. Also sets default
     * properties and creates default subnodes.
     *
     * @param string $name Name of the new node
     * @param NodeType $nodeType Node type of the new node (optional)
     * @param string $identifier The identifier of the node, unique within the workspace, optional(!) -> node aggregate identifier
     * @return NodeInterface
     * @throws Exception
     * @throws Exception\NodeNotFoundException
     * @api
     */
    public function createNode($name, NodeType $nodeType = null, $identifier = null)
    {
        $nodeAggregateIdentifier = new NodeAggregateIdentifier($identifier);
        $nodeTypeName = new NodeTypeName(($nodeType ? $nodeType->getName() : 'unstructured'));
        $nodeIdentifier = new NodeIdentifier();
        $parentNodeIdentifier = $this->getNodeIdentifier();
        $dimensionSpacePoint = $this->context->getContentSubgraph()->getDimensionSpacePoint();
        $nodeName = new NodeName($name);

        $this->emitBeforeNodeCreate($this, $name, $nodeType, $identifier);

        // If an identifier is given: Check if a node aggregate already exists and then just add another node to the aggregate.
        // (the legacy API allows to specify the same identifier multiple times for different "node variants")
            // TODO Add a contentGraph->hasNodeAggregateInContentStream method (or getNodeAggregateInContentStream)
        // The root node does not have a content stream identifier --> and when adding a node directly next to the root node,
        // it can never exist yet.
        $isRootNode = $this->contentStreamIdentifier === null;
        if (!$isRootNode && $identifier !== null && !empty($this->contentGraph->findNodesByNodeAggregateIdentifier($this->contentStreamIdentifier, $nodeAggregateIdentifier))) {
            $command = new Command\AddNodeToAggregate(
                $this->contentStreamIdentifier,
                $nodeAggregateIdentifier,
                $dimensionSpacePoint,
                $nodeIdentifier,
                $parentNodeIdentifier,
                $nodeName
            );
            $this->nodeCommandHandler->handleAddNodeToAggregate($command);
        } else {
            $command = new Command\CreateNodeAggregateWithNode(
                $this->context->getContentSubgraph()->getContentStreamIdentifier(),
                $nodeAggregateIdentifier,
                $nodeTypeName,
                $dimensionSpacePoint,
                $nodeIdentifier,
                $parentNodeIdentifier,
                $nodeName
            );

            $this->nodeCommandHandler->handleCreateNodeAggregateWithNode($command);
        }

        $newNode = $this->context->getContentSubgraph()->findNodeByIdentifier($nodeIdentifier, $this->context);
        if ($newNode === null) {
            throw new Exception\NodeNotFoundException(sprintf('Node with identifier %s created but not found in supgraph', $nodeIdentifier), 1506097675, $nodeIdentifier);
        }

        // TODO CR rewrite: Do we need to emit signals for all recursively created nodes, too? Then we need to have a "fat" command to know about all created nodes.
        $this->emitNodeAdded($newNode);
        $this->emitAfterNodeCreate($newNode);

        return $newNode;
    }

    /**
     * Creates and persists a node from the given $nodeTemplate as child node
     *
     * @param NodeTemplate $nodeTemplate
     * @param string $nodeName name of the new node. If not specified the name of the nodeTemplate will be used.
     * @return NodeInterface the freshly generated node
     * @api
     */
    public function createNodeFromTemplate(NodeTemplate $nodeTemplate, $nodeName = null)
    {
        // TODO CR rewrite: Execute CreateChildNodeWithVariantFromTemplate command
        // TODO CR rewrite: Check if we still want to support this!

        #$this->emitNodeAdded($node);

        #return $node;
    }

    /**
     * Returns a node specified by the given relative path.
     *
     * @param string $path Path specifying the node, relative to this node
     * @return NodeInterface|null The specified node or NULL if no such node exists
     * @api
     * @throws Exception
     */
    public function getNode($path)
    {
        $path = new Domain\ValueObject\NodePath($path);
        $node = $this;
        if ($path->isAbsolute()) {
            $node = $this->context->getRootNode();
        }

        foreach ($path->getParts() as $nodeName) {
            // TODO: replace by CTE and make performant
            $node = $this->context->getContentSubgraph()->findChildNodeConnectedThroughEdgeName($node->getNodeIdentifier(), $nodeName, $this->context);
            if (!$node) {
                return null;
            }
        }

        return $node;
    }

    /**
     * Returns the primary child node of this node.
     *
     * Which node acts as a primary child node will in the future depend on the
     * node type. For now it is just the first child node.
     *
     * @return NodeInterface|null The primary child node or NULL if no such node exists
     * @api
     * @throws Exception
     */
    public function getPrimaryChildNode()
    {
        return $this->context->getContentSubgraph()->findFirstChildNode($this->getNodeIdentifier(), $this->context);
    }

    /**
     * Returns all direct child nodes of this node.
     * If a node type is specified, only nodes of that type are returned.
     *
     * @param string $nodeTypeFilter If specified, only nodes with that node type are considered
     * @param integer $limit An optional limit for the number of nodes to find. Added or removed nodes can still change the number nodes!
     * @param integer $offset An optional offset for the query
     * @return array<\Neos\ContentRepository\Domain\Model\NodeInterface> An array of nodes or an empty array if no child nodes matched
     * @api
     * @throws Exception
     */
    public function getChildNodes($nodeTypeFilter = null, $limit = null, $offset = null)
    {
        $nodeTypeConstraints = ($nodeTypeFilter ? $this->nodeTypeConstraintService->unserializeFilters($nodeTypeFilter) : null);
        return $this->context->getContentSubgraph()->findChildNodes($this->getNodeIdentifier(), $nodeTypeConstraints, $limit, $offset, $this->context);
    }

    /**
     * Returns the number of child nodes a similar getChildNodes() call would return.
     *
     * @param string $nodeTypeFilter If specified, only nodes with that node type are considered
     * @return integer The number of child nodes
     * @api
     * @throws Exception
     */
    public function getNumberOfChildNodes($nodeTypeFilter = null)
    {
        $nodeTypeConstraints = ($nodeTypeFilter ? $this->nodeTypeConstraintService->unserializeFilters($nodeTypeFilter) : null);

        return $this->context->getContentSubgraph()->countChildNodes($this->getNodeIdentifier(), $nodeTypeConstraints);
    }

    /**
     * Checks if this node has any child nodes.
     *
     * @param string $nodeTypeFilter If specified, only nodes with that node type are considered
     * @return boolean TRUE if this node has child nodes, otherwise FALSE
     * @api
     * @throws Exception
     */
    public function hasChildNodes($nodeTypeFilter = null)
    {
        return ($this->getNumberOfChildNodes($nodeTypeFilter) > 0);
    }

    /**
     * Removes this node and all its child nodes. This is an alias for setRemoved(TRUE)
     *
     * @return void
     * @api
     */
    public function remove()
    {
        $this->setRemoved(true);
    }

    /**
     * Enables using the remove method when only setters are available
     *
     * @param boolean $removed If TRUE, this node and it's child nodes will be removed. If it is FALSE only this node will be restored.
     * @return void
     * @api
     */
    public function setRemoved($removed)
    {
        // TODO CR rewrite: Execute command MarkAsRemoved / UnmarkAsRemoved

        if ($removed === true) {
            $this->emitNodeRemoved($this);
        } else {
            $this->emitNodeUpdated($this);
        }

        // TODO CR rewrite: Also recursively removed nodes need to be signaled for BC
    }

    /**
     * If this node is a removed node.
     *
     * @return boolean
     * @api
     */
    public function isRemoved()
    {
        // TODO!!!!
        return false;
        // TODO CR rewrite: Get removed flag
        return $this->nodeData->isRemoved();
    }

    /**
     * Sets the "hidden" flag for this node.
     *
     * @param boolean $hidden If TRUE, this Node will be hidden
     * @return void
     * @api
     */
    public function setHidden($hidden)
    {
        if ($this->isHidden() === $hidden) {
            return;
        }

        // TODO CR rewrite: Execute command HideNode / UnhideNode

        $this->emitNodeUpdated($this);
    }

    /**
     * Returns the current state of the hidden flag
     *
     * @return boolean
     * @api
     */
    public function isHidden()
    {
        return $this->hidden;
    }

    /**
     * Sets the date and time when this node becomes potentially visible.
     *
     * @param \DateTime $dateTime Date before this node should be hidden
     * @return void
     * @api
     */
    public function setHiddenBeforeDateTime(\DateTime $dateTime = null)
    {
        if ($this->getHiddenBeforeDateTime() instanceof \DateTime && $dateTime instanceof \DateTime && $this->getHiddenBeforeDateTime()->format(\DateTime::W3C) === $dateTime->format(\DateTime::W3C)) {
            return;
        }

        // TODO CR rewrite: Execute command SetNodeHiddenBeforeDateTime

        $this->emitNodeUpdated($this);
    }

    /**
     * Returns the date and time before which this node will be automatically hidden.
     *
     * @return \DateTime Date before this node will be hidden
     * @api
     */
    public function getHiddenBeforeDateTime()
    {
        // TODO CR rewrite: Get hiddenBeforeDateTime
        return null;
        return $this->nodeData->getHiddenBeforeDateTime();
    }

    /**
     * Sets the date and time when this node should be automatically hidden
     *
     * @param \DateTime $dateTime Date after which this node should be hidden
     * @return void
     * @api
     */
    public function setHiddenAfterDateTime(\DateTime $dateTime = null)
    {
        if ($this->getHiddenAfterDateTime() instanceof \DateTimeInterface && $dateTime instanceof \DateTimeInterface && $this->getHiddenAfterDateTime()->format(\DateTime::W3C) === $dateTime->format(\DateTime::W3C)) {
            return;
        }

        // TODO CR rewrite: Execute command SetNodeHiddenAfterDateTime

        $this->emitNodeUpdated($this);
    }

    /**
     * Returns the date and time after which this node will be automatically hidden.
     *
     * @return \DateTime Date after which this node will be hidden
     * @api
     */
    public function getHiddenAfterDateTime()
    {
        return null;
        // TODO CR rewrite: Get hiddenAfterDateTime
        return $this->nodeData->getHiddenAfterDateTime();
    }

    /**
     * Sets if this node should be hidden in indexes, such as a site navigation.
     *
     * @param boolean $hidden TRUE if it should be hidden, otherwise FALSE
     * @return void
     * @api
     */
    public function setHiddenInIndex($hidden)
    {
        // TODO CR rewrite: Execute command SetNodeHiddenInIndex

        $this->emitNodeUpdated($this);
    }

    /**
     * If this node should be hidden in indexes
     *
     * @return boolean
     * @api
     */
    public function isHiddenInIndex()
    {
        return $this->properties['_hiddenInIndex'] ?? false;
    }

    /**
     * Sets the roles which are required to access this node
     *
     * @param array $accessRoles
     * @return void
     * @api
     */
    public function setAccessRoles(array $accessRoles)
    {
        if ($this->getAccessRoles() === $accessRoles) {
            return;
        }

        // TODO CR rewrite: Execute command SetNodeAccessRoles

        $this->emitNodeUpdated($this);
    }

    /**
     * Returns the names of defined access roles
     *
     * @return array
     * @api
     */
    public function getAccessRoles()
    {
        return $this->nodeData->getAccessRoles();
    }

    /**
     * Tells if a node, in general,  has access restrictions, independent of the
     * current security context.
     *
     * @return boolean
     * @api
     */
    public function hasAccessRestrictions()
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
     * @api
     */
    public function isVisible()
    {
        if ($this->isHidden()) {
            return false;
        }
        $currentDateTime = $this->context ? $this->context->getCurrentDateTime() : $this->now;
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
     * @api
     */
    public function isAccessible()
    {
        // TODO: fix with CR rewrite
        return true;
        return $this->nodeData->isAccessible();
    }

    /**
     * Returns the context this node operates in.
     *
     * @return Context
     * @api
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return NodeData
     */
    public function getNodeData()
    {
        return $this->nodeData;
    }

    /**
     * Returns a string which distinctly identifies this object and thus can be used as an identifier for cache entries
     * related to this object.
     *
     * @return string
     * @throws Exception
     */
    public function getCacheEntryIdentifier()
    {
        return $this->getContextPath();
    }

    /**
     * Return the assigned content dimensions of the node.
     *
     * @return array
     */
    public function getDimensions()
    {
        return $this->dimensionSpacePoint->toLegacyDimensionArray();
    }

    /**
     * For debugging purposes, the node can be converted to a string.
     *
     * @return string
     * @throws Exception
     */
    public function __toString()
    {
        return 'Node ' . $this->getContextPath() . '[' . $this->getNodeType()->getName() . ']';
    }

    /**
     * Given a context a new node is returned that is like this node, but
     * lives in the new context.
     *
     * @param Context $context
     * @return NodeInterface
     * @throws Exception
     * @throws NodeException
     */
    public function createVariantForContext($context)
    {
        // TODO CR rewrite: Check if we need to specialize, generalize or translate!!!

        $destinationNodeIdentifier = new NodeIdentifier();
        $this->nodeCommandHandler->handleTranslateNodeInAggregate(new Command\TranslateNodeInAggregate(
            $this->contentStreamIdentifier,
            $this->getNodeIdentifier(),
            $destinationNodeIdentifier,
            new DimensionSpacePoint($context->getTargetDimensions())
        ));

        $node = $context->getContentSubgraph()->findNodeByIdentifier($destinationNodeIdentifier, $context);
        $this->emitNodeAdded($node);

        return $node;
    }

    /**
     * Checks if the given $nodeType would be allowed as a child node of this node according to the configured constraints.
     *
     * @param NodeType $nodeType
     * @return boolean TRUE if the passed $nodeType is allowed as child node
     * @throws Exception
     */
    public function isNodeTypeAllowedAsChildNode(NodeType $nodeType)
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
     * @return boolean TRUE if this node is auto-created by the parent.
     * @throws Exception
     */
    public function isAutoCreated()
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
     * @return bool
     * @throws Exception
     */
    public function dimensionsAreMatchingTargetDimensionValues(): bool
    {
        return $this->dimensionSpacePoint->getHash() === $this->context->getContentSubgraph()->getDimensionSpacePoint()->getHash();
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
     * Signals that the node path has been changed.
     *
     * @Flow\Signal
     * @param NodeInterface $node
     * @param string $oldPath
     * @param string $newPath
     * @param boolean $recursion TRUE if the node path change was caused because a parent node path was changed
     */
    protected function emitNodePathChanged(NodeInterface $node, $oldPath, $newPath, $recursion)
    {
    }

    public function getNodeIdentifier(): NodeIdentifier
    {
        return $this->nodeIdentifier;
    }

    public function getNodeTypeName(): NodeTypeName
    {
        return $this->nodeTypeName;
    }

    public function getNodeName(): NodeName
    {
        return $this->nodeName;
    }

    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }
}
