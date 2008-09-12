<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR::FLOW3::Persistence;

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
class Backend implements F3::FLOW3::Persistence::BackendInterface {

	/**
	 * @var F3::FLOW3::Reflection::Service
	 */
	protected $reflectionService;

	/**
	 * @var F3::PHPCR::SessionInterface
	 */
	protected $session;

	/**
	 * @var F3::PHPCR::NodeInterface
	 */
	protected $baseNode;

	/**
	 * @var array
	 */
	protected $classSchemata;

	/**
	 * @var F3::TYPO3CR::FLOW3::Persistence::IdentityMap
	 */
	protected $identityMap;

	/**
	 * @var array
	 */
	protected $newObjects;

	/**
	 * @var array
	 */
	protected $updatedObjects;

	/**
	 * @var array
	 */
	protected $deletedObjects;

	/**
	 * Injects A Reflection Service instance used for processing objects
	 *
	 * @param F3::FLOW3::Reflection::Service $reflectionService
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectReflectionService(F3::FLOW3::Reflection::Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the Content Repository used to persist data
	 *
	 * @param F3::PHPCR::RepositoryInterface $repository
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectContentRepository(F3::PHPCR::RepositoryInterface $repository) {
		$this->session = $repository->login();
	}

	/**
	 * Injects the identity map
	 *
	 * @param F3::TYPO3CR::FLOW3::Persistence::IdentityMap $identityMap
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectIdentityMap(F3::TYPO3CR::FLOW3::Persistence::IdentityMap $identityMap) {
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
		$nodeTypeManager = $this->session->getWorkspace()->getNodeTypeManager();

		if (!$this->session->getRootNode()->hasNode('flow3:persistence/flow3:objects')) {
			$persistenceNode = $this->session->getRootNode()->addNode('flow3:persistence');
			$this->baseNode = $persistenceNode->addNode('flow3:objects');
		} else {
			$this->baseNode = $this->session->getRootNode()->getNode('flow3:persistence/flow3:objects');
		}

		foreach($this->classSchemata as $schema) {
			$className = str_replace('::', '_', $schema->getClassName());
			if ($nodeTypeManager->hasNodeType($className)) {
				$nodeTypeManager->unregisterNodeType($className);
			}
			$nodeTypeTemplate = $nodeTypeManager->createNodeTypeTemplate();
			$nodeTypeTemplate->setName($className);
			$nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
		}
	}


	/**
	 * Sets the new objects
	 *
	 * @param array $objects
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setNewObjects(array $objects) {
		$this->newObjects = $objects;
	}


	/**
	 * Sets the updated objects
	 *
	 * @param array $objects
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUpdatedObjects(array $objects) {
		$this->updatedObjects = $objects;
	}


	/**
	 * Sets the deleted objects
	 *
	 * @param array $objects
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setDeletedObjects(array $objects) {
		$this->deleteObjects = $objects;
	}

	/**
	 * Commits the current persistence session
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function commit() {
		foreach ($this->newObjects as $object) {
			$this->processNewObject($object);
		}
		foreach ($this->updatedObjects as $object) {
			$this->processUpdatedObject($object);
		}

		$this->session->save();
	}

	/**
	 * Stores, updates or removes an object's corresponding node in the repository
	 *
	 * @param object $object
	 * @return string Identifier for the corresponding node
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function processObject($object) {
		if (array_search($object, $this->newObjects)) {
			return $this->processNewObject($object);
		} elseif (array_search($object, $this->updatedObjects)) {
			return $this->processUpdatedObject($object);
		} elseif ($this->identityMap->hasObject(spl_object_hash($object))) {
			return $this->identityMap->getIdentifier(spl_object_hash($object));
		} else {
			throw new F3::FLOW3::Persistence::Exception('processObject(' . get_class($object) . ') called for object I cannot handle.', 1218478184);
		}
	}

	/**
	 * Stores an object as node in the repository
	 *
	 * @param object $object The object to store, update or delete
	 * @return string The identifier for the node representing the object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo make sure setPropertiesForObject is called only once per commit and object
	 */
	protected function processNewObject($object) {
		$objectHash = spl_object_hash($object);
		if ($this->identityMap->hasObject($objectHash)) {
			$identifier = $this->identityMap->getIdentifier($objectHash);
		} else {
			$className = $object->AOPProxyGetProxyTargetClassName();
			if (!$this->baseNode->hasNode('flow3:' . $className)) {
				$this->baseNode->addNode('flow3:' . $className);
			}
			$identifierProperty = $this->classSchemata[$className]->getIdentifierProperty();
			if ($identifierProperty !== NULL) {
				$node = $this->baseNode->getNode('flow3:' . $className)->addNode('flow3:' . $className . 'Instance', 'flow3:' . $className, $object->AOPProxyGetProperty($identifierProperty));
			} else {
				$node = $this->baseNode->getNode('flow3:' . $className)->addNode('flow3:' . $className . 'Instance', 'flow3:' . $className);
			}
			$identifier = $node->getIdentifier();
			$this->identityMap->registerObject($objectHash, $identifier);
			$this->setPropertiesForObject($node, $object);
		}
		unset($this->newObjects[$objectHash]);

		return $identifier;
	}

	/**
	 * Updates an object stored as node in the repository
	 *
	 * @param object $object The object to store, update or delete
	 * @return string The identifier for the node representing the object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo make sure setPropertiesForObject is called only once per commit and object
	 */
	protected function processUpdatedObject($object) {
		$objectHash = spl_object_hash($object);
		if ($this->identityMap->hasObject($objectHash)) {
			$identifier = $this->identityMap->getIdentifier($objectHash);
			$node = $this->session->getNodeByIdentifier($identifier);
			$this->setPropertiesForObject($node, $object);
		} else {
			throw new F3::FLOW3::Persistence::Exception('How am I supposed to update an object I do not know about?', 1218478512);
		}
		unset($this->updatedObjects[$objectHash]);

		return $identifier;
	}

	/**
	 * Iterates over the properties of an object and sets them on the given node
	 *
	 * @param F3::PHPCR::NodeInterface $node
	 * @param object $object
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function setPropertiesForObject(F3::PHPCR::NodeInterface $node, $object) {
		$className = $object->AOPProxyGetProxyTargetClassName();
		foreach ($this->classSchemata[$className]->getProperties() as $propertyName => $propertyType) {
			$value = $object->AOPProxyGetProperty($propertyName);

			if ($propertyType === 'array') {
				if (count($value) == 0) {
						// delete empty array properties
					$value = NULL;
					$type = F3::PHPCR::PropertyType::UNDEFINED;
				} elseif (is_object(current($value)) && $this->reflectionService->isPropertyTaggedWith($className, $propertyName, 'reference')) {
					$value = $this->processObjectArray($value);
					$type = F3::PHPCR::PropertyType::REFERENCE;
				} else {
					$type = F3::PHPCR::PropertyType::valueFromType(gettype(current($value)));
				}
			} elseif (is_object($value) && $this->reflectionService->isPropertyTaggedWith($className, $propertyName, 'reference')) {
				$value = $this->processObject($value);
				$type = F3::PHPCR::PropertyType::REFERENCE;
			} else {
				$type = F3::PHPCR::PropertyType::valueFromType($propertyType);
			}

			$node->setProperty($propertyName, $value, $type);
		}
	}

	/**
	 * Returns an indentifier array for the given object array that can be used
	 * in a multi-valued REFERENCE property
	 *
	 * @param array $objects
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function processObjectArray(array $objects) {
		$identifiers = array();
		foreach ($objects as $object) {
			$identifiers[] = $this->processObject($object);
		}
		return $identifiers;
	}

}

?>