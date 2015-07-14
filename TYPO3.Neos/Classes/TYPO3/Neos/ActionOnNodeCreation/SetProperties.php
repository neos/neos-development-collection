<?php
namespace TYPO3\Neos\ActionOnNodeCreation;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Neos\Service\NodeOperations;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Set properties of freshly created node based on configuration
 */
class SetProperties extends AbstractActionOnNodeCreation {

	/**
	 * @Flow\Inject
	 * @var PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @Flow\Inject
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Execute the action (e.g. change properties or create child nodes)
	 *
	 * @param NodeInterface $node
	 * @param array $options
	 * @return void
	 */
	public function execute(NodeInterface $node, array $options) {
		if (isset($options['properties']) && is_array($options['properties'])) {
			$nodeType = $node->getNodeType();
			$nodeTypeProperties = $nodeType->getProperties();
			foreach ($options['properties'] as $nodePropertyName => $nodePropertyValue) {
				$nodePropertyType = isset($nodeTypeProperties[$nodePropertyName]['type']) ? $nodeTypeProperties[$nodePropertyName]['type'] : NULL;
				switch ($nodePropertyType) {
					case 'reference':
						$nodePropertyValue = $context->getNodeByIdentifier($nodePropertyValue);
					break;
					case 'references':
						$nodeIdentifiers = json_decode($nodePropertyValue);
						$nodePropertyValue = array();
						if (is_array($nodeIdentifiers)) {
							foreach ($nodeIdentifiers as $nodeIdentifier) {
								$referencedNode = $context->getNodeByIdentifier($nodeIdentifier);
								if ($referencedNode !== NULL) {
									$nodePropertyValue[] = $referencedNode;
								}
							}
						} else {
							throw new TypeConverterException(sprintf('node type "%s" expects an array of identifiers for its property "%s"', $nodeType->getName(), $nodePropertyName), 1383587419);
						}
					break;
					case 'DateTime':
						if ($nodePropertyValue !== '') {
							$nodePropertyValue = \DateTime::createFromFormat(\DateTime::W3C, $nodePropertyValue);
							$nodePropertyValue->setTimezone(new \DateTimeZone(date_default_timezone_get()));
						} else {
							$nodePropertyValue = NULL;
						}
					break;
					case 'integer':
						$nodePropertyValue = intval($nodePropertyValue);
					break;
					case 'boolean':
						if (is_string($nodePropertyValue)) {
							$nodePropertyValue = $nodePropertyValue === 'true' ? TRUE : FALSE;
						}
					break;
					case 'array':
						$nodePropertyValue = json_decode($nodePropertyValue, TRUE);
					break;
				}
				if (!isset($nodeTypeProperties[$nodePropertyName])) {
					throw new TypeConverterException(sprintf('Node type "%s" does not have a property "%s" according to the schema', $nodeType->getName(), $nodePropertyName), 1359552744);
				}
				$innerType = $nodePropertyType;
				if ($nodePropertyType !== NULL) {
					try {
						$parsedType = \TYPO3\Flow\Utility\TypeHandling::parseType($nodePropertyType);
						$innerType = $parsedType['elementType'] ?: $parsedType['type'];
					} catch(\TYPO3\Flow\Utility\Exception\InvalidTypeException $exception) {
					}
				}

				if (is_string($nodePropertyValue) && $this->objectManager->isRegistered($innerType) && $nodePropertyValue !== '') {
					$nodePropertyValueDecoded = json_decode($nodePropertyValue, TRUE);
					if (isset($nodePropertyValueDecoded['__type'])) {
						unset($nodePropertyValueDecoded['__type']);
					}
					$nodePropertyValue = $this->propertyMapper->convert($nodePropertyValueDecoded, $nodePropertyType);
				}

				if ($nodePropertyValue) {
					$node->setProperty($nodePropertyName, $nodePropertyValue);
				}
			}
		}
	}

}
