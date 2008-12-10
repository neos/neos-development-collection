<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\FLOW3\Persistence;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package TYPO3CR
 * @subpackage FLOW3
 * @version $Id$
 */

/**
 * A persistence backend for FLOW3 using the Content Repository
 *
 * @package TYPO3CR
 * @subpackage FLOW3
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Backend implements \F3\FLOW3\Persistence\BackendInterface {

	/**
	 * @var \F3\FLOW3\Reflection\Service
	 */
	protected $reflectionService;

	/**
	 * @var \F3\PHPCR\SessionInterface
	 */
	protected $session;

	/**
	 * @var \F3\PHPCR\NodeInterface
	 */
	protected $baseNode;

	/**
	 * @var array
	 */
	protected $classSchemata;

	/**
	 * @var \F3\TYPO3CR\FLOW3\Persistence\IdentityMap
	 */
	protected $identityMap;

	/**
	 * @var array
	 */
	protected $aggregateRootObjects = array();

	/**
	 * @var array
	 */
	protected $deletedObjects = array();

	/**
	 * Constructs the backend
	 *
	 * @param \F3\PHPCR\SessionInterface $session the Content Repository session used to persist data
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(\F3\PHPCR\SessionInterface $session) {
		$this->session = $session;
	}

	/**
	 * Injects A Reflection Service instance used for processing objects
	 *
	 * @param \F3\FLOW3\Reflection\Service $reflectionService
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the identity map
	 *
	 * @param \F3\TYPO3CR\FLOW3\Persistence\IdentityMap $identityMap
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectIdentityMap(\F3\TYPO3CR\FLOW3\Persistence\IdentityMap $identityMap) {
		$this->identityMap = $identityMap;
	}

	/**
	 * Initializes the backend
	 *
	 * @param array $classSchemata the class schemata the backend will be handling
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initialize(array $classSchemata) {
		$rootNode = $this->session->getRootNode();
		$nodeTypeManager = $this->session->getWorkspace()->getNodeTypeManager();

		if ($rootNode->hasNode('flow3:persistence/flow3:objects')) {
			$this->baseNode = $rootNode->getNode('flow3:persistence/flow3:objects');
		} else {
			if ($rootNode->hasNode('flow3:persistence')) {
				$persistenceNode = $rootNode->getNode('flow3:persistence');
				$this->baseNode = $persistenceNode->addNode('flow3:objects', 'nt:unstructured');
			} else {
				$persistenceNode = $rootNode->addNode('flow3:persistence', 'nt:unstructured');
				$this->baseNode = $persistenceNode->addNode('flow3:objects', 'nt:unstructured');
			}
		}

		if (!$nodeTypeManager->hasNodeType('flow3:arrayPropertyProxy')) {
			$nodeTypeTemplate = $nodeTypeManager->createNodeTypeTemplate();
			$nodeTypeTemplate->setName('flow3:arrayPropertyProxy');
			$nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
		}

		$this->classSchemata = $classSchemata;
		foreach($this->classSchemata as $schema) {
			$nodeTypeName = 'flow3:' . $this->convertClassNameToJCRName($schema->getClassName());
			if (!$nodeTypeManager->hasNodeType($nodeTypeName)) {
				$nodeTypeTemplate = $nodeTypeManager->createNodeTypeTemplate();
				$nodeTypeTemplate->setName($nodeTypeName);
				$nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
			}
		}
	}

	/**
	 * Returns the repository session
	 *
	 * @return \F3\PHPCR\SessionInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getSession() {
		return $this->session;
	}

	/**
	 * Sets the aggregate root objects
	 *
	 * @param array $objects
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setAggregateRootObjects(array $objects) {
		$this->aggregateRootObjects = $objects;
	}

	/**
	 * Sets the deleted objects
	 *
	 * @param array $objects
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setDeletedObjects(array $objects) {
		$this->deletedObjects = $objects;
	}

	/**
	 * Commits the current persistence session.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function commit() {
		$this->traverseAggregateRootObjects();

		foreach ($this->deletedObjects as $object) {
			$this->processDeletedObject($object);
		}

		$this->session->save();
		$this->deletedObjects = array();
	}

	/**
	 * Traverse all aggregate roots breadth first.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function traverseAggregateRootObjects() {
			// make sure we have a corresponding node for all new objects on
			// first level
		foreach ($this->aggregateRootObjects as $object) {
			if (!$this->identityMap->hasObject($object)) {
				$this->createNodeForObject($object, $this->baseNode, 'flow3:' . $this->convertClassNameToJCRName($object->AOPProxyGetProxyTargetClassName()));
			}
		}

			// now traverse into the objects
		foreach ($this->aggregateRootObjects as $object) {
			$this->traverseObject($object);
		}

	}

	/**
	 * Stores, updates or removes an object's corresponding node in the
	 * repository by doing a breadth first traversal over the properties.
	 * In other words, objects are handled in a second step.
	 *
	 * @param object $object The object to persist
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function traverseObject($object) {
		$queue = array();
		$node = $this->session->getNodeByIdentifier($this->identityMap->getIdentifier($object));

		foreach ($this->classSchemata[$object->AOPProxyGetProxyTargetClassName()]->getProperties() as $propertyName => $propertyType) {
			$propertyValue = $object->AOPProxyGetProperty($propertyName);
			if (is_array($propertyValue)) {
				if ($object->isNew() || $object->isDirty($propertyName)) {
					$this->storeArrayAsNode($propertyValue, $node, 'flow3:' . $propertyName);
				}
				if (is_object(current($propertyValue)) && !(current($propertyValue) instanceof \DateTime)) {
					$queue = array_merge($queue, array_values($propertyValue));
				}
			} elseif (is_object($propertyValue) && $propertyType !== 'DateTime') {
				if ($object->isNew()) {
					$this->createNodeForObject($propertyValue, $node, 'flow3:' . $propertyName);
				}
				$queue[] = $propertyValue;
			} elseif ($object->isNew() || $object->isDirty($propertyName)) {
				$node->setProperty('flow3:' . $propertyName, $propertyValue, \F3\PHPCR\PropertyType::valueFromType($propertyType));
			}
		}

		$object->memorizeCleanState();

			// here we loop over the objects. their nodes are already at the
			// right place and have the right name. fancy, eh?
		foreach ($queue as $object) {
			$this->traverseObject($object);
			$object->memorizeCleanState();
		}
	}

	/**
	 * Creates a node for the given object and registers it with the identity map.
	 *
	 * @param object $object The object for which to create a node
	 * @param \F3\PHPCR\NodeInterface $parentNode
	 * @param string $nodeName The name to use for the object, must be a legal name as per JSR-283
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function createNodeForObject($object, \F3\PHPCR\NodeInterface $parentNode, $nodeName) {
		$className = $object->AOPProxyGetProxyTargetClassName();
		$nodeTypeName = 'flow3:' . $this->convertClassNameToJCRName($className);
		$identifierProperty = $this->classSchemata[$className]->getIdentifierProperty();

		if ($identifierProperty !== NULL) {
			$node = $parentNode->addNode($nodeName, $nodeTypeName, $object->AOPProxyGetProperty($identifierProperty));
		} else {
			$node = $parentNode->addNode($nodeName, $nodeTypeName);
		}

		$this->identityMap->registerObject($object, $node->getIdentifier());
	}

	/**
	 * Store an array as a node of type flow3:arrayPropertyProxy, with each
	 * array element becoming a property named like the key and the value.
	 *
	 * Every element not being an object or array will become a property on the
	 * node, arrays will be handled recursively.
	 *
	 * Note: Objects contained in the array will have a node created, properties
	 * On those nodes must be set elsewhere!
	 *
	 * @param array $array The array for which to create a node
	 * @param \F3\PHPCR\NodeInterface $parentNode The node to add the property proxy to
	 * @param string $nodeName The name to use for the object, must be a legal name as per JSR-283
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function storeArrayAsNode(array $array, \F3\PHPCR\NodeInterface $parentNode, $nodeName) {
		if ($parentNode->hasNode($nodeName)) {
			$node = $parentNode->getNode($nodeName);
		} else {
			$node = $parentNode->addNode($nodeName, 'flow3:arrayPropertyProxy');
		}
		foreach ($array as $key => $element) {
			if (is_object($element) && !($element instanceof \DateTime) && $element->isNew()) {
				$this->createNodeForObject($element, $node, 'flow3:' . $key);
			} elseif (is_array($element)) {
				$this->storeArrayAsNode($element, $node, 'flow3:' . $key);
			} elseif ($element->isDirty($key)) {
				$node->setProperty('flow3:' . $key, $element, \F3\PHPCR\PropertyType::valueFromType(gettype($element)));
			}
		}
	}

	/**
	 * Removes an object from the content repository.
	 *
	 * @param object $object
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function processDeletedObject($object) {
		if ($this->identityMap->hasObject($object)) {
			$identifier = $this->identityMap->getIdentifier($object);
			$node = $this->session->getNodeByIdentifier($identifier);
			$node->remove();
			$this->identityMap->unregisterObject($object);
		}
	}

	/**
	 * Converts a given class name to a legal JCR node name
	 *
	 * @param string $className
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function convertClassNameToJCRName($className) {
		return str_replace('\\', '_', $className);
	}

}

?>
