<?php
namespace TYPO3\TYPO3CR\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\ContentDimension;
use TYPO3\TYPO3CR\Exception\InvalidNodeContextException;

/**
 * The ContextFactory makes sure you don't create context instances with
 * the same properties twice. Calling create() with the same parameters
 * a second time will return the _same_ Context instance again.
 * Refer to 'ContextFactoryInterface' instead of 'ContextFactory' when
 * injecting this factory into your own class.
 *
 * @Flow\Scope("singleton")
 */
class ContextFactory implements ContextFactoryInterface {

	/**
	 * @var array<\TYPO3\TYPO3CR\Domain\Service\Context>
	 */
	protected $contextInstances = array();

	/**
	 * The context implementation this factory will create
	 *
	 * @var string
	 */
	protected $contextImplementation = 'TYPO3\TYPO3CR\Domain\Service\Context';

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository
	 */
	protected $contentDimensionRepository;

	/**
	 * Create the context from the given properties. If a context with those properties was already
	 * created before then the existing one is returned.
	 *
	 * The context properties to give depend on the implementation of the context object, for the
	 * TYPO3\TYPO3CR\Domain\Service\Context it should look like this:
	 *
	 * array(
	 *        'workspaceName' => 'live',
	 *        'currentDateTime' => new \TYPO3\Flow\Utility\Now(),
	 *        'dimensions' => array(...),
	 *        'targetDimensions' => array(...),
	 *        'invisibleContentShown' => FALSE,
	 *        'removedContentShown' => FALSE,
	 *        'inaccessibleContentShown' => FALSE
	 * )
	 *
	 * This array also shows the defaults that get used if you don't provide a certain property.
	 *
	 * @param array $contextProperties
	 * @return \TYPO3\TYPO3CR\Domain\Service\Context
	 * @api
	 */
	public function create(array $contextProperties) {
		$contextProperties = $this->mergeContextPropertiesWithDefaults($contextProperties);
		$contextIdentifier = $this->getIdentifier($contextProperties);
		if (!isset($this->contextInstances[$contextIdentifier])) {
			$this->validateContextProperties($contextProperties);
			$context = $this->buildContextInstance($contextProperties);
			$this->contextInstances[$contextIdentifier] = $context;
		}

		return $this->contextInstances[$contextIdentifier];
	}

	/**
	 * Creates the actual Context instance.
	 * This needs to be overridden if the Builder is extended.
	 *
	 * @param array $contextProperties
	 * @return \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	protected function buildContextInstance(array $contextProperties) {
		$contextProperties = $this->setBackwardCompatibleLocales($contextProperties);
		return new \TYPO3\TYPO3CR\Domain\Service\Context($contextProperties['workspaceName'], $contextProperties['currentDateTime'], $contextProperties['dimensions'], $contextProperties['targetDimensions'], $contextProperties['invisibleContentShown'], $contextProperties['removedContentShown'], $contextProperties['inaccessibleContentShown']);
	}

	/**
	 * Merges the given context properties with sane defaults for the context implementation.
	 *
	 * @param array $contextProperties
	 * @return array
	 */
	protected function mergeContextPropertiesWithDefaults(array $contextProperties) {
		$contextProperties = $this->setBackwardCompatibleLocales($contextProperties);

		$defaultContextProperties = array(
			'workspaceName' => 'live',
			'currentDateTime' => new \TYPO3\Flow\Utility\Now(),
			'dimensions' => array(),
			'targetDimensions' => array(),
			'invisibleContentShown' => FALSE,
			'removedContentShown' => FALSE,
			'inaccessibleContentShown' => FALSE
		);

		$mergedProperties = \TYPO3\Flow\Utility\Arrays::arrayMergeRecursiveOverrule($defaultContextProperties, $contextProperties, TRUE);

		$this->mergeDimensionValues($contextProperties, $mergedProperties);
		$this->mergeTargetDimensionContextProperties($contextProperties, $mergedProperties, $defaultContextProperties);

		return $mergedProperties;
	}

	/**
	 * Provides a way to identify a context to prevent duplicate context objects.
	 *
	 * @param array $contextProperties
	 * @return string
	 */
	protected function getIdentifier(array $contextProperties) {
		return md5($this->getIdentifierSource($contextProperties));
	}

	/**
	 * This creates the actual identifier and needs to be overridden by builders extending this.
	 *
	 * @param array $contextProperties
	 * @return string
	 */
	protected function getIdentifierSource(array $contextProperties) {
		ksort($contextProperties);
		$identifierSource = $this->contextImplementation;
		foreach ($contextProperties as $propertyName => $propertyValue) {
			if ($propertyName === 'dimensions') {
				$stringParts = array();
				foreach ($propertyValue as $dimensionName => $dimensionValues) {
					$stringParts[] = $dimensionName . '=' . implode(',', $dimensionValues);
				}
				$stringValue = implode('&', $stringParts);
			} elseif ($propertyName === 'targetDimensions') {
				$stringParts = array();
				foreach ($propertyValue as $dimensionName => $dimensionValue) {
					$stringParts[] = $dimensionName . '=' . $dimensionValue;
				}
				$stringValue = implode('&', $stringParts);
			} else {
				$stringValue = $propertyValue instanceof \DateTime ? $propertyValue->getTimestamp() : (string)$propertyValue;
			}
			$identifierSource .= ':' . $stringValue;
		}

		return $identifierSource;
	}

