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
 * Interface for a Node inside the Content Repository (persisted).
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
	 * Returns the level at which this node is located.
	 * Counting starts with 0 for "/", 1 for "/foo", 2 for "/foo/bar" etc.
	 *
	 * @return integer
	 */
	public function getDepth();

	/**
	 * Returns the path of this node
	 *
	 * @return string
	 */
	public function getPath();

	/**
	 * Returns the path of this node with additional context information (such as the workspace name)
	 *
	 * @return string Node path with context information
	 */
	public function getContextPath();

	/**
	 * Sets the absolute path of this node.
	 *
	 * This method is only for internal use by the content repository. Changing
	 * the path of a node manually may lead to unexpected behavior.
	 *
	 * @param string $path
	 * @param boolean $recursive
	 * @return void
	 */
	public function setPath($path, $recursive = TRUE);

	/**
	 * Sets the workspace of this node.
	 * This method is only for internal use by the content repository. Changing
	 * the workspace of a node manually may lead to unexpected behavior.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace
	 * @return void
	 */
	public function setWorkspace(\TYPO3\TYPO3CR\Domain\Model\Workspace $workspace);

	/**
	 * Returns the workspace this node is contained in
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\Workspace
	 */
	public function getWorkspace();

	/**
	 * Returns the identifier of this node
	 *
	 * @return string the node's UUID (unique within the workspace)
	 */
	public function getIdentifier();

	/**
	 * Sets the index of this node
	 * NOTE: This method is meant for internal use and may only be used by other nodes.
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
	 * @return PersistentNodeInterface The parent node or NULL if this is the root node
	 */
	public function getParent();

	/**
	 * Returns the parent node path
	 *
	 * @return string Absolute node path of the parent node
	 */
	public function getParentPath();

	/**
	 * Moves this node before the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @return void
	 */
	public function moveBefore(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode);

	/**
	 * Moves this node after the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @return void
	 */
	public function moveAfter(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode);

	/**
	 * Moves this node into the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @return void
	 */
	public function moveInto(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode);

	/**
	 * Copies this node before the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @param string $nodeName
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface
	 * @throws \TYPO3\TYPO3CR\Exception\NodeExistsException
	 */
	public function copyBefore(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode, $nodeName);

	/**
	 * Copies this node after the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @param string $nodeName
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface
	 * @throws \TYPO3\TYPO3CR\Exception\NodeExistsException
	 */
	public function copyAfter(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode, $nodeName);

	/**
	 * Copies this node into the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @param string $nodeName
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface
	 * @throws \TYPO3\TYPO3CR\Exception\NodeExistsException
	 */
	public function copyInto(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode, $nodeName);

	/**
	 * Returns the current context this node operates in.
	 * This is for internal use only.
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	public function getContext();

	/**
	 * Tells if this node is "visible".
	 * For this the "hidden" flag and the "hiddenBeforeDateTime" and "hiddenAfterDateTime" dates are
	 * taken into account.
	 *
	 * @return boolean
	 */
	public function isVisible();

	/**
	 * Tells if this node may be accessed according to the current security context.
	 *
	 * @return boolean
	 */
	public function isAccessible();

	/**
	 * Returns a node specified by the given relative path.
	 *
	 * @param string $path Path specifying the node, relative to this node
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface The specified node or NULL if no such node exists
	 */
	public function getNode($path);

	/**
	 * Creates, adds and returns a child node of this node. Also sets default
	 * properties and creates default subnodes.
	 *
	 * @param string $name Name of the new node
	 * @param \TYPO3\TYPO3CR\Domain\Model\ContentType $contentType Content type of the new node (optional)
	 * @param string $identifier The identifier of the node, unique within the workspace, optional(!)
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface
	 */
	public function createNode($name, \TYPO3\TYPO3CR\Domain\Model\ContentType $contentType = NULL, $identifier = NULL);

	/**
	 * Creates, adds and returns a child node of this node, without setting default
	 * properties or creating subnodes. Only used internally.
	 *
	 * @param string $name Name of the new node
	 * @param \TYPO3\TYPO3CR\Domain\Model\ContentType $contentType Content type of the new node (optional)
	 * @param string $identifier The identifier of the node, unique within the workspace, optional(!)
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface
	 */
	public function createSingleNode($name, \TYPO3\TYPO3CR\Domain\Model\ContentType $contentType = NULL, $identifier = NULL);

	/**
	 * Creates and persists a node from the given $nodeTemplate as child node
	 *
	 * @param NodeTemplate $nodeTemplate
	 * @param string $nodeName name of the new node. If not specified the name of the nodeTemplate will be used.
	 * @return PersistentNodeInterface the freshly generated node
	 */
	public function createNodeFromTemplate(NodeTemplate $nodeTemplate, $nodeName = NULL);

	/**
	 * Returns the primary child node of this node.
	 *
	 * Which node acts as a primary child node will in the future depend on the
	 * content type. For now it is just the first child node.
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface The primary child node or NULL if no such node exists
	 */
	public function getPrimaryChildNode();

	/**
	 * Returns all direct child nodes of this node.
	 * If a content type is specified, only nodes of that type are returned.
	 *
	 * @param string $contentTypeFilter If specified, only nodes with that content type are considered
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface> An array of nodes or an empty array if no child nodes matched
	 */
	public function getChildNodes($contentTypeFilter = NULL);

	/**
	 * Checks if this node has any child nodes.
	 *
	 * @param string $contentTypeFilter If specified, only nodes with that content type are considered
	 * @return boolean TRUE if this node has child nodes, otherwise FALSE
	 */
	public function hasChildNodes($contentTypeFilter = NULL);

	/**
	 * Removes this node and all its child nodes.
	 *
	 * @return void
	 */
	public function remove();

	/**
	 * Enables using the remove method when only setters are available.
	 *
	 * @param boolean $removed If TRUE, this node and it's child nodes will be removed. Cannot handle FALSE (yet).
	 * @return void
	 */
	public function setRemoved($removed);

	/**
	 * If this node is a removed node.
	 *
	 * @return boolean
	 */
	public function isRemoved();

}

?>
