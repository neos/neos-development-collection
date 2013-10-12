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

/**
 * ContentContextFactory which ensures contexts stay unique. Make sure to
 * get ContextFactoryInterface injected instead of this class.
 *
 * See \TYPO3\TYPO3CR\Domain\Service\ContextFactory->build for detailed
 * explanations about the usage.
 *
 * @Flow\Scope("singleton")
 */
class ContentContextFactory extends \TYPO3\TYPO3CR\Domain\Service\ContextFactory {

	/**
	 * The context implementation this factory will create
	 *
	 * @var string
	 */
	protected $contextImplementation = 'TYPO3\Neos\Domain\Service\ContentContext';

	/**
	 * Creates the actual Context instance.
	 * This needs to be overriden if the Builder is extended.
	 *
	 * @param array $contextProperties
	 * @return \TYPO3\Neos\Domain\Service\ContentContext
	 */
	protected function buildContextInstance(array $contextProperties) {
		return new \TYPO3\Neos\Domain\Service\ContentContext($contextProperties['workspaceName'], $contextProperties['currentDateTime'], $contextProperties['locale'], $contextProperties['invisibleContentShown'], $contextProperties['removedContentShown'], $contextProperties['inaccessibleContentShown'], $contextProperties['currentSite'], $contextProperties['currentDomain']);
	}

	/**
	 * Merges the given context properties with sane defaults for the context implementation.
	 *
	 * @param array $contextProperties
	 * @return array
	 */
	protected function mergeContextPropertiesWithDefaults(array $contextProperties) {
		$defaultContextProperties = array (
			'workspaceName' => 'live',
			'currentDateTime' => new \TYPO3\Flow\Utility\Now(),
			'locale' => new \TYPO3\Flow\I18n\Locale('mul_ZZ'),
			'invisibleContentShown' => FALSE,
			'removedContentShown' => FALSE,
			'inaccessibleContentShown' => FALSE,
			'currentSite' => NULL,
			'currentDomain' => NULL
		);

		return \TYPO3\Flow\Utility\Arrays::arrayMergeRecursiveOverrule($defaultContextProperties, $contextProperties, TRUE);
	}

	/**
	 * This creates the actual identifier and needs to be overriden by builders extending this.
	 *
	 * @param array $contextProperties
	 * @return string
	 */
	protected function getIdentifierSource(array $contextProperties) {
		ksort($contextProperties);
		$identifierSource = $this->contextImplementation;
		foreach ($contextProperties as $propertyValue) {
			if ($propertyValue instanceof \DateTime) {
				$stringValue = $propertyValue->getTimestamp();
			} elseif ($propertyValue instanceof \TYPO3\Neos\Domain\Model\Site) {
				$stringValue = $propertyValue->getNodeName();
			} elseif ($propertyValue instanceof \TYPO3\Neos\Domain\Model\Domain) {
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
	 * @throws \TYPO3\TYPO3CR\Exception\InvalidNodeContextException
	 */
	protected function validateContextProperties($contextProperties) {
		parent::validateContextProperties($contextProperties);

		if (isset($contextProperties['currentSite'])) {
			if (!$contextProperties['currentSite'] instanceof \TYPO3\Neos\Domain\Model\Site) {
				throw new \TYPO3\TYPO3CR\Exception\InvalidNodeContextException('You tried to set currentSite in the context and did not provide a \\TYPO3\Neos\\Domain\\Model\\Site object as value.', 1373145297);
			}
		}
		if (isset($contextProperties['currentDomain'])) {
			if (!$contextProperties['currentDomain'] instanceof \TYPO3\Neos\Domain\Model\Domain) {
				throw new \TYPO3\TYPO3CR\Exception\InvalidNodeContextException('You tried to set locale in the context and did not provide a \\TYPO3\Neos\\Domain\\Model\\Domain object as value.', 1373145384);
			}
		}
	}


}
