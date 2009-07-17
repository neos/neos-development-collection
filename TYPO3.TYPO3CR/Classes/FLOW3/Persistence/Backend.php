<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\FLOW3\Persistence;

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
 * A persistence backend for FLOW3 using the Content Repository
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Backend implements \F3\FLOW3\Persistence\BackendInterface {

	/**
	 * The nodetype used for array property proxy nodes
	 * @var string
	 */
	const NODETYPE_ARRAYPROXY = 'flow3:arrayProxy';

	/**
	 * The nodetype used for object proxy nodes
	 * @var string
	 */
	const NODETYPE_OBJECTPROXY = 'flow3:objectProxy';

	/**
	 * The nodetype used for SplObjectStorage proxy nodes
	 * @var string
	 */
	const NODETYPE_SPLOBJECTSTORAGEPROXY = 'flow3:splObjectStorageProxy';

	/**
	 * @var \F3\FLOW3\Reflection\Service
	 */
	protected $reflectionService;

	/**
	 * @var \F3\PHPCR\SessionInterface
	 */
	protected $session;

	/**
	 * @var \SplObjectStorage
	 */
	protected $aggregateRootObjects;

	/**
	 * @var \SplObjectStorage
	 */
	protected $deletedObjects;

	/**
	 * @var \SplObjectStorage
	 */
	protected $incompleteObjectProxyNodes;

	/**
	 * @var \F3\TYPO3CR\FLOW3\Persistence\IdentityMap
	 */
	protected $identityMap;

	/**
	 * @var \F3\PHPCR\NodeInterface
	 */
	protected $baseNode;

	/**
	 * @var array
	 */
	protected $classSchemata = array();

	/**
	 * Constructs the backend
	 *
	 * @param \F3\PHPCR\SessionInterface $session the Content Repository session used to persist data
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(\F3\PHPCR\SessionInterface $session) {
		$this->session = $session;
		$this->aggregateRootObjects = new \SplObjectStorage();
		$this->deletedObjects = new \SplObjectStorage();
		$this->incompleteObjectProxyNodes = new \SplObjectStorage();
	}

	/**
	 * Injects a Reflection Service instance used for processing objects
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
		$this->classSchemata = $classSchemata;
		$this->initializeBaseNode();
		$this->initializeNodeTypes();
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
	 * Returns the (internal) identifier for the object, if it is known to the
	 * backend. Otherwise NULL is returned.
	 *
	 * Note: this returns an identifier even if the object has not been
	 * persisted, in case of AOP-managed entities. Use isNewObject() if you need
	 * to distinguish those cases.
	 *
	 * @param object $object
	 * @return string The identifier for the object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getIdentifierByObject($object) {
		if ($this->identityMap->hasObject($object)) {
			return $this->identityMap->getIdentifierByObject($object);
		} elseif ($object instanceof \F3\FLOW3\AOP\ProxyInterface && $object->FLOW3_AOP_Proxy_hasProperty('FLOW3_Persistence_Entity_UUID')) {
				// entities created get an UUID set through AOP
			return $object->FLOW3_AOP_Proxy_getProperty('FLOW3_Persistence_Entity_UUID');
		} else {
			return NULL;
		}
	}

	/**
	 * Checks if the given object has ever been persisted.
	 *
	 * @param object $object The object to check
	 * @return boolean TRUE if the object is new, FALSE if the object exists in the repository
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isNewObject($object) {
		return ($this->identityMap->hasObject($object) === FALSE);
	}

	/**
	 * Replaces the given object by the second object.
	 *
	 * This method will unregister the existing object at the identity map and
	 * register the new object instead. The existing object must therefore
	 * already be registered at the identity map which is the case for all
	 * reconstituted objects.
	 *
	 * The new object will be identified by the uuid which formerly belonged
	 * to the existing object. The existing object looses its uuid.
	 *
	 * @param object $existingObject The existing object
	 * @param object $newObject The new object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function replaceObject($existingObject, $newObject) {
		$existingUUID = $this->getIdentifierByObject($existingObject);
		if ($existingUUID === NULL) throw new \F3\TYPO3CR\FLOW3\Persistence\Exception\UnknownObjectException('The given object is unknown to this persistence backend.', 1238070163);

		$this->identityMap->unregisterObject($existingObject);
		$this->identityMap->registerObject($newObject, $existingUUID);
	}

	/**
	 * Sets the aggregate root objects
	 *
	 * @param \SplObjectStorage $objects
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setAggregateRootObjects(\SplObjectStorage $objects) {
		$this->aggregateRootObjects = $objects;
	}

	/**
	 * Sets the deleted objects
	 *
	 * @param \SplObjectStorage $objects
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setDeletedObjects(\SplObjectStorage $objects) {
		$this->deletedObjects = $objects;
	}

	/**
	 * Commits the current persistence session.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function commit() {
		$this->persistObjects();
		$this->processDeletedObjects();
		$this->session->save();
	}

	/**
	 * Initializes the base node for object persistence
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function initializeBaseNode() {
		$rootNode = $this->session->getRootNode();
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
	}

	/**
	 * Initializes the nodetypes used
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function initializeNodeTypes() {
		$nodeTypeManager = $this->session->getWorkspace()->getNodeTypeManager();

		if (!$nodeTypeManager->hasNodeType(self::NODETYPE_ARRAYPROXY)) {
			$nodeTypeTemplate = $nodeTypeManager->createNodeTypeTemplate();
			$nodeTypeTemplate->setName(self::NODETYPE_ARRAYPROXY);
			$nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
		}

		if (!$nodeTypeManager->hasNodeType(self::NODETYPE_OBJECTPROXY)) {
			$nodeTypeTemplate = $nodeTypeManager->createNodeTypeTemplate();
			$nodeTypeTemplate->setName(self::NODETYPE_OBJECTPROXY);
			$nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
		}

		if (!$nodeTypeManager->hasNodeType(self::NODETYPE_SPLOBJECTSTORAGEPROXY)) {
			$nodeTypeTemplate = $nodeTypeManager->createNodeTypeTemplate();
			$nodeTypeTemplate->setName(self::NODETYPE_SPLOBJECTSTORAGEPROXY);
			$nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
		}

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
	 * Create a node for all aggregate roots first, then traverse object graph.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function persistObjects() {
		foreach ($this->aggregateRootObjects as $object) {
			if (!$this->identityMap->hasObject($object)) {
				$this->createNodeForEntity($object, $this->baseNode, 'flow3:' . $this->convertClassNameToJCRName($object->FLOW3_AOP_Proxy_getProxyTargetClassName()));
			}
		}

		foreach ($this->aggregateRootObjects as $object) {
			$this->persistObject($object);
		}

		$this->finalizeObjectProxyNodes();
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
	protected function persistObject($object) {
		$queue = array();
		$node = $this->session->getNodeByIdentifier($this->identityMap->getIdentifierByObject($object));

		$classSchema = $this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()];
		foreach ($classSchema->getProperties() as $propertyName => $propertyData) {
			$propertyValue = $object->FLOW3_AOP_Proxy_getProperty($propertyName);
			$propertyType = $propertyData['type'];

				// if a LazyLoadingProxy has not been activated, it can neither
				// be new nor dirty...
			if ($propertyValue instanceof \F3\FLOW3\Persistence\LazyLoadingProxy) {
				continue;
			}

			if ($propertyValue === NULL) {
				if ($node->hasNode('flow3:' . $propertyName)) {
					$node->getNode('flow3:' . $propertyName)->remove();
				} elseif ($node->hasProperty('flow3:' . $propertyName)) {
					$node->setProperty('flow3:' . $propertyName, NULL);
				}
				continue;
			}

			$this->checkPropertyType($propertyType, $propertyValue);

			if ($propertyType === 'array') {
				if ($object->FLOW3_Persistence_isDirty($propertyName)) {
					$this->persistArray($propertyValue, $node, 'flow3:' . $propertyName, $queue);
				}
			} elseif ($propertyType === 'SplObjectStorage') {
				if ($object->FLOW3_Persistence_isDirty($propertyName)) {
					$this->persistSplObjectStorage($propertyValue, $node, 'flow3:' . $propertyName, $queue);
				} else {
					foreach ($propertyValue as $containedObject) {
						$queue[] = $containedObject;
					}
				}
			} elseif ($propertyType === 'DateTime') {
				$node->setProperty('flow3:' . $propertyName, $propertyValue, \F3\PHPCR\PropertyType::DATE);
			} elseif (is_object($propertyValue) && $propertyValue instanceof \F3\FLOW3\AOP\ProxyInterface) {
				if ($this->classSchemata[$propertyValue->FLOW3_AOP_Proxy_getProxyTargetClassName()]->isAggregateRoot() === TRUE) {
					if ($object->FLOW3_Persistence_isDirty($propertyName)) {
						$this->createOrUpdateProxyNodeForEntity($propertyValue, $node, 'flow3:' . $propertyName);
					}
				} else {
					if ($object->FLOW3_Persistence_isNew()) {
						if ($this->classSchemata[$propertyValue->FLOW3_AOP_Proxy_getProxyTargetClassName()]->getModelType() === \F3\FLOW3\Persistence\ClassSchema::MODELTYPE_ENTITY) {
							if ($this->identityMap->hasObject($propertyValue)) {
								$this->createOrUpdateProxyNodeForEntity($propertyValue, $node, 'flow3:' . $propertyName);
							} else {
								$this->createNodeForEntity($propertyValue, $node, 'flow3:' . $propertyName);
								$queue[] = $propertyValue;
							}
						} else {
							$this->createNodeForValueObject($propertyValue, $node, 'flow3:' . $propertyName);
							$queue[] = $propertyValue;
						}
					}
				}
			} elseif ($classSchema->getModelType() === \F3\FLOW3\Persistence\ClassSchema::MODELTYPE_VALUEOBJECT || ($object->FLOW3_Persistence_isNew() || $object->FLOW3_Persistence_isDirty($propertyName))) {
				if (!is_object($propertyValue)) {
					$node->setProperty('flow3:' . $propertyName, $propertyValue, \F3\PHPCR\PropertyType::valueFromType($propertyType));
				}
			}
		}

		if ($classSchema->getModelType() === \F3\FLOW3\Persistence\ClassSchema::MODELTYPE_ENTITY) {
			$object->FLOW3_Persistence_memorizeCleanState();
		}

			// here we loop over the objects. their nodes are already at the
			// right place and have the right name. fancy, eh?
		foreach ($queue as $object) {
			$this->persistObject($object);
		}
	}

	/**
	 * Checks a value given against the expected type. If not matching, an
	 * UnexpectedTypeException is thrown. NULL is always considered valid.
	 *
	 * @param string $expectedType The expected type
	 * @param mixed $value The value to check
	 * @return void
	 * @throws \F3\TYPO3CR\FLOW3\Persistence\Exception\UnexpectedTypeException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function checkPropertyType($expectedType, $value) {
		if ($value === NULL) {
			return;
		}

		if (is_object($value)) {
			if (!($value instanceof $expectedType)) {
				throw new \F3\TYPO3CR\FLOW3\Persistence\Exception\UnexpectedTypeException('Expected property of type ' . $expectedType . ', but got ' . get_class($value), 1244465558);
			}
		} elseif ($expectedType !== gettype($value)) {
			throw new \F3\TYPO3CR\FLOW3\Persistence\Exception\UnexpectedTypeException('Expected property of type ' . $expectedType . ', but got ' . gettype($value), 1244465558);
		}
	}

	/**
	 * Creates a node for the given value object and registers it with the identity map.
	 *
	 * @param object $object The value object for which to create a node
	 * @param \F3\PHPCR\NodeInterface $parentNode
	 * @param string $nodeName The name to use for the object, must be a legal name as per JSR-283
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function createNodeForValueObject($object, \F3\PHPCR\NodeInterface $parentNode, $nodeName) {
		$className = $object->FLOW3_AOP_Proxy_getProxyTargetClassName();
		$nodeTypeName = 'flow3:' . $this->convertClassNameToJCRName($className);
		$node = $parentNode->addNode($nodeName, $nodeTypeName);
		$this->identityMap->registerObject($object, $node->getIdentifier());
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
	protected function createNodeForEntity($object, \F3\PHPCR\NodeInterface $parentNode, $nodeName) {
		$className = $object->FLOW3_AOP_Proxy_getProxyTargetClassName();
		$nodeTypeName = 'flow3:' . $this->convertClassNameToJCRName($className);
		$uuidPropertyName = $this->classSchemata[$className]->getUUIDPropertyName();

		if ($uuidPropertyName !== NULL) {
			$node = $parentNode->addNode($nodeName, $nodeTypeName, $object->FLOW3_AOP_Proxy_getProperty($uuidPropertyName));
		} elseif ($object->FLOW3_AOP_Proxy_getProperty('FLOW3_Persistence_Entity_UUID') !== NULL) {
			$node = $parentNode->addNode($nodeName, $nodeTypeName, $object->FLOW3_AOP_Proxy_getProperty('FLOW3_Persistence_Entity_UUID'));
		} else {
			$node = $parentNode->addNode($nodeName, $nodeTypeName);
		}

		$this->identityMap->registerObject($object, $node->getIdentifier());
	}

	/**
	 * Creates a proxy node pointing to another object's node. Is used for inter-
	 * aggregate references, i.e. when a reference points to another aggregate
	 * root.
	 * If the node alreadyx exists, it will be updated to point to the current
	 * object.
	 *
	 * @param object $object The object to create/update a proxy for
	 * @param \F3\PHPCR\NodeInterface $parentNode
	 * @param string $nodeName The name to use for the object, must be a legal name as per JSR-283
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function createOrUpdateProxyNodeForEntity($object, \F3\PHPCR\NodeInterface $parentNode, $nodeName) {
		if ($parentNode->hasNode($nodeName)) {
			$proxyNode = $parentNode->getNode($nodeName);
		} else {
			$proxyNode = $parentNode->addNode($nodeName, self::NODETYPE_OBJECTPROXY);
		}

		$objectUUID = $this->getIdentifierByObject($object);
		if ($objectUUID !== NULL && !$this->isNewObject($object)) {
			$proxyNode->setProperty('flow3:target', $objectUUID, \F3\PHPCR\PropertyType::REFERENCE);
		} else {
			if ($proxyNode->isNew() === FALSE) {
				$proxyNode->getProperty('flow3:target')->remove();
			}
			$this->incompleteObjectProxyNodes->attach($proxyNode, $object);
		}
	}

	/**
	 * Persists the given value object to $nodeName below $parentNode.
	 *
	 * @param object $object The value object to persist
	 * @param \F3\PHPCR\NodeInterface $parentNode
	 * @param string $nodeName The name to use for the object, must be a legal name as per JSR-283
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function persistValueObject($object, \F3\PHPCR\NodeInterface $parentNode, $nodeName) {
		$className = $object->FLOW3_AOP_Proxy_getProxyTargetClassName();
		$nodeTypeName = 'flow3:' . $this->convertClassNameToJCRName($className);
		$node = $parentNode->addNode($nodeName, $nodeTypeName);

		foreach ($this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()]->getProperties() as $propertyName => $propertyData) {
			$propertyValue = $object->FLOW3_AOP_Proxy_getProperty($propertyName);
			if (is_array($propertyValue)) {
				$this->persistArray($propertyValue, $node, 'flow3:' . $propertyName, $queue);
			} elseif (is_object($propertyValue) && $propertyData['type'] !== 'DateTime') {
				$this->persistValueObject($propertyValue, $node, 'flow3:' . $propertyName);
			} else {
				$node->setProperty('flow3:' . $propertyName, $propertyValue, \F3\PHPCR\PropertyType::valueFromType($propertyData['type']));
			}
		}
	}

	/**
	 * Store an array as a node of type flow3:arrayPropertyProxy, with each
	 * array element becoming a property named like the key and the value.
	 *
	 * Every element not being an object or array will become a property on the
	 * node, arrays will be handled recursively.
	 *
	 * Note: Objects contained in the array will have a node created, properties
	 * on those nodes must be set elsewhere!
	 *
	 * @param array $array The array for which to create a proxy node
	 * @param \F3\PHPCR\NodeInterface $parentNode The node to add the property proxy to
	 * @param string $nodeName The name to use for the object, must be a legal name as per JSR-283
	 * @param array &$queue Found entities are accumulated here.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function persistArray(array $array, \F3\PHPCR\NodeInterface $parentNode, $nodeName, array &$queue) {
		if ($parentNode->hasNode($nodeName)) {
			$node = $parentNode->getNode($nodeName);
		} else {
			$node = $parentNode->addNode($nodeName, self::NODETYPE_ARRAYPROXY);
		}

		foreach ($array as $key => $element) {
			if (is_object($element) && !($element instanceof \DateTime)) {
				if ($this->classSchemata[$element->FLOW3_AOP_Proxy_getProxyTargetClassName()]->getModelType() === \F3\FLOW3\Persistence\ClassSchema::MODELTYPE_ENTITY) {
					if ($this->classSchemata[$element->FLOW3_AOP_Proxy_getProxyTargetClassName()]->isAggregateRoot() === TRUE) {
						$this->createOrUpdateProxyNodeForEntity($element, $node, 'flow3:' . $key);
					} else {
						if ($element->FLOW3_Persistence_isNew()) {
							$this->createNodeForEntity($element, $node, 'flow3:' . $key);
						}
						$queue[] = $element;
					}
				} else {
					$this->persistValueObject($element, $node, 'flow3:' . $key);
				}
			} elseif (is_array($element)) {
				$this->persistArray($element, $node, 'flow3:' . $key, $queue);
			} else {
				$node->setProperty('flow3:' . $key, $element, \F3\PHPCR\PropertyType::valueFromType(gettype($element)));
			}
		}
	}

	/**
	 * Store an array as a node of type flow3:arrayPropertyProxy, with each
	 * array element becoming a property named like the key and the value.
	 *
	 * Every element not being an object or array will become a property on the
	 * node, arrays will be handled recursively.
	 *
	 * Note: Objects contained in the SplObjectStorage will have a node created,
	 * properties on those nodes must be set elsewhere!
	 *
	 * @param \SplObjectStorage $objectStorage The SplObjectStorage to persist
	 * @param \F3\PHPCR\NodeInterface $parentNode The node to add the proxy to
	 * @param string $nodeName The name to use for the proxy, must be a legal name as per JSR-283
	 * @param array &$queue Found entities are accumulated here.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo add persisting information attached to contained objects
	 */
	protected function persistSplObjectStorage(\SplObjectStorage $objectStorage, \F3\PHPCR\NodeInterface $parentNode, $nodeName, array &$queue) {
		if ($parentNode->hasNode($nodeName)) {
			$node = $parentNode->getNode($nodeName);
		} else {
			$node = $parentNode->addNode($nodeName, self::NODETYPE_SPLOBJECTSTORAGEPROXY);
		}

		foreach ($objectStorage as $object) {
			$itemNode = $node->addNode('flow3:item', 'nt:unstructured');

			if ($object instanceof \DateTime) {
				$itemNode->setProperty('flow3:object', $element, \F3\PHPCR\PropertyType::DATE);
			} else {
				if ($this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()]->getModelType() === \F3\FLOW3\Persistence\ClassSchema::MODELTYPE_ENTITY) {
					if ($this->classSchemata[$object->FLOW3_AOP_Proxy_getProxyTargetClassName()]->isAggregateRoot() === TRUE) {
						$this->createOrUpdateProxyNodeForEntity($object, $itemNode, 'flow3:object');
					} else {
						if ($object->FLOW3_Persistence_isNew()) {
							$this->createNodeForEntity($object, $itemNode, 'flow3:object');
						}
						$queue[] = $object;
					}
				} else {
					$this->persistValueObject($object, $itemNode, 'flow3:object');
				}
			}
		}
	}

	/**
	 * Iterate over object proxy nodes that are not complete and tries to
	 * finalize them (i.e. set their target object node).
	 *
	 * If a proxy cannot be finalized it will be removed and this is logged.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function finalizeObjectProxyNodes() {
		foreach ($this->incompleteObjectProxyNodes as $proxyNode) {
			$object = $this->incompleteObjectProxyNodes->getInfo();
			if ($this->isNewObject($object)) {
				throw new \F3\TYPO3CR\FLOW3\Persistence\Exception\DanglingAggregateRootObjectException('Found an instance of "' . get_class($object) . '" for "' . $proxyNode->getPath() . '" being aggregate root but not being persisted.', 1240200821);
			} else {
				$objectUUID = $this->getIdentifierByObject($object);
				if ($objectUUID !== NULL) {
					$proxyNode->setProperty('flow3:target', $objectUUID, \F3\PHPCR\PropertyType::REFERENCE);
				} else {
					throw new \F3\TYPO3CR\FLOW3\Persistence\Exception\UnknownObjectException('Could not resolve UUID for object reference in object proxy node "' . $proxyNode->getPath() . '".', 1240200392);
				}
			}
		}
	}

	/**
	 * Iterate over deleted objects and process them
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function processDeletedObjects() {
		foreach ($this->deletedObjects as $object) {
			if ($this->identityMap->hasObject($object)) {
				$node = $this->session->getNodeByIdentifier($this->identityMap->getIdentifierByObject($object));
				$node->remove();
				$this->identityMap->unregisterObject($object);
			}
		}
		$this->deletedObjects = new \SplObjectStorage();
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
