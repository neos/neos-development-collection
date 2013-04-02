<?php
namespace TYPO3\TYPO3CR\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Interface for a Node which is be persisted in the Node Repository
 *
 * @api
 */
interface PersistentNodeInterface extends NodeInterface {

	/**
	 * Regex pattern which matches a node path without any context information
	 */
	const MATCH_PATTERN_PATH = '/^(\/|(?:\/[a-z0-9\-]+)+)$/i';

	/**
	 * Regex pattern which matches a "context path", ie. a node path possibly containing context information such as the
	 * workspace name. This pattern is used at least in the route part handler.
	 */
	const MATCH_PATTERN_CONTEXTPATH = '/^(?P<NodePath>(?:\/?[a-z0-9\-]+)(?:\/[a-z0-9\-]+)*)?(?:@(?P<WorkspaceName>[a-z0-9\-]+))?$/i';

	/**
	 * Sets the absolute path of this node
	 *
	 * This method is only for internal use by the content repository. Changing
	 * the path of a node manually may lead to unexpected behavior and bad breath.
	 *
	 * @param string $path
	 * @param boolean $recursive
	 * @return void
	 */
	public function setPath($path, $recursive = TRUE);

	/**
	 * Returns the path of this node
	 *
	 * Example: /sites/mysitecom/homepage/about
	 *
	 * @return string The absolute node path
	 * @api
	 */
	public function getPath();

	/**
	 * Returns the absolute path of this node with additional context information (such as the workspace name).
	 *
	 * Example: /sites/mysitecom/homepage/about@user-admin
	 *
	 * @return string Node path with context information
	 * @api
	 */
	public function getContextPath();

	/**
	 * Returns the level at which this node is located.
	 * Counting starts with 0 for "/", 1 for "/foo", 2 for "/foo/bar" etc.
	 *
	 * @return integer
	 * @api
	 */
	public function getDepth();

	/**
	 * Sets the workspace of this node.
	 *
	 * This method is only for internal use by the content repository. Changing
	 * the workspace of a node manually may lead to unexpected behavior.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace
	 * @return void
	 */
	public function setWorkspace(Workspace $workspace);

	/**
	 * Returns the workspace this node is contained in
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\Workspace
	 * @api
	 */
	public function getWorkspace();

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
	public function getIdentifier();

	/**
	 * Sets the index of this node
	 *
	 * This method is for internal use and must only be used by other nodes!
	 *
	 * @param integer $index The new index
	 * @return void
	 */
	public function setIndex($index);

	/**
	 * Returns the index of this node which determines the order among siblings
	 * with the same parent node.
	 *
	 * @return integer
	 */
	public function getIndex();

	/**
	 * Returns the parent node of this node
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface The parent node or NULL if this is the root node
	 * @api
	 */
	public function getParent();

	/**
	 * Returns the parent node path
	 *
	 * @return string Absolute node path of the parent node
	 * @api
	 */
	public function getParentPath();

	/**
	 * Moves this node before the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @return void
	 * @api
	 */
	public function moveBefore(PersistentNodeInterface $referenceNode);

	/**
	 * Moves this node after the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @return void
	 * @api
	 */
	public function moveAfter(PersistentNodeInterface $referenceNode);

	/**
	 * Moves this node into the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @return void
	 * @api
	 */
	public function moveInto(PersistentNodeInterface $referenceNode);

	/**
	 * Copies this node before the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @param string $nodeName
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface
	 * @throws \TYPO3\TYPO3CR\Exception\NodeExistsException
	 * @api
	 */
	public function copyBefore(PersistentNodeInterface $referenceNode, $nodeName);

	/**
	 * Copies this node after the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @param string $nodeName
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface
	 * @throws \TYPO3\TYPO3CR\Exception\NodeExistsException
	 * @api
	 */
	public function copyAfter(PersistentNodeInterface $referenceNode, $nodeName);

	/**
	 * Copies this node to below the given node. The new node will be added behind
	 * any existing sub nodes of the given node.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @param string $nodeName
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface
	 * @throws \TYPO3\TYPO3CR\Exception\NodeExistsException
	 * @api
	 */
	public function copyInto(PersistentNodeInterface $referenceNode, $nodeName);

