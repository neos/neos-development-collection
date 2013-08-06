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
	 * @var array<\TYPO3\TYPO3CR\Domain\Service\ContextInterface>
	 */
	protected $contextInstances = array();

	/**
	 * The context implementation this factory will create
	 *
	 * @var string
	 */
	protected $contextImplementation = 'TYPO3\TYPO3CR\Domain\Service\Context';

	/**
	 * Create the context from the given properties. If a context with those properties was already
	 * created before then the existing one is returned.
	 *
	 * The context properties to give depend on the implementation of the context object, for the
	 * TYPO3\TYPO3CR\Domain\Service\Context it should look like this:
	 *
	 * array(
	 * 		'workspaceName' => 'live',
	 * 		'currentDateTime' => new \TYPO3\Flow\Utility\Now(),
	 * 		'locale' => new \TYPO3\Flow\I18n\Locale('mul_ZZ'),
	 * 		'invisibleContentShown' => FALSE,
	 * 		'removedContentShown' => FALSE,
	 * 		'inaccessibleContentShown' => FALSE
	 * )
	 *
	 * This array also shows the defaults that get used if you don't provide a certain property.
	 *
	 * @param array $contextProperties
	 * @return \TYPO3\TYPO3CR\Domain\Service\ContextInterface
	 * @api
	 */
	public function create(array $contextProperties) {
		$contextIdentifier = $this->getIdentifier($contextProperties);
		if (!isset($this->contextInstances[$contextIdentifier])) {
			$contextProperties = $this->mergeContextPropertiesWithDefaults($contextProperties);
			$this->validateContextProperties($contextProperties);
			$context = $this->buildContextInstance($contextProperties);
			$this->contextInstances[$contextIdentifier] = $context;
		}

		return $this->contextInstances[$contextIdentifier];
	}

	/**
	 * Creates the actual Context instance.
	 * This needs to be overriden if the Builder is extended.
	 *
	 * @param array $contextProperties
	 * @return \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	protected function buildContextInstance(array $contextProperties) {
		return new \TYPO3\TYPO3CR\Domain\Service\Context($contextProperties['workspaceName'], $contextProperties['currentDateTime'], $contextProperties['locale'], $contextProperties['invisibleContentShown'], $contextProperties['removedContentShown'], $contextProperties['inaccessibleContentShown']);
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
			'inaccessibleContentShown' => FALSE
		);

		return \TYPO3\Flow\Utility\Arrays::arrayMergeRecursiveOverrule($defaultContextProperties, $contextProperties, TRUE);
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
	 * This creates the actual identifier and needs to be overriden by builders extending this.
	 *
	 * @param array $contextProperties
	 * @return string
	 */
	protected function getIdentifierSource(array $contextProperties) {
		ksort($contextProperties);
		$identifierSource = $this->contextImplementation;
		foreach ($contextProperties as $propertyValue) {
			$stringValue = $propertyValue instanceof \DateTime ? $propertyValue->getTimestamp() : (string)$propertyValue;
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
		if (isset($contextProperties['workspaceName'])) {
			if (!is_string($contextProperties['workspaceName']) || $contextProperties['workspaceName'] === '') {
				throw new \TYPO3\TYPO3CR\Exception\InvalidNodeContextException('You tried to set a workspaceName in the context that was either no string or an empty string.', 1373144966);
			}
		}
		if (isset($contextProperties['invisibleContentShown'])) {
			if (!is_bool($contextProperties['invisibleContentShown'])) {
				throw new \TYPO3\TYPO3CR\Exception\InvalidNodeContextException('You tried to set invisibleContentShown in the context and did not provide a boolean value.', 1373145239);
			}
		}
		if (isset($contextProperties['removedContentShown'])) {
			if (!is_bool($contextProperties['removedContentShown'])) {
				throw new \TYPO3\TYPO3CR\Exception\InvalidNodeContextException('You tried to set removedContentShown in the context and did not provide a boolean value.', 1373145239);
			}
		}
		if (isset($contextProperties['inaccessibleContentShown'])) {
			if (!is_bool($contextProperties['inaccessibleContentShown'])) {
				throw new \TYPO3\TYPO3CR\Exception\InvalidNodeContextException('You tried to set inaccessibleContentShown in the context and did not provide a boolean value.', 1373145239);
			}
		}
		if (isset($contextProperties['currentDateTime'])) {
			if (!$contextProperties['currentDateTime'] instanceof \DateTime) {
				throw new \TYPO3\TYPO3CR\Exception\InvalidNodeContextException('You tried to set currentDateTime in the context and did not provide a DateTime object as value.', 1373145297);
			}
		}
		if (isset($contextProperties['locale'])) {
			if (!$contextProperties['locale'] instanceof \TYPO3\Flow\I18n\Locale) {
				throw new \TYPO3\TYPO3CR\Exception\InvalidNodeContextException('You tried to set locale in the context and did not provide a \\TYPO3\\Flow\\I18n\\Locale object as value.', 1373145384);
			}
		}
	}
}
?>