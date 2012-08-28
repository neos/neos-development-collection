<?php
namespace TYPO3\TYPO3CR\Domain\Model;

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
 * Interface for a Node inside the Content Repository.
 *
 */
interface NodeInterface {

	/**
	 * Regex pattern which matches a node path without any context information
	 */
	const MATCH_PATTERN_PATH = '/^(\/|(?:\/[a-z0-9\-]+)+)$/i';

	/**
	 * Regex pattern which matches a Node Name (ie. segment of a node path)
	 */
	const MATCH_PATTERN_NAME = '/^[a-z0-9\-]+$/i';

	/**
	 * Regex pattern which matches a "context path", ie. a node path possibly containing context information such as the
	 * workspace name. This pattern is used at least in the route part handler.
	 */
	const MATCH_PATTERN_CONTEXTPATH = '/^(?P<NodePath>(?:\/?[a-z0-9\-]+)(?:\/[a-z0-9\-]+)*)?(?:@(?P<WorkspaceName>[a-z0-9\-]+))?$/i';

	/**
	 * Maximum number of characters to allow / use for a "label" of a Node
	 */
	const LABEL_MAXIMUM_CHARACTERS = 30;

	/**
	 * Sets the absolute path of this node.
	 *
	 * This method is only for internal use by the content repository. Changing
	 * the path of a node manually may lead to unexpected behavior.
	 *
	 * @param string $path
	 * @return void
	 */
	public function setPath($path);

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
	 * Returns the level at which this node is located.
	 * Counting starts with 0 for "/", 1 for "/foo", 2 for "/foo/bar" etc.
	 *
	 * @return integer
	 */
	public function getDepth();

	/**
	 * Returns the name of this node
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Returns an up to LABEL_MAXIMUM_LENGTH characters long plain text description of this node
	 *
	 * @return string
	 */
	public function getLabel();

	/**
	 * Returns a short abstract describing / containing summarized content of this node
	 *
	 * @return string
	 */
	public function getAbstract();

	/**
	 * Sets the workspace of this node.
	 *
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
	 *
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
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The parent node or NULL if this is the root node
	 */
	public function getParent();

	/**
	 * Moves this node before the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @return void
	 */
	public function moveBefore(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode);

	/**
	 * Moves this node after the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @return void
	 */
	public function moveAfter(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode);

	/**
	 * Sets the specified property.
	 *
	 * If the node has a content object attached, the property will be set there
	 * if it is settable.
	 *
	 * @param string $propertyName Name of the property
	 * @param mixed $value Value of the property
	 * @return void
	 */
	public function setProperty($propertyName, $value);

	/**
	 * If this node has a property with the given name.
	 *
	 * If the node has a content object attached, the property will be checked
	 * there.
	 *
	 * @param string $propertyName
	 * @return boolean
	 */
	public function hasProperty($propertyName);

	/**
	 * Returns the specified property.
	 *
	 * If the node has a content object attached, the property will be fetched
	 * there if it is gettable.
	 *
	 * @param string $propertyName Name of the property
	 * @return mixed value of the property
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException if the node does not contain the specified property
	 */
	public function getProperty($propertyName);

	/**
	 * Removes the specified property.
	 *
	 * If the node has a content object attached, the property will not be removed on
	 * that object if it exists.
	 *
	 * @param string $propertyName Name of the property
	 * @return void
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException if the node does not contain the specified property
	 */
	public function removeProperty($propertyName);

	/**
	 * Returns all properties of this node.
	 *
	 * If the node has a content object attached, the properties will be fetched
	 * there.
	 *
	 * @return array Property values, indexed by their name
	 */
	public function getProperties();

	/**
	 * Returns the names of all properties of this node.
	 *
	 * @return array Property names
	 */
	public function getPropertyNames();

	/**
	 * Sets a content object for this node.
	 *
	 * @param object $contentObject The content object
	 * @return void
	 */
	public function setContentObject($contentObject);

	/**
	 * Returns the content object of this node (if any).
	 *
	 * @return object
	 */
	public function getContentObject();

	/**
	 * Unsets the content object of this node.
	 *
	 * @return void
	 */
	public function unsetContentObject();

	/**
	 * Sets the content type of this node.
	 *
	 * @param string $contentType
	 * @return void
	 */
	public function setContentType($contentType);

	/**
	 * Returns the content type of this node.
	 *
	 * @return ContentType
	 */
	public function getContentType();

	/**
	 * Creates, adds and returns a child node of this node.
	 *
	 * @param string $name Name of the new node
	 * @param string $contentType Content type of the new node (optional)
	 * @param string $identifier The identifier of the node, unique within the workspace, optional(!)
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	public function createNode($name, $contentType = NULL, $identifier = NULL);

	/**
	 * Returns a node specified by the given relative path.
	 *
	 * @param string $path Path specifying the node, relative to this node
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The specified node or NULL if no such node exists
	 */
	public function getNode($path);
	/**
	 * Returns the primary child node of this node.
	 *
	 * Which node acts as a primary child node will in the future depend on the
	 * content type. For now it is just the first child node.
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The primary child node or NULL if no such node exists
	 */
	public function getPrimaryChildNode();

	/**
	 * Returns all direct child nodes of this node.
	 * If a content type is specified, only nodes of that type are returned.
	 *
	 * @param string $contentTypeFilter If specified, only nodes with that content type are considered
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> An array of nodes or an empty array if no child nodes matched
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
	 * If this node is a removed node.
	 *
	 * @return boolean
	 */
	public function isRemoved();

	/**
	 * Sets the "hidden" flag for this node.
	 *
	 * @param boolean $hidden If TRUE, this Node will be hidden
	 * @return void
	 */
	public function setHidden($hidden);

	/**
	 * Returns the current state of the hidden flag
	 *
	 * @return boolean
	 */
	public function isHidden();

	/**
	 * Sets the date and time when this node becomes potentially visible.
	 *
	 * @param \DateTime $dateTime Date before this node should be hidden
	 * @return void
	 */
	public function setHiddenBeforeDateTime(\DateTime $dateTime = NULL);

	/**
	 * Returns the date and time before which this node will be automatically hidden.
	 *
	 * @return \DateTime Date before this node will be hidden
	 */
	public function getHiddenBeforeDateTime();

	/**
	 * Sets the date and time when this node should be automatically hidden
	 *
	 * @param \DateTime $dateTime Date after which this node should be hidden
	 * @return void
	 */
	public function setHiddenAfterDateTime(\DateTime $dateTime = NULL);

	/**
	 * Returns the date and time after which this node will be automatically hidden.
	 *
	 * @return \DateTime Date after which this node will be hidden
	 */
	public function getHiddenAfterDateTime();

	/**
	 * Sets if this node should be hidden in indexes, such as a site navigation.
	 *
	 * @param boolean $hidden TRUE if it should be hidden, otherwise FALSE
	 * @return void
	 */
	public function setHiddenInIndex($hidden);

	/**
	 * If this node should be hidden in indexes
	 *
	 * @return boolean
	 */
	public function isHiddenInIndex();

	/**
	 * Sets the roles which are required to access this node
	 *
	 * @param array $accessRoles
	 * @return void
	 */
	public function setAccessRoles(array $accessRoles);

	/**
	 * Returns the names of defined access roles
	 *
	 * @return array
	 */
	public function getAccessRoles();

	/**
	 * Tells if this node is "visible".
	 *
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

}

?>