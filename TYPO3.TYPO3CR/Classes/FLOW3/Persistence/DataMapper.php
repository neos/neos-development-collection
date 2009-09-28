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
 * A data mapper to map nodes to objects
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DataMapper {

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Object\Builder
	 */
	protected $objectBuilder;

	/**
	 * @var \F3\TYPO3CR\FLOW3\Persistence\IdentityMap
	 */
	protected $identityMap;

	/**
	 * @var \F3\FLOW3\Persistence\Session
	 */
	protected $persistenceSession;

	/**
	 * @var \F3\FLOW3\Reflection\Service
	 */
	protected $reflectionService;

	/**
	 * Injects the object manager
	 *
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the object builder
	 *
	 * @param \F3\FLOW3\Object\Builder $objectBuilder
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectObjectBuilder(\F3\FLOW3\Object\Builder $objectBuilder) {
		$this->objectBuilder = $objectBuilder;
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
	 * Injects the persistence session
	 *
	 * @param \F3\FLOW3\Persistence\Session $persistenceSession The persistence session
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectPersistenceSession(\F3\FLOW3\Persistence\Session $persistenceSession) {
		$this->persistenceSession = $persistenceSession;
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
	 * Maps the (aggregate root) nodes and registers them as reconstituted
	 * with the session.
	 *
	 * @param \F3\PHPCR\NodeIteratorInterface $nodes
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function map(\F3\PHPCR\NodeIteratorInterface $nodes) {
		$objects = array();
		foreach ($nodes as $node) {
			$objects[] = $this->mapSingleNode($node);
		}

		return $objects;
	}

	/**
	 * Maps a single node into the object it represents
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapSingleNode(\F3\PHPCR\NodeInterface $node) {
		if ($this->identityMap->hasIdentifier($node->getIdentifier())) {
			$object = $this->identityMap->getObjectByIdentifier($node->getIdentifier());
		} else {
			$explodedNodeTypeName = explode(':', $node->getPrimaryNodeType()->getName(), 2);
			$className = str_replace('_', '\\', $explodedNodeTypeName[1]);
			$classSchema = $this->reflectionService->getClassSchema($className);
			$objectConfiguration = $this->objectManager->getObjectConfiguration($className);

			$object = $this->objectBuilder->createEmptyObject($className, $objectConfiguration);
			$this->identityMap->registerObject($object, $node->getIdentifier());

			$this->objectBuilder->reinjectDependencies($object, $objectConfiguration);
			$this->thawProperties($object, $node, $classSchema);
			$object->FLOW3_Persistence_memorizeCleanState();
			$this->persistenceSession->registerReconstitutedObject($object);
		}

		return $object;
	}

	/**
	 * Sets the given properties on the object.
	 *
	 * @param \F3\FLOW3\AOP\ProxyInterface $object The object to set properties on
	 * @param \F3\PHPCR\NodeInterface $node
	 * @param \F3\FLOW3\Reflection\ClassSchema $classSchema
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function thawProperties(\F3\FLOW3\AOP\ProxyInterface $object, \F3\PHPCR\NodeInterface $node, \F3\FLOW3\Reflection\ClassSchema $classSchema) {
		foreach ($classSchema->getProperties() as $propertyName => $propertyData) {
			$propertyValue = NULL;
			switch ($propertyData['type']) {
				case 'integer':
				case 'int':
				case 'float':
				case 'boolean':
				case 'string':
				case 'DateTime':
					if ($node->hasProperty('flow3:' . $propertyName)) {
						$property = $node->getProperty('flow3:' . $propertyName);
						$propertyValue = $this->getNativeValue($property);
					}
				break;
				case 'array':
					if ($node->hasNode('flow3:' . $propertyName)) {
						$propertyValue = $this->mapArrayProxyNode($node->getNode('flow3:' . $propertyName));
					}
				break;
				case 'SplObjectStorage':
					if ($node->hasNode('flow3:' . $propertyName)) {
						$propertyValue = $this->mapSplObjectStorageProxyNode($object, $propertyName, $node->getNode('flow3:' . $propertyName), $propertyData['lazy']);
					}
				break;
					// we have an object to handle...
				default:
					if ($node->hasNode('flow3:' . $propertyName)) {
						$propertyNode = $node->getNode('flow3:' . $propertyName);
						if ($propertyNode->getPrimaryNodeType()->getName() === \F3\TYPO3CR\FLOW3\Persistence\Backend::NODETYPE_OBJECTPROXY) {
							$propertyValue = $this->mapObjectProxyNode($propertyNode);
						} else {
							$propertyValue = $this->mapSingleNode($propertyNode);
						}
					}
				break;
			}

			if ($propertyValue !== NULL) {
				$object->FLOW3_AOP_Proxy_setProperty($propertyName, $propertyValue);
			}
		}

		$uuidPropertyName = $classSchema->getUUIDPropertyName();
		$object->FLOW3_AOP_Proxy_setProperty(($uuidPropertyName !== NULL ? $uuidPropertyName : 'FLOW3_Persistence_Entity_UUID'), $node->getIdentifier());
	}

	/**
	 * Maps an array proxy node back to a native PHP array
	 *
	 * @param \F3\PHPCR\NodeInterface $proxyNode
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo remove the check on the node/property names and use name pattern
	 */
	protected function mapArrayProxyNode(\F3\PHPCR\NodeInterface $proxyNode) {
		if ($proxyNode->getPrimaryNodeType()->getName() !== \F3\TYPO3CR\FLOW3\Persistence\Backend::NODETYPE_ARRAYPROXY) {
			throw new \F3\TYPO3CR\FLOW3\Persistence\Exception\UnsupportedTypeException('Arrays can only be mapped back from nodes of type ' . \F3\TYPO3CR\FLOW3\Persistence\Backend::NODETYPE_ARRAYPROXY, 1227705954);
		}

		$array = array();
		$objectNodes = $proxyNode->getNodes();
		foreach ($objectNodes as $objectNode) {
			$objectNodeName = explode(':', $objectNode->getName(), 2);
			$nodeTypeName = $objectNode->getPrimaryNodeType()->getName();
			if ($nodeTypeName === \F3\TYPO3CR\FLOW3\Persistence\Backend::NODETYPE_ARRAYPROXY) {
				$array[$objectNodeName[1]] = $this->mapArrayProxyNode($objectNode);
			} elseif ($nodeTypeName === \F3\TYPO3CR\FLOW3\Persistence\Backend::NODETYPE_OBJECTPROXY) {
				$array[$objectNodeName[1]] = $this->mapObjectProxyNode($objectNode);
			} elseif ($objectNodeName[0] === 'flow3') {
				$array[$objectNodeName[1]] = $this->mapSingleNode($objectNode);
			}
		}

		$properties = $proxyNode->getProperties();
		foreach ($properties as $property) {
			$propertyName = explode(':', $property->getName(), 2);
			if ($propertyName[0] === 'flow3') {
				$array[$propertyName[1]] = $this->getNativeValue($property);
			}
		}

		return $array;
	}

	/**
	 * Maps an SplObjectStorage proxy node back to an SplObjectStorage
	 *
	 * @param object $parent The parent object for the mapping result
	 * @param string $propertyName The target property name for the mapping result
	 * @param \F3\PHPCR\NodeInterface $proxyNode
	 * @param boolean $lazy Whether to create a LazyLoadingProxy for the property
	 * @return \SplObjectStorage|\F3\FLOW3\Persistence\LazyLoadingProxy
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo restore information attached to objects
	 * @todo allow to switch between eager and lazy loading
	 */
	protected function mapSplObjectStorageProxyNode($parent, $propertyName, \F3\PHPCR\NodeInterface $proxyNode, $lazy = FALSE) {
		if ($proxyNode->getPrimaryNodeType()->getName() !== \F3\TYPO3CR\FLOW3\Persistence\Backend::NODETYPE_SPLOBJECTSTORAGEPROXY) {
			throw new \F3\TYPO3CR\FLOW3\Persistence\Exception\UnsupportedTypeException('SplObjectStorage can only be mapped back from nodes of type ' . \F3\TYPO3CR\FLOW3\Persistence\Backend::NODETYPE_SPLOBJECTSTORAGEPROXY, 1236166559);
		}

		if ($lazy === FALSE) {
			$objectStorage = new \SplObjectStorage();

			$itemNodes = $proxyNode->getNodes();
			foreach ($itemNodes as $itemNode) {
				$objectNode = $itemNode->getNode('flow3:object');
				if ($objectNode->getPrimaryNodeType()->getName() === \F3\TYPO3CR\FLOW3\Persistence\Backend::NODETYPE_OBJECTPROXY) {
					$object = $this->mapObjectProxyNode($objectNode);
				} else {
					$object = $this->mapSingleNode($objectNode);
				}

				$objectStorage->attach($object);
			}
		} else {
			$dataMapper = $this; // make available to closure...
			$objectStorage = new \F3\FLOW3\Persistence\LazyLoadingProxy(
				$parent,
				$propertyName,
				function() use ($proxyNode, $dataMapper) {
					$objectStorage = new \SplObjectStorage();
					$itemNodes = $proxyNode->getNodes();
					foreach ($itemNodes as $itemNode) {
						$objectNode = $itemNode->getNode('flow3:object');
						if ($objectNode->getPrimaryNodeType()->getName() === \F3\TYPO3CR\FLOW3\Persistence\Backend::NODETYPE_OBJECTPROXY) {
							$object = $dataMapper->mapObjectProxyNode($objectNode);
						} else {
							$object = $dataMapper->mapSingleNode($objectNode);
						}

						$objectStorage->attach($object);
					}
					return $objectStorage;
				}
			);
		}

		return $objectStorage;
	}

	/**
	 * Fetches the object pointed to by the object proxy node.
	 *
	 * @param \F3\PHPCR\NodeInterface $proxyNode
	 * @return object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapObjectProxyNode(\F3\PHPCR\NodeInterface $proxyNode) {
		return $this->mapSingleNode($proxyNode->getProperty('flow3:target')->getNode());
	}

	/**
	 * Determines the type of a Value and returns the value as corresponding
	 * native PHP type.
	 *
	 * @param \F3\PHPCR\PropertyInterface $property
	 * @param integer $type A constant from \F3\PHPCR\PropertyType
	 * @return mixed
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getNativeValue(\F3\PHPCR\PropertyInterface $property) {
		$value = $property->getValue();
		switch ($property->getType()) {
			case \F3\PHPCR\PropertyType::BOOLEAN:
				$value = $value->getBoolean();
				break;
			case \F3\PHPCR\PropertyType::DATE:
				$value = $value->getDate();
				break;
			case \F3\PHPCR\PropertyType::DECIMAL:
			case \F3\PHPCR\PropertyType::DOUBLE:
				$value = $value->getDouble();
				break;
			case \F3\PHPCR\PropertyType::LONG:
				$value = $value->getLong();
				break;
			case \F3\PHPCR\PropertyType::STRING:
				$value = $value->getString();
				break;
			default:
				throw new \F3\TYPO3CR\FLOW3\Persistence\Exception\UnsupportedTypeException('The encountered value type (' . \F3\PHPCR\PropertyType::nameFromValue($property->getType()) . ') cannot be mapped.', 1217843827);
				break;
		}

		return $value;
	}
}

?>
