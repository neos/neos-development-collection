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

use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\ValueObject\PropertyCollectionInterface;
use Neos\ContentRepository\Exception\NodeException;
use Neos\ContentRepository\Exception\NodeExistsException;

/**
 * Interface for a Node
 *
 * @api
 */
interface NodeInterface
{
    /**
     * Regex pattern which matches a node path without any context information
     */
    const MATCH_PATTERN_PATH = '/^(\/|(?:\/[a-z0-9\-]+)+)$/';

    /**
     * Regex pattern which matches a "context path", ie. a node path possibly containing context information such as the
     * workspace name. This pattern is used at least in the route part handler.
     */
    const MATCH_PATTERN_CONTEXTPATH = '/^   # A Context Path consists of...
		(?>(?P<NodePath>                       # 1) a NODE PATH
			(?>
			\/ [a-z0-9\-]+ |                # Which either starts with a slash followed by a node name
			\/ |                            # OR just a slash (the root node)
			[a-z0-9\-]+                     # OR only a node name (if it is a relative path)
			)
			(?:                             #    and (optionally) more path-parts)
				\/
				[a-z0-9\-]+
			)*
		))
		(?:                                 # 2) a CONTEXT
			@                               #    which is delimited from the node path by the "@" sign
			(?>(?P<WorkspaceName>              #    followed by the workspace name (NON-EMPTY)
				[a-z0-9\-]+
			))
			(?:                             #    OPTIONALLY followed by dimension values
				;                           #    ... which always start with ";"
				(?P<Dimensions>
					(?>                     #        A Dimension Value is a key=value structure
						[a-zA-Z_]+
						=
						[^=&]+
					)
					(?>&(?-1))?             #        ... delimited by &
				)){0,1}
		){0,1}$/ix';

    /**
     * Regex pattern which matches a Node Name (ie. segment of a node path)
     */
    const MATCH_PATTERN_NAME = '/^[a-z0-9\-]+$/';

    /**
     * Set the name of the node to $newName, keeping it's position as it is
     *
     * @param string $newName
     * @return void
     * @throws \InvalidArgumentException if $newName is invalid
     * @api
     */
    public function setName(string $newName): void;

    /**
     * Returns the name of this node
     *
     * @return string
     * @api
     */
    public function getName(): string;

    /**
     * Returns a full length plain text label of this node
     *
     * @return string
     * @api
     */
    public function getLabel(): string;

    /**
     * Sets the specified property.
     *
     * If the node has a content object attached, the property will be set there
     * if it is settable.
     *
     * @param string $propertyName Name of the property
     * @param mixed $value Value of the property
     * @return void
     * @api
     */
    public function setProperty(string $propertyName, $value): void;

    /**
     * If this node has a property with the given name.
     *
     * If the node has a content object attached, the property will be checked
     * there.
     *
     * @param string $propertyName Name of the property to test for
     * @return boolean
     * @api
     */
    public function hasProperty(string $propertyName): bool;

    /**
     * Returns the specified property.
     *
     * If the node has a content object attached, the property will be fetched
     * there if it is gettable.
     *
     * @param string $propertyName Name of the property
     * @return mixed value of the property
     * @throws NodeException if the node does not contain the specified property
     * @api
     */
    public function getProperty(string $propertyName);

    /**
     * Removes the specified property.
     *
     * If the node has a content object attached, the property will not be removed on
     * that object if it exists.
     *
     * @param string $propertyName Name of the property
     * @return void
     * @throws NodeException if the node does not contain the specified property
     * @api
     */
    public function removeProperty(string $propertyName): void;

    /**
     * Returns all properties of this node.
     *
     * If the node has a content object attached, the properties will be fetched
     * there.
     *
     * @return PropertyCollectionInterface Property values
     * @api
     */
    public function getProperties(): PropertyCollectionInterface;

    /**
     * Returns the names of all properties of this node.
     *
     * @return string[] Property names
     * @api
     */
    public function getPropertyNames(): array;

    /**
     * Sets a content object for this node.
     *
     * @param object $contentObject The content object
     * @return void
     * @throws \InvalidArgumentException if the given contentObject is no object.
     * @api
     */
    public function setContentObject($contentObject): void;

