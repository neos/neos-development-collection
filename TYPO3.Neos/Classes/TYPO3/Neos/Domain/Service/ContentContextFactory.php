<?php
namespace TYPO3\Neos\Domain\Service;

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
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Neos\Domain\Model\Domain;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\TYPO3CR\Domain\Service\ContextFactory;
use TYPO3\TYPO3CR\Exception\InvalidNodeContextException;

/**
 * ContentContextFactory which ensures contexts stay unique. Make sure to
 * get ContextFactoryInterface injected instead of this class.
 *
 * See \TYPO3\TYPO3CR\Domain\Service\ContextFactory->build for detailed
 * explanations about the usage.
 *
 * @Flow\Scope("singleton")
 */
class ContentContextFactory extends ContextFactory {

	/**
	 * The context implementation this factory will create
	 *
	 * @var string
	 */
	protected $contextImplementation = 'TYPO3\Neos\Domain\Service\ContentContext';

	/**
	 * Creates the actual Context instance.
	 * This needs to be overridden if the Builder is extended.
	 *
	 * @param array $contextProperties
	 * @return ContentContext
	 */
	protected function buildContextInstance(array $contextProperties) {
		$contextProperties = $this->removeDeprecatedProperties($contextProperties);

		return new ContentContext(
			$contextProperties['workspaceName'],
			$contextProperties['currentDateTime'],
			$contextProperties['dimensions'],
			$contextProperties['targetDimensions'],
			$contextProperties['invisibleContentShown'],
			$contextProperties['removedContentShown'],
			$contextProperties['inaccessibleContentShown'],
			$contextProperties['currentSite'],
			$contextProperties['currentDomain']
		);
	}

	/**
	 * Merges the given context properties with sane defaults for the context implementation.
	 *
	 * @param array $contextProperties
	 * @return array
	 */
	protected function mergeContextPropertiesWithDefaults(array $contextProperties) {
		$contextProperties = $this->removeDeprecatedProperties($contextProperties);

		$defaultContextProperties = array (
			'workspaceName' => 'live',
			'currentDateTime' => $this->now,
			'dimensions' => array(),
			'targetDimensions' => array(),
			'invisibleContentShown' => FALSE,
			'removedContentShown' => FALSE,
			'inaccessibleContentShown' => FALSE,
			'currentSite' => NULL,
			'currentDomain' => NULL
		);

		$mergedProperties = Arrays::arrayMergeRecursiveOverrule($defaultContextProperties, $contextProperties, TRUE);

		$this->mergeDimensionValues($contextProperties, $mergedProperties);
		$this->mergeTargetDimensionContextProperties($contextProperties, $mergedProperties, $defaultContextProperties);

		return $mergedProperties;
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
			} elseif ($propertyValue instanceof \DateTime) {
				$stringValue = $propertyValue->getTimestamp();
			} elseif ($propertyValue instanceof Site) {
				$stringValue = $propertyValue->getNodeName();
			} elseif ($propertyValue instanceof Domain) {
				$stringValue = $propertyValue->getHostPattern();
			} else {
				$stringValue = (string)$propertyValue;
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
		parent::validateContextProperties($contextProperties);

		if (isset($contextProperties['currentSite'])) {
			if (!$contextProperties['currentSite'] instanceof Site) {
				throw new InvalidNodeContextException('You tried to set currentSite in the context and did not provide a \\TYPO3\Neos\\Domain\\Model\\Site object as value.', 1373145297);
			}
		}
		if (isset($contextProperties['currentDomain'])) {
			if (!$contextProperties['currentDomain'] instanceof Domain) {
				throw new InvalidNodeContextException('You tried to set currentDomain in the context and did not provide a \\TYPO3\Neos\\Domain\\Model\\Domain object as value.', 1373145384);
			}
		}
	}

}
