<?php
namespace F3\TYPO3CR\Domain\Model;

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
 * Interface for a Node inside the Content Repository.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
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
	const MATCH_PATTERN_CONTEXTPATH = '/^(?P<NodePath>(?:\/?[a-z0-9\-]+)(?:\/[a-z0-9\-]+)*)?(?:@(?P<WorkspaceName>[a-z0-9\-]+))?$/';

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
	 * @param \F3\TYPO3CR\Domain\Model\Workspace $workspace
	 * @return void
	 */
	public function setWorkspace(\F3\TYPO3CR\Domain\Model\Workspace $workspace);

	/**
	 * Returns the workspace this node is contained in
	 *
	 * @return \F3\TYPO3CR\Domain\Model\Workspace
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
	 * @return \F3\TYPO3CR\Domain\Model\NodeInterface The parent node or NULL if this is the root node
	 */
	public function getParent();

	/**
	 * Moves this node before the given node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @return void
	 */
	public function moveBefore(\F3\TYPO3CR\Domain\Model\NodeInterface $referenceNode);

	/**
	 * Moves this node after the given node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @return void
	 */
	function moveAfter(\F3\TYPO3CR\Domain\Model\NodeInterface $referenceNode);

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
	 * @throws \F3\TYPO3CR\Exception\NodeException if the a content object exists but does not contain the specified property
	 */
	public function getProperty($propertyName);

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
	 * @return string $contentType
	 */
	public function getContentType();

	/**
	 * Creates, adds and returns a child node of this node.
	 *
	 * @param string $name Name of the new node
	 * @param string $contentType Content type of the new node (optional)
	 * @param string $identifier The identifier of the node, unique within the workspace, optional(!)
	 * @return \F3\TYPO3CR\Domain\Model\NodeInterface
	 */
	public function createNode($name, $contentType = NULL, $identifier = NULL);

	/**
	 * Returns a node specified by the given relative path.
	 *
	 * @param string $path Path specifying the node, relative to this node
	 * @return \F3\TYPO3CR\Domain\Model\NodeInterface The specified node or NULL if no such node exists
	 */
	public function getNode($path);
	/**
	 * Returns the primary child node of this node.
	 *
	 * Which node acts as a primary child node will in the future depend on the
	 * content type. For now it is just the first child node.
	 *
	 * @return \F3\TYPO3CR\Domain\Model\NodeInterface The primary child node or NULL if no such node exists
	 */
	public function getPrimaryChildNode();

	/**
	 * Returns all direct child nodes of this node.
	 * If a content type is specified, only nodes of that type are returned.
	 *
	 * @param string $contentTypeFilter If specified, only nodes with that content type are considered
	 * @return array<\F3\TYPO3CR\Domain\Model\NodeInterface> An array of nodes or an empty array if no child nodes matched
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
	 * @param \DateTime $hideBeforeDate Date before this node should be hidden
	 * @return void
	 */
	public function setHiddenBeforeDate(\DateTime $dateTime);

	/**
	 * Returns the date and time before which this node will be automatically hidden.
	 *
	 * @return \DateTime Date before this node will be hidden
	 */
	public function getHiddenBeforeDate();

	/**
	 * Sets the date and time when this node should be automatically hidden
	 *
	 * @param \DateTime $hideAfterDate Date after which this node should be hidden
	 * @return void
	 */
	public function setHiddenAfterDate(\DateTime $dateTime);

	/**
	 * Returns the date and time after which this node will be automatically hidden.
	 *
	 * @return \DateTime Date after which this node will be hidden
	 */
	public function getHiddenAfterDate();

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
	 * For this the "hidden" flag and the "hiddenBeforeDate" and "hiddenAfterDate" dates are
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
	 * Sets the context from which this node was acquired.
	 *
	 * This will be set by the context or other nodes while retrieving this node.
	 * This method is only for internal use, don't mess with it.
	 *
	 * @param \F3\TYPO3CR\Domain\Service\Context $context
	 * @return void
	 */
	public function setContext(\F3\TYPO3CR\Domain\Service\Context $context);

	/**
	 * Returns the current context this node operates in.
	 *
	 * @return \F3\TYPO3CR\Domain\Service\Context
	 */
	public function getContext();

}

?>