    /**
     * Returns the content object of this node (if any).
     *
     * @return object The content object or NULL if none was set
     * @api
     */
    public function getContentObject();

    /**
     * Unsets the content object of this node.
     *
     * @return void
     * @api
     */
    public function unsetContentObject(): void;

    /**
     * Sets the node type of this node.
     *
     * @param NodeType $nodeType
     * @return void
     * @api
     */
    public function setNodeType(NodeType $nodeType): void;

    /**
     * Returns the node type of this node.
     *
     * @return NodeType
     * @api
     */
    public function getNodeType(): NodeType;

    /**
     * Sets the "hidden" flag for this node.
     *
     * @param boolean $hidden If true, this Node will be hidden
     * @return void
     * @api
     */
    public function setHidden(bool $hidden): void;

    /**
     * Returns the current state of the hidden flag
     *
     * @return boolean
     * @api
     */
    public function isHidden(): bool;

    /**
     * Sets the date and time when this node becomes potentially visible.
     *
     * @param \DateTimeInterface $dateTime Date before this node should be hidden
     * @return void
     * @api
     */
    public function setHiddenBeforeDateTime(\DateTimeInterface $dateTime = null): void;

    /**
     * Returns the date and time before which this node will be automatically hidden.
     *
     * @return \DateTimeInterface|null Date before this node will be hidden - or null if no hidden before date is set
     * @api
     */
    public function getHiddenBeforeDateTime(): ?\DateTimeInterface;

    /**
     * Sets the date and time when this node should be automatically hidden
     *
     * @param \DateTimeInterface $dateTime Date after which this node should be hidden
     * @return void
     * @api
     */
    public function setHiddenAfterDateTime(\DateTimeInterface $dateTime = null): void;

    /**
     * Returns the date and time after which this node will be automatically hidden.
     *
     * @return \DateTimeInterface|null Date after which this node will be hidden - or null if no hidden after date is set
     * @api
     */
    public function getHiddenAfterDateTime(): ?\DateTimeInterface;

    /**
     * Sets if this node should be hidden in indexes, such as a site navigation.
     *
     * @param boolean $hidden true if it should be hidden, otherwise false
     * @return void
     * @api
     */
    public function setHiddenInIndex(bool $hidden): void;

    /**
     * If this node should be hidden in indexes
     *
     * @return boolean
     * @api
     */
    public function isHiddenInIndex(): bool;

    /**
     * Sets the roles which are required to access this node
     *
     * @param array $accessRoles
     * @return void
     * @api
     */
    public function setAccessRoles(array $accessRoles): void;

    /**
     * Returns the names of defined access roles
     *
     * @return array
     * @api
     */
    public function getAccessRoles(): array;

    /**
     * Returns the path of this node
     *
     * Example: /sites/mysitecom/homepage/about
     *
     * @return string The absolute node path
     * @api
     */
    public function getPath(): string;

    /**
     * Returns the absolute path of this node with additional context information (such as the workspace name).
     *
     * Example: /sites/mysitecom/homepage/about@user-admin
     *
     * @return string Node path with context information
     * @api
     */
    public function getContextPath(): string;

    /**
     * Returns the level at which this node is located.
     * Counting starts with 0 for "/", 1 for "/foo", 2 for "/foo/bar" etc.
     *
     * @return integer
     * @api
     */
    public function getDepth(): int;

    /**
     * Sets the workspace of this node.
     *
     * This method is only for internal use by the content repository. Changing
     * the workspace of a node manually may lead to unexpected behavior.
     *
     * @param Workspace $workspace
     * @return void
     */
    public function setWorkspace(Workspace $workspace): void;

    /**
     * Returns the workspace this node is contained in
     *
     * @return Workspace
     * @api
     */
    public function getWorkspace(): Workspace;

