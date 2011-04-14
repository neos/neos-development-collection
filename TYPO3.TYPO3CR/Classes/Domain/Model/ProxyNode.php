<?php
declare(ENCODING = 'utf-8');
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
 * A Proxy Node which behaves like a real Node but acts as a placeholder for nodes
 * of other workspaces than the current workspace.
 *
 * This is used for realizing a copy-on-write / lazy cloning functionality.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class ProxyNode implements NodeInterface {

	/**
	 * This ID is only for the ORM.
	 *
	 * @var integer
	 * @Id
	 * @GeneratedValue
	*/
	protected $id;

	/**
	 * The original node this proxy refers to
	 *
	 * @var \F3\TYPO3CR\Domain\Model\NodeInterface
	 * @ManyToOne
	 */
	protected $originalNode;

	/**
	 * @var \F3\TYPO3CR\Domain\Model\NodeInterface
	 * @ManyToOne
	 */
	protected $newNode;

	/**
	 * @inject
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @inject
	 * @var \F3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @var \F3\TYPO3CR\Domain\Service\Context
	 * @transient
	 */
	protected $context;

	/**
	 * Constructs this proxy node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\NodeInterface $originalNode
	 * @autowiring off
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function  __construct(\F3\TYPO3CR\Domain\Model\NodeInterface $originalNode) {
		if ($originalNode instanceof \F3\TYPO3CR\Domain\Model\ProxyNode) {
			throw new \InvalidArgumentException('The original node must not be a ProxyNode', 1289475179);
		}
		$this->originalNode = $originalNode;
	}

	/**
	 * Sets the absolute path of this node.
	 *
	 * This method is only for internal use by the content repository. Changing
	 * the path of a node manually may lead to unexpected behavior.
	 *
	 * @param string $path
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setPath($path) {
		if (!isset($this->newNode)) {
			$this->cloneOriginalNode();
		}
		$this->newNode->setPath($path);
	}

	/**
	 * Returns the path of this node
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPath() {
		return (isset($this->newNode) ? $this->newNode->getPath() : $this->originalNode->getPath());
	}

	/**
	 * Returns the path of this node with additional context information (such as the workspace name)
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContextPath() {
		return (isset($this->newNode) ? $this->newNode->getContextPath() : $this->originalNode->getContextPath());
	}

	/**
	 * Returns the level at which this node is located.
	 * Counting starts with 0 for "/", 1 for "/foo", 2 for "/foo/bar" etc.
	 *
	 * @return integer
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDepth() {
		return (isset($this->newNode) ? $this->newNode->getDepth() : $this->originalNode->getDepth());
	}

	/**
	 * Returns the name of this node
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getName() {
		return (isset($this->newNode) ? $this->newNode->getName() : $this->originalNode->getName());
	}

	/**
	 * Returns an up to LABEL_MAXIMUM_LENGTH characters long plain text description of this node
	 *
	 * @return string
	 */
	public function getLabel() {
		return (isset($this->newNode) ? $this->newNode->getLabel() : $this->originalNode->getLabel());
	}

	/**
	 * Returns a short abstract describing / containing summarized content of this node
	 *
	 * @return string
	 */
	public function getAbstract() {
		return (isset($this->newNode) ? $this->newNode->getAbstract() : $this->originalNode->getAbstract());
	}

	/**
	 * Sets the workspace of this node.
	 *
	 * This method is only for internal use by the content repository. Changing
	 * the workspace of a node manually may lead to unexpected behavior.
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Workspace $workspace
	 * @return void
	 */
	public function setWorkspace(\F3\TYPO3CR\Domain\Model\Workspace $workspace) {
		if (!isset($this->newNode)) {
			$this->cloneOriginalNode();
		}
		$this->newNode->setWorkspace($workspace);
	}

	/**
	 * Returns the workspace this node is contained in
	 *
	 * @return \F3\TYPO3CR\Domain\Model\Workspace
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getWorkspace() {
		return (isset($this->newNode) ? $this->newNode->getWorkspace() : $this->originalNode->getWorkspace());
	}

	/**
	 * Returns the identifier of this node
	 *
	 * @return string the node's UUID (unique within the workspace)
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getIdentifier() {
		return $this->originalNode->getIdentifier();
	}

	/**
	 * Sets the index of this node
	 *
	 * NOTE: This method is meant for internal use and must only be used by other nodes.
	 *
	 * @param integer $index The new index
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setIndex($index) {
		if (!isset($this->newNode)) {
			$this->cloneOriginalNode();
		}
		$this->newNode->setIndex($index);
	}

	/**
	 * Returns the index of this node which determines the order among siblings
	 * with the same parent node.
	 *
	 * @return integer
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getIndex() {
		return (isset($this->newNode) ? $this->newNode->getIndex() : $this->originalNode->getIndex());
	}

	/**
	 * Returns the parent node of this node
	 *
	 * @return \F3\TYPO3CR\Domain\Model\NodeInterface The parent node or NULL if this is the root node
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getParent() {
		return (isset($this->newNode) ? $this->newNode->getParent() : $this->originalNode->getParent());
	}

	/**
	 * Moves this node before the given node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function moveBefore(\F3\TYPO3CR\Domain\Model\NodeInterface $referenceNode) {
		if (!isset($this->newNode)) {
			$this->cloneOriginalNode();
		}
		$this->newNode->moveBefore($referenceNode);
	}

	/**
	 * Moves this node after the given node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function moveAfter(\F3\TYPO3CR\Domain\Model\NodeInterface $referenceNode) {
		if (!isset($this->newNode)) {
			$this->cloneOriginalNode();
		}
		$this->newNode->moveAfter($referenceNode);
	}

	/**
	 * Sets the specified property.
	 *
	 * If the node has a content object attached, the property will be set there
	 * if it is settable.
	 *
	 * @param string $propertyName Name of the property
	 * @param mixed $value Value of the property
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setProperty($propertyName, $value) {
		if (!isset($this->newNode)) {
			$this->cloneOriginalNode();
		}
		$this->newNode->setProperty($propertyName, $value);
	}

	/**
	 * If this node has a property with the given name.
	 *
	 * If the node has a content object attached, the property will be checked
	 * there.
	 *
	 * @param string $propertyName
	 * @return boolean
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasProperty($propertyName) {
		return (isset($this->newNode) ? $this->newNode->hasProperty($propertyName) : $this->originalNode->hasProperty($propertyName));
	}

	/**
	 * Returns the specified property.
	 *
	 * If the node has a content object attached, the property will be fetched
	 * there if it is gettable.
	 *
	 * @param string $propertyName Name of the property
	 * @return mixed value of the property
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getProperty($propertyName) {
		return (isset($this->newNode) ? $this->newNode->getProperty($propertyName) : $this->originalNode->getProperty($propertyName));
	}

	/**
	 * Returns all properties of this node.
	 *
	 * If the node has a content object attached, the properties will be fetched
	 * there.
	 *
	 * @return array Property values, indexed by their name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getProperties() {
		return (isset($this->newNode) ? $this->newNode->getProperties() : $this->originalNode->getProperties());
	}

	/**
	 * Returns the names of all properties of this node.
	 *
	 * @return array Property names
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPropertyNames() {
		return (isset($this->newNode) ? $this->newNode->getPropertyNames() : $this->originalNode->getPropertyNames());
	}

	/**
	 * Sets a content object for this node.
	 *
	 * @param object $contentObject The content object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContentObject($contentObject) {
		if (!isset($this->newNode)) {
			$this->cloneOriginalNode();
		}
		$this->newNode->setContentObject($contentObject);
	}

	/**
	 * Returns the content object of this node (if any).
	 *
	 * @return object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContentObject() {
		return (isset($this->newNode) ? $this->newNode->getContentObject() : $this->originalNode->getContentObject());
	}

	/**
	 * Unsets the content object of this node.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function unsetContentObject() {
		if (!isset($this->newNode)) {
			$this->cloneOriginalNode();
		}
		$this->newNode->unsetContentObject();
	}

	/**
	 * Sets the content type of this node.
	 *
	 * @param string $contentType
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContentType($contentType) {
		if (!isset($this->newNode)) {
			$this->cloneOriginalNode();
		}
		$this->newNode->setContentType($contentType);
	}

	/**
	 * Returns the content type of this node.
	 *
	 * @return string $contentType
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContentType() {
		return (isset($this->newNode) ? $this->newNode->getContentType() : $this->originalNode->getContentType());
	}

	/**
	 * Creates, adds and returns a child node of this node.
	 *
	 * @param string $name Name of the new node
	 * @param string $contentType Content type of the new node (optional)
	 * @return \F3\TYPO3CR\Domain\Model\NodeInterface
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createNode($name, $contentType = NULL) {
		return (isset($this->newNode) ? $this->newNode->createNode($name, $contentType) : $this->originalNode->createNode($name, $contentType));
	}

	/**
	 * Returns a node specified by the given relative path.
	 *
	 * @param string $path Path specifying the node, relative to this node
	 * @return \F3\TYPO3CR\Domain\Model\NodeInterface The specified node or NULL if no such node exists
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getNode($path) {
		return (isset($this->newNode) ? $this->newNode->getNode($path) : $this->originalNode->getNode($path));
	}

	/**
	 * Returns the primary child node of this node.
	 *
	 * Which node acts as a primary child node will in the future depend on the
	 * content type. For now it is just the first child node.
	 *
	 * @return \F3\TYPO3CR\Domain\Model\NodeInterface The primary child node or NULL if no such node exists
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPrimaryChildNode() {
		return (isset($this->newNode) ? $this->newNode->getPrimaryChildNode() : $this->originalNode->getPrimaryChildNode());
	}

	/**
	 * Returns all direct child nodes of this node.
	 * If a content type is specified, only nodes of that type are returned.
	 *
	 * @param string $contentType If specified, only nodes with that content type are considered
	 * @return array<\F3\TYPO3CR\Domain\Model\NodeInterface> An array of nodes or an empty array if no child nodes matched
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getChildNodes($contentType = NULL) {
		return (isset($this->newNode) ? $this->newNode->getChildNodes($contentType) : $this->originalNode->getChildNodes($contentType));
	}

	/**
	 * Removes this node and all its child nodes.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function remove() {
		if (!isset($this->newNode)) {
			$this->cloneOriginalNode();
		}
		$this->newNode->remove();
	}

	/**
	 * Sets the context from which this proxy node was acquired.
	 *
	 * This will be set by the context or other nodes while retrieving this node.
	 * This method is only for internal use, don't mess with it.
	 *
	 * @param \F3\TYPO3CR\Domain\Service\Context $context
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContext(\F3\TYPO3CR\Domain\Service\Context $context) {
		$this->context = $context;
		$this->originalNode->setContext($context);
		if (isset($this->newNode)) {
			$this->newNode->setContext($context);
		}
	}

	/**
	 * Returns the current context this proxy node operates in.
	 *
	 * @return \F3\TYPO3CR\Domain\Service\Context
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * Materializes the original node (of a different workspace) into the current
	 * workspace.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function cloneOriginalNode() {
		$this->newNode = $this->objectManager->create('F3\TYPO3CR\Domain\Model\Node', $this->originalNode->getPath(), $this->context->getWorkspace(), $this->originalNode->getIdentifier());

		foreach ($this->originalNode->getProperties() as $propertyName => $propertyValue) {
			$this->newNode->setProperty($propertyName, $propertyValue);
		}
		$this->newNode->setIndex($this->originalNode->getIndex());
		$this->newNode->setContentType($this->originalNode->getContentType());
		$contentObject = $this->originalNode->getContentObject();
		if ($contentObject !== NULL) {
			$this->newNode->setContentObject($contentObject);
		}
		$this->newNode->setContext($this->context);

		$this->nodeRepository->add($this->newNode);
	}
}

?>