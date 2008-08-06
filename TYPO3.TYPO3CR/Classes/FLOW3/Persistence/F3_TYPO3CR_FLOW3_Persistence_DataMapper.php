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
 * @version $Id:$
 */

/**
 * A data mapper to map nodes to objects
 *
 * @package TYPO3CR
 * @subpackage FLOW3
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_FLOW3_Persistence_DataMapper {

	/**
	 * @var F3_FLOW3_Component_ManagerInterface
	 */
	protected $componentManager;

	/**
	 * @var F3_FLOW3_Component_ObjectBuilder
	 */
	protected $componentObjectBuilder;

	/**
	 * @var F3_PHPCR_SessionInterface
	 */
	protected $session;

	/**
	 * Injects a Component Manager
	 *
	 * @param F3_FLOW3_Component_ManagerInterface $componentManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectComponentManager(F3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
	}

	/**
	 * Injects a Component Object Builder
	 *
	 * @param F3_FLOW3_Component_ObjectBuilder $componentObjectBuilder
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectComponentObjectBuilder(F3_FLOW3_Component_ObjectBuilder $componentObjectBuilder) {
		$this->componentObjectBuilder = $componentObjectBuilder;
	}

	/**
	 * Injects a Content Repository instance used to get the current session from
	 *
	 * @param F3_PHPCR_RepositoryInterface $contentRepository
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectContentRepository(F3_PHPCR_RepositoryInterface $contentRepository) {
		$this->session = $contentRepository->login();
	}

	/**
	 * Maps the nodes
	 *
	 * @param F3_PHPCR_NodeIteratorInterface $nodes
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function map(F3_PHPCR_NodeIteratorInterface $nodes) {
		$objects = array();
		foreach ($nodes as $node) {
			$objects[] = $this->mapSingleNode($node);
		}

		return $objects;
	}

	/**
	 * Maps a single node into the object it represents
	 *
	 * @param F3_PHPCR_NodeInterface $node
	 * @return object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function mapSingleNode(F3_PHPCR_NodeInterface $node) {
		$className = array_pop(explode(':', $node->getPrimaryNodeType()->getName()));
		$componentConfiguration = $this->componentManager->getComponentConfiguration($className);
		$properties = array();
		foreach ($node->getProperties() as $property) {
			$properties[$property->getName()] = $this->getPropertyValue($property);
		}
		return $this->componentObjectBuilder->reconstituteComponentObject($className, $componentConfiguration, $properties);
	}

	/**
	 * Determines the type of a property and returns the value as corresponding native PHP type
	 *
	 * @param F3_PHPCR_PropertyInterface $property
	 * @return mixed
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo replace try/catch block with check against the PropertyDefinition when implemented
	 */
	protected function getPropertyValue(F3_PHPCR_PropertyInterface $property) {
		try {
			$values = $property->getValues();
			$propertyValue = array();
			foreach ($values as $value) {
				$propertyValue[] = $this->getValueValue($value, $property->getType());
			}
		} catch (F3_PHPCR_ValueFormatException $e) {
			$propertyValue = $this->getValueValue($property->getValue(), $property->getType());
		}

		return $propertyValue;
	}

	/**
	 * Determines the type of a Value and returns the value as corresponding native PHP type
	 *
	 * @param F3_PHPCR_ValueInterface $value
	 * @return mixed
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getValueValue(F3_PHPCR_ValueInterface $value, $type) {
		switch ($type) {
			case F3_PHPCR_PropertyType::BOOLEAN:
				$value = $value->getBoolean();
				break;
			case F3_PHPCR_PropertyType::DATE:
				$value = $value->getDate();
				break;
			case F3_PHPCR_PropertyType::DECIMAL:
			case F3_PHPCR_PropertyType::DOUBLE:
				$value = $value->getDouble();
				break;
			case F3_PHPCR_PropertyType::LONG:
				$value = $value->getLong();
				break;
			case F3_PHPCR_PropertyType::STRING:
				$value = $value->getString();
				break;
			case F3_PHPCR_PropertyType::REFERENCE:
				$value = $this->mapSingleNode($this->session->getNodeByIdentifier($value->getString()));
				break;
			default:
				throw new F3_TYPO3CR_FLOW3_Persistence_Exception_UnsupportedTypeException('The encountered value type (' . F3_PHPCR_PropertyType::nameFromValue($value->getType()) . ') cannot be mapped.', 1217843827);
				break;
		}

		return $value;
	}
}

?>