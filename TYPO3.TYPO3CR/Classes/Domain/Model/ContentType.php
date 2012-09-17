<?php
namespace TYPO3\TYPO3CR\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A Content Type
 *
 * @FLOW3\Scope("prototype")
 */
class ContentType {

	/**
	 * Name of this content type. Example: "TYPO3CR:Folder"
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Configuration for this content type, can be an arbitrarily nested array.
	 *
	 * @var array
	 */
	protected $configuration;

	/**
	 * Content types this content type directly inherits from
	 *
	 * @var array<\TYPO3\TYPO3CR\Domain\Model\ContentType>
	 */
	protected $declaredSuperTypes;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Constructs this content type
	 *
	 * @param string $name Name of the content type
	 * @param array<\TYPO3\TYPO3CR\Domain\Model\ContentType> $declaredSuperTypes a list of declared super types
	 * @param array $configuration the configuration for this content type which is defined in the schema
	 */
	public function __construct($name, array $declaredSuperTypes, array $configuration) {
		$this->name = $name;

		foreach ($declaredSuperTypes as $type) {
			if (!$type instanceof \TYPO3\TYPO3CR\Domain\Model\ContentType) {
				throw new \InvalidArgumentException('$types must be an array of ContentType objects', 1291300950);
			}
		}
		$this->declaredSuperTypes = $declaredSuperTypes;

		$this->configuration = $configuration;
	}

	/**
	 * Returns the name of this content type
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the direct, explicitly declared super types
	 * of this content type.
	 *
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\ContentType>
	 */
	public function getDeclaredSuperTypes() {
		return $this->declaredSuperTypes;
	}

	/**
	 * If this content type or any of the direct or indirect super types
	 * has the given name.
	 *
	 * @param string $contentTypeName
	 * @return boolean TRUE if this content type is of the given kind, otherwise FALSE
	 */
	public function isOfType($contentTypeName) {
		if ($contentTypeName === $this->name) {
			return TRUE;
		}
		foreach ($this->declaredSuperTypes as $superType) {
			if ($superType->isOfType($contentTypeName) === TRUE) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Get the full configuration of the content type. Should only be used internally.
	 * Instead, use the get* / has* methods which exist for every configuration property.
	 *
	 * @return array
	 */
	public function getConfiguration() {
		return $this->configuration;
	}

	/**
	 * Get the human-readable label of this content type
	 *
	 * @return string
	 * @api
	 */
	public function getLabel() {
		return (isset($this->configuration['label']) ? $this->configuration['label'] : '');
	}

	/**
	 * Return the node label generator class for the given node
	 *
	 * @return NodeLabelGeneratorInterface
	 */
	public function getNodeLabelGenerator() {
		if (isset($this->configuration['nodeLabelGenerator'])) {
			$nodeLabelGeneratorClassName = $this->configuration['nodeLabelGenerator'];
		} else {
			$nodeLabelGeneratorClassName = 'TYPO3\TYPO3CR\Domain\Model\DefaultNodeLabelGenerator';
		}
		return $this->objectManager->get($nodeLabelGeneratorClassName);
	}

	/**
	 * Return the array with the defined properties. The key is the property name,
	 * the value the property configuration. There are no guarantees on how the
	 * property configuration looks like.
	 *
	 * @return array
	 * @api
	 */
	public function getProperties() {
		return (isset($this->configuration['properties']) ? $this->configuration['properties'] : array());
	}

	/**
	 * Alias for getName().
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->getName();
	}

	/**
	 * Magic get* and has* method for all properties inside $configuration.
	 *
	 * @param string $methodName
	 * @param array $arguments
	 * @return mixed
	 * @api
	 */
	public function __call($methodName, array $arguments) {
		if (substr($methodName, 0, 3) === 'get') {
			$propertyName = lcfirst(substr($methodName, 3));
			if (isset($this->configuration[$propertyName])) {
				return $this->configuration[$propertyName];
			}
		} elseif (substr($methodName, 0, 3) === 'has') {
			$propertyName = lcfirst(substr($methodName, 3));
			return isset($this->configuration[$propertyName]);
		}

		trigger_error('Call to undefined method ' . get_class($this) . '::' . $methodName, E_USER_ERROR);
	}
}
?>