    /**
     * Returns the identifier of this node.
     *
     * This UUID is not the same as the technical persistence identifier used by
     * Flow's persistence framework. It is an additional identifier which is unique
     * within the same workspace and is used for tracking the same node in across
     * workspaces.
     *
     * It is okay and recommended to use this identifier for synchronisation purposes
     * as it does not change even if all of the nodes content or its path changes.
     *
     * @return string the node's UUID
     * @api
     */
    public function getIdentifier(): string;

    /**
     * Sets the index of this node
     *
     * This method is for internal use and must only be used by other nodes!
     *
     * @param integer $index The new index
     * @return void
     */
    public function setIndex(int $index): void;

    /**
     * Returns the index of this node which determines the order among siblings
     * with the same parent node.
     *
     * @return integer
     */
    public function getIndex(): int;

    /**
     * Returns the parent node of this node
     *
     * @return NodeInterface The parent node
     * @api
     * @throws NodeException if no node has no parent (= is root node)
     */
    public function getParent(): NodeInterface;

    /**
     * Returns the parent node path
     *
     * @return string Absolute node path of the parent node
     * @api
     */
    public function getParentPath(): string;

    /**
     * Creates, adds and returns a child node of this node. Also sets default
     * properties and creates default subnodes.
     *
     * @param string $name Name of the new node
     * @param NodeType $nodeType Node type of the new node (optional)
     * @param string $identifier The identifier of the node, unique within the workspace, optional(!)
     * @return NodeInterface
     * @throws \InvalidArgumentException if the node name is not accepted.
     * @throws NodeExistsException if a node with this path already exists.
     * @api
     */
    public function createNode(string $name, NodeType $nodeType = null, string $identifier = null): NodeInterface;

    /**
     * Creates, adds and returns a child node of this node, without setting default
     * properties or creating subnodes.
     *
     * For internal use only!
     *
     * @param string $name Name of the new node
     * @param NodeType $nodeType Node type of the new node (optional)
     * @param string $identifier The identifier of the node, unique within the workspace, optional(!)
     * @return NodeInterface
     * @throws \InvalidArgumentException if the node name is not accepted.
     * @throws NodeExistsException if a node with this path already exists.
     */
    public function createSingleNode(string $name, NodeType $nodeType = null, string $identifier = null): NodeInterface;

    /**
     * Creates and persists a node from the given $nodeTemplate as child node
     *
     * @param \Neos\ContentRepository\Domain\Model\NodeTemplate $nodeTemplate
     * @param string $nodeName name of the new node. If not specified the name of the nodeTemplate will be used.
     * @return NodeInterface the freshly generated node
     * @api
     */
    public function createNodeFromTemplate(NodeTemplate $nodeTemplate, string $nodeName = null): NodeInterface;

    /**
     * Returns a node specified by the given relative path.
     *
     * @param string $path Path specifying the node, relative to this node
     * @return NodeInterface The specified node or NULL if no such node exists
     * @api
     * @throws NodeException if no node with the given $path was found
     */
    public function getNode(string $path): NodeInterface;

    /**
     * Returns the primary child node of this node.
     *
     * Which node acts as a primary child node will in the future depend on the
     * node type. For now it is just the first child node.
     *
     * @return NodeInterface The primary child node
     * @api
     * @throws NodeException if no primary child node exists
     */
    public function getPrimaryChildNode(): NodeInterface;

    /**
     * Returns all direct child nodes of this node.
     * If a node type is specified, only nodes of that type are returned.
     *
     * @param string $nodeTypeFilter If specified, only nodes with that node type are considered
     * @param integer $limit An optional limit for the number of nodes to find. Added or removed nodes can still change the number nodes!
     * @param integer $offset An optional offset for the query
     * @return array<\Neos\ContentRepository\Domain\Model\NodeInterface> An array of nodes or an empty array if no child nodes matched
     * @api
     */
    public function getChildNodes(string $nodeTypeFilter = null, int $limit = null, int $offset = null): array;

    /**
     * Checks if this node has any child nodes.
     *
     * @param string $nodeTypeFilter If specified, only nodes with that node type are considered
     * @return boolean true if this node has child nodes, otherwise false
     * @api
     */
    public function hasChildNodes(string $nodeTypeFilter = null): bool;