	/**
	 * @param array $contextProperties
	 * @return void
	 * @throws InvalidNodeContextException
	 */
	protected function validateContextProperties($contextProperties) {
		if (isset($contextProperties['workspaceName'])) {
			if (!is_string($contextProperties['workspaceName']) || $contextProperties['workspaceName'] === '') {
				throw new InvalidNodeContextException('You tried to set a workspaceName in the context that was either no string or an empty string.', 1373144966);
			}
		}
		if (isset($contextProperties['invisibleContentShown'])) {
			if (!is_bool($contextProperties['invisibleContentShown'])) {
				throw new InvalidNodeContextException('You tried to set invisibleContentShown in the context and did not provide a boolean value.', 1373145239);
			}
		}
		if (isset($contextProperties['removedContentShown'])) {
			if (!is_bool($contextProperties['removedContentShown'])) {
				throw new InvalidNodeContextException('You tried to set removedContentShown in the context and did not provide a boolean value.', 1373145239);
			}
		}
		if (isset($contextProperties['inaccessibleContentShown'])) {
			if (!is_bool($contextProperties['inaccessibleContentShown'])) {
				throw new InvalidNodeContextException('You tried to set inaccessibleContentShown in the context and did not provide a boolean value.', 1373145239);
			}
		}
		if (isset($contextProperties['currentDateTime'])) {
			if (!$contextProperties['currentDateTime'] instanceof \DateTime) {
				throw new InvalidNodeContextException('You tried to set currentDateTime in the context and did not provide a DateTime object as value.', 1373145297);
			}
		}

		$dimensions = $this->getAvailableDimensions();
		/** @var ContentDimension $dimension */
		foreach ($dimensions as $dimension) {
			if (!isset($contextProperties['dimensions'][$dimension->getIdentifier()])
				|| !is_array($contextProperties['dimensions'][$dimension->getIdentifier()])
				|| $contextProperties['dimensions'][$dimension->getIdentifier()] === array()
			) {
				throw new InvalidNodeContextException(sprintf('You have to set a non-empty array with one or more values for content dimension "%s" in the context', $dimension->getIdentifier()), 1390300646);
			}
		}

		foreach ($contextProperties['targetDimensions'] as $dimensionName => $dimensionValue) {
			if (!isset($contextProperties['dimensions'][$dimensionName]) || !in_array($dimensionValue, $contextProperties['dimensions'][$dimensionName])) {
				throw new InvalidNodeContextException(sprintf('Target dimension value %s for dimension %s is not in the list of dimension values (%s)', $dimensionValue, $dimensionName, implode(', ', $contextProperties['dimensions'][$dimensionName])), 1391340741);
			}
		}
	}

	/**
	 * Set the "locales" context property from a "locale" property if given
	 *
	 * @param array $contextProperties
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	protected function setBackwardCompatibleLocales(array $contextProperties) {
		if (isset($contextProperties['locale'])) {
			if (!isset($contextProperties['dimensions']['locales'])) {
				$contextProperties['dimensions']['locales'] = array($contextProperties['locale']);
				unset($contextProperties['locale']);
				return $contextProperties;
			} else {
				throw new \InvalidArgumentException('Context properties "locale" and dimension "locales" cannot be mixed.', 1389613179);
			}
		}
		return $contextProperties;
	}

	/**
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\ContentDimension>
	 */
	protected function getAvailableDimensions() {
		return $this->contentDimensionRepository->findAll();
	}

	/**
	 * Reset instances (internal)
	 */
	public function reset() {
		$this->contextInstances = array();
	}

	/**
	 * @param array $contextProperties
	 * @param array $mergedProperties
	 * @param array $defaultContextProperties
	 * @return mixed
	 */
	protected function mergeTargetDimensionContextProperties(array $contextProperties, &$mergedProperties, $defaultContextProperties) {
			// Use first value of each dimension as default target dimension value
		$defaultContextProperties['targetDimensions'] = array_map(function ($values) {
			return reset($values);
		}, $mergedProperties['dimensions']);
		if (!isset($contextProperties['targetDimensions'])) {
			$contextProperties['targetDimensions'] = array();
		}
		$mergedProperties['targetDimensions'] = \TYPO3\Flow\Utility\Arrays::arrayMergeRecursiveOverrule($defaultContextProperties['targetDimensions'], $contextProperties['targetDimensions']);
	}

	/**
	 * @param array $contextProperties
	 * @param array $mergedProperties
	 */
	protected function mergeDimensionValues(array $contextProperties, array &$mergedProperties) {
		$dimensions = $this->getAvailableDimensions();
		foreach ($dimensions as $dimension) {
			/** @var ContentDimension $dimension */
			$identifier = $dimension->getIdentifier();
			$values = array($dimension->getDefault());
			if (isset($contextProperties['dimensions'][$identifier])) {
				$values = \TYPO3\Flow\Utility\Arrays::arrayMergeRecursiveOverrule($values, $contextProperties['dimensions'][$identifier]);
			}
			$mergedProperties['dimensions'][$identifier] = $values;
		}
	}

	/**
	 * Helper method which parses the "dimension" part of the context, i.e.
	 * "locales=de_DE,mul_ZZ&...." into an *array* of dimension values.
	 *
	 * Is needed at both the RoutePartHandler and the ObjectConverter; that's why
	 * it's placed here.
	 *
	 * @param array $dimensionPartOfContext
	 * @return array
	 */
	public function parseDimensionValueStringToArray(array $dimensionPartOfContext) {
		parse_str($dimensionPartOfContext, $dimensions);
		$dimensions = array_map(function ($commaSeparatedValues) { return explode(',', $commaSeparatedValues); }, $dimensions);

		return $dimensions;
	}
}