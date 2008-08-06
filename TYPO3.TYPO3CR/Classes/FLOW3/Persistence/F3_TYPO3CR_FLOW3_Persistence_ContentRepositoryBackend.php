<?php
declare(ENCODING = 'utf-8');

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
class F3_TYPO3CR_FLOW3_Persistence_ContentRepositoryBackend implements F3_FLOW3_Persistence_BackendInterface {

	/**
	 * @var F3_FLOW3_Reflection_Service
	 */
	protected $reflectionService;

	/**
	 * @var F3_PHPCR_SessionInterface
	 */
	protected $session;

	/**
	 * @var F3_PHPCR_NodeInterface
	 */
	protected $baseNode;

	/**
	 * @var array
	 */
	protected $classSchemata;

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
	 * @var array
	 */
	protected $identityMap = array();

	/**
	 * Injects A Reflection Service instance used for processing objects
	 *
	 * @param F3_FLOW3_Reflection_Service $reflectionService
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @required
	 */
	public function injectReflectionService(F3_FLOW3_Reflection_Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the Content Repository used to persist data
	 *
	 * @param F3_PHPCR_RepositoryInterface $repository
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @required
	 */
	public function injectContentRepository(F3_PHPCR_RepositoryInterface $repository) {
		$this->session = $repository->login();
	}

	/**
	 * Initializes the backend
	 *
	 * @param array $classSchemata the class schemata the backend will be handling
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initialize(array $classSchemata) {
		$nodeTypeManager = $this->session->getWorkspace()->getNodeTypeManager();

		if (!$this->session->getRootNode()->hasNode('flow3:persistence/flow3:objects')) {
			$persistenceNode = $this->session->getRootNode()->addNode('flow3:persistence');
			$this->baseNode = $persistenceNode->addNode('flow3:objects');
		} else {
			$this->baseNode = $this->session->getRootNode()->getNode('flow3:persistence/flow3:objects');
		}

		foreach($classSchemata as $schema) {
			if ($nodeTypeManager->hasNodeType($schema->getClassName())) {
				$nodeTypeManager->unregisterNodeType($schema->getClassName());
			}
			$nodeTypeTemplate = $nodeTypeManager->createNodeTypeTemplate();
			$nodeTypeTemplate->setName($schema->getClassName());
			$nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
		}
		$this->classSchemata = $classSchemata;
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
			$this->processObject($object);
		}

		$this->session->save();
	}

	/**
	 * Stores, updates or removes an object's corresponding node from the repository
	 *
	 * @param object $object The object to store, update or delete
	 * @return string The identifier for the node representing the object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo make sure setPropertiesForObject is called only once per commit and object
	 */
	protected function processObject($object) {
		$objectHash = spl_object_hash($object);
		if (key_exists($objectHash, $this->identityMap)) {
			$identifier = $this->identityMap[$objectHash];
			$node = $this->session->getNodeByIdentifier($identifier);
		} else {
			$className = $object->AOPProxyGetProxyTargetClassName();
			if (!$this->baseNode->hasNode('flow3:' . $className)) {
				$this->baseNode->addNode('flow3:' . $className);
			}
			$node = $this->baseNode->getNode('flow3:' . $className)->addNode('flow3:' . $className . 'Instance', 'flow3:' . $className);
			$identifier = $node->getIdentifier();
			$this->identityMap[$objectHash] = $identifier;
		}
		$this->setPropertiesForObject($node, $object);

		return $identifier;
	}

	/**
	 * Iterates over the properties of an object and sets them on the given node
	 *
	 * @param F3_PHPCR_NodeInterface $node
	 * @param object $object
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function setPropertiesForObject(F3_PHPCR_NodeInterface $node, $object) {
		$className = $object->AOPProxyGetProxyTargetClassName();
		foreach ($this->classSchemata[$className]->getProperties() as $propertyName => $propertyType) {
			$value = $object->AOPProxyGetProperty($propertyName);

			if ($propertyType === 'array' && count($value)) {
				if (count($value) && is_object(current($value)) && $this->reflectionService->isPropertyTaggedWith($className, $propertyName, 'reference')) {
					$value = $this->reduceObjectArrayToIdentifierArray($value);
					$type = F3_PHPCR_PropertyType::REFERENCE;
				} elseif (count($value)) {
					$type = $this->typeStringToInteger(gettype(current($value)));
				} else {
						// delete empty array properties
					$value = NULL;
					$type = F3_PHPCR_PropertyType::UNDEFINED;
				}
			} elseif (is_object($value) && $this->reflectionService->isPropertyTaggedWith($className, $propertyName, 'reference')) {
				$value = $this->processObject($value);
				$type = F3_PHPCR_PropertyType::REFERENCE;
			} else {
				$type = $this->typeStringToInteger($propertyType);
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
	protected function reduceObjectArrayToIdentifierArray(array $objects) {
		$identifiers = array();
		foreach ($objects as $object) {
			$identifiers[] = $this->processObject($object);
		}
		return $identifiers;
	}

	/**
	 * Returns a constant from F3_PHPCR_PropertyType for the given PHP type name
	 *
	 * @param string $type
	 * @return integer
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function typeStringToInteger($typeName) {
		switch (F3_PHP6_Functions::strtolower($typeName)) {
			case 'string':
				$typeNumber = F3_PHPCR_PropertyType::STRING;
				break;
			case 'boolean':
				$typeNumber = F3_PHPCR_PropertyType::BOOLEAN;
				break;
			case 'integer':
				$typeNumber = F3_PHPCR_PropertyType::LONG;
				break;
			case 'float':
			case 'double':
				$typeNumber = F3_PHPCR_PropertyType::DOUBLE;
				break;
			case 'datetime':
				$typeNumber = F3_PHPCR_PropertyType::DATE;
				break;
			default:
				$typeNumber = F3_PHPCR_PropertyType::UNDEFINED;
		}
		return $typeNumber;
	}

}

?>