    /**
     * Removes this node and all its child nodes. This is an alias for setRemoved(true)
     *
     * @return void
     * @api
     */
    public function remove(): void;

    /**
     * Removes this node and all its child nodes or sets ONLY this node to not being removed.
     *
     * @param boolean $removed If true, this node and it's child nodes will be removed or set to be not removed.
     * @return void
     * @api
     */
    public function setRemoved(bool $removed): void;

    /**
     * If this node is a removed node.
     *
     * @return boolean
     * @api
     */
    public function isRemoved(): bool;

    /**
     * Tells if this node is "visible".
     * For this the "hidden" flag and the "hiddenBeforeDateTime" and "hiddenAfterDateTime" dates are
     * taken into account.
     *
     * @return boolean
     * @api
     */
    public function isVisible(): bool;

    /**
     * Tells if this node may be accessed according to the current security context.
     *
     * @return boolean
     * @api
     */
    public function isAccessible(): bool;

    /**
     * Tells if a node, in general,  has access restrictions, independent of the
     * current security context.
     *
     * @return boolean
     * @api
     */
    public function hasAccessRestrictions(): bool;

    /**
     * Checks if the given $nodeType would be allowed as a child node of this node according to the configured constraints.
     *
     * @param NodeType $nodeType
     * @return boolean true if the passed $nodeType is allowed as child node
     */
    public function isNodeTypeAllowedAsChildNode(NodeType $nodeType): bool;

    /**
     * Moves this node before the given node
     *
     * @param NodeInterface $referenceNode
     * @return void
     * @api
     */
    public function moveBefore(NodeInterface $referenceNode): void;

    /**
     * Moves this node after the given node
     *
     * @param NodeInterface $referenceNode
     * @return void
     * @api
     */
    public function moveAfter(NodeInterface $referenceNode): void;

    /**
     * Moves this node into the given node
     *
     * @param NodeInterface $referenceNode
     * @return void
     * @api
     */
    public function moveInto(NodeInterface $referenceNode): void;

    /**
     * Copies this node before the given node
     *
     * @param NodeInterface $referenceNode
     * @param string $nodeName
     * @return NodeInterface The copied node
     * @throws NodeExistsException
     * @api
     */
    public function copyBefore(NodeInterface $referenceNode, string $nodeName): NodeInterface;

    /**
     * Copies this node after the given node
     *
     * @param NodeInterface $referenceNode
     * @param string $nodeName
     * @return NodeInterface The copied node
     * @throws NodeExistsException
     * @api
     */
    public function copyAfter(NodeInterface $referenceNode, string $nodeName): NodeInterface;

    /**
     * Copies this node to below the given node. The new node will be added behind
     * any existing sub nodes of the given node.
     *
     * @param NodeInterface $referenceNode
     * @param string $nodeName
     * @return NodeInterface The copied node
     * @throws NodeExistsException
     * @api
     */
    public function copyInto(NodeInterface $referenceNode, string $nodeName): NodeInterface;

    /**
     * Return the NodeData representation of the node.
     *
     * @return NodeData
     */
    public function getNodeData(): NodeData;

    /**
     * Return the context of the node
     *
     * @return Context
     */
    public function getContext(): Context;

    /**
     * Return the assigned content dimensions of the node.
     *
     * @return array An array of dimensions to array of dimension values
     */
    public function getDimensions(): array;

    /**
     * Given a context a new node is returned that is like this node, but
     * lives in the new context.
     *
     * @param Context $context
     * @return NodeInterface
     */
    public function createVariantForContext(Context $context): NodeInterface;

    /**
     * Determine if this node is configured as auto-created childNode of the parent node. If that is the case, it
     * should not be deleted.
     *
     * @return boolean true if this node is auto-created by the parent.
     */
    public function isAutoCreated(): bool;

    /**
     * Get other variants of this node (with different dimension values)
     *
     * A variant of a node can have different dimension values and path (for non-aggregate nodes).
     * The resulting node instances might belong to a different context.
     *
     * @return array<NodeInterface> All node variants of this node (excluding the current node)
     */
    public function getOtherNodeVariants(): array;
}