	/**
	 * Creates, adds and returns a child node of this node. Also sets default
	 * properties and creates default subnodes.
	 *
	 * @param string $name Name of the new node
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeType $nodeType Node type of the new node (optional)
	 * @param string $identifier The identifier of the node, unique within the workspace, optional(!)
	 * @return \TYPO3\TYPO3CR\Domain\Model\Node
	 * @throws \InvalidArgumentException if the node name is not accepted.
	 * @throws \TYPO3\TYPO3CR\Exception\NodeExistsException if a node with this path already exists.
	 * @api
	 */
	public function createNode($name, NodeType $nodeType = NULL, $identifier = NULL);

	/**
	 * Creates, adds and returns a child node of this node, without setting default
	 * properties or creating subnodes.
	 *
	 * For internal use only!
	 *
	 * @param string $name Name of the new node
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeType $nodeType Node type of the new node (optional)
	 * @param string $identifier The identifier of the node, unique within the workspace, optional(!)
	 * @return \TYPO3\TYPO3CR\Domain\Model\Node
	 * @throws \InvalidArgumentException if the node name is not accepted.
	 * @throws \TYPO3\TYPO3CR\Exception\NodeExistsException if a node with this path already exists.
	 */
	public function createSingleNode($name, NodeType $nodeType = NULL, $identifier = NULL);

	/**
	 * Creates and persists a node from the given $nodeTemplate as child node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeTemplate $nodeTemplate
	 * @param string $nodeName name of the new node. If not specified the name of the nodeTemplate will be used.
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface the freshly generated node
	 * @api
	 */
	public function createNodeFromTemplate(NodeTemplate $nodeTemplate, $nodeName = NULL);

	/**
	 * Returns a node specified by the given relative path.
	 *
	 * @param string $path Path specifying the node, relative to this node
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface The specified node or NULL if no such node exists
	 * @api
	 */
	public function getNode($path);

	/**
	 * Returns the primary child node of this node.
	 *
	 * Which node acts as a primary child node will in the future depend on the
	 * node type. For now it is just the first child node.
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface The primary child node or NULL if no such node exists
	 * @api
	 */
	public function getPrimaryChildNode();

	/**
	 * Returns all direct child nodes of this node.
	 * If a node type is specified, only nodes of that type are returned.
	 *
	 * @param string $nodeTypeFilter If specified, only nodes with that node type are considered
	 * @param integer $limit An optional limit for the number of nodes to find. Added or removed nodes can still change the number nodes!
	 * @param integer $offset An optional offset for the query
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface> An array of nodes or an empty array if no child nodes matched
	 * @api
	 */
	public function getChildNodes($nodeTypeFilter = NULL, $limit = NULL, $offset = NULL);

	/**
	 * Checks if this node has any child nodes.
	 *
	 * @param string $nodeTypeFilter If specified, only nodes with that node type are considered
	 * @return boolean TRUE if this node has child nodes, otherwise FALSE
	 * @api
	 */
	public function hasChildNodes($nodeTypeFilter = NULL);

	/**
	 * Removes this node and all its child nodes.
	 *
	 * @return void
	 * @api
	 */
	public function remove();

	/**
	 * Enables using the remove method when only setters are available.
	 *
	 * @param boolean $removed If TRUE, this node and it's child nodes will be removed. Cannot handle FALSE (yet).
	 * @return void
	 * @api
	 */
	public function setRemoved($removed);

	/**
	 * If this node is a removed node.
	 *
	 * @return boolean
	 * @api
	 */
	public function isRemoved();

	/**
	 * Tells if this node is "visible".
	 * For this the "hidden" flag and the "hiddenBeforeDateTime" and "hiddenAfterDateTime" dates are
	 * taken into account.
	 *
	 * @return boolean
	 * @api
	 */
	public function isVisible();

	/**
	 * Tells if this node may be accessed according to the current security context.
	 *
	 * @return boolean
	 * @api
	 */
	public function isAccessible();

	/**
	 * Tells if a node, in general,  has access restrictions, independent of the
	 * current security context.
	 *
	 * @return boolean
	 * @api
	 */
	public function hasAccessRestrictions();

	/**
	 * Returns the current context this node operates in.
	 *
	 * This is for internal use only.
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	public function getContext();

}
?>
