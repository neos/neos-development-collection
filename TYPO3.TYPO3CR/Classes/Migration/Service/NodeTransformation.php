<?php
namespace TYPO3\TYPO3CR\Migration\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Service that executes a series of configured transformations on a node.
 *
 * @FLOW3\Scope("singleton")
 */
class NodeTransformation {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var array<\TYPO3\TYPO3CR\Migration\Transformations\TransformationInterface>
	 */
	protected $transformationConjunctions = array();

	/**
	 * Executes all configured transformations starting on the given node.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param array $transformationConfigurations
	 * @return void
	 */
	public function execute(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, array $transformationConfigurations) {
		$transformationConjunction = $this->buildTransformationConjunction($transformationConfigurations);
		foreach ($transformationConjunction as $transformation) {
			if ($transformation->isTransformable($node)) {
				$transformation->execute($node);
			}
		}
	}

	/**
	 * @param array $transformationConfigurations
	 * @return array<\TYPO3\TYPO3CR\Migration\Transformations\TransformationInterface>
	 */
	protected function buildTransformationConjunction(array $transformationConfigurations) {
		$conjunctionIdentifier = md5(serialize($transformationConfigurations));
		if (isset($this->transformationConjunctions[$conjunctionIdentifier])) {
			return $this->transformationConjunctions[$conjunctionIdentifier];
		}

		$conjunction = array();
		foreach ($transformationConfigurations as $transformationConfiguration) {
			$conjunction[] = $this->buildTransformationObject($transformationConfiguration);
		}
		$this->transformationConjunctions[$conjunctionIdentifier] = $conjunction;

		return $conjunction;
	}

	/**
	 * Builds a transformation object from the given configuration.
	 *
	 * @param array $transformationConfiguration
	 * @return \TYPO3\TYPO3CR\Migration\Transformations\TransformationInterface
	 * @throws \TYPO3\TYPO3CR\Migration\Exception\MigrationException if a given setting is not supported
	 */
	protected function buildTransformationObject($transformationConfiguration) {
		$transformationClassName = $this->resolveTransformationClassName($transformationConfiguration['type']);
		$transformation = new $transformationClassName();

		foreach ($transformationConfiguration['settings'] as $settingName => $settingValue) {
			if (!\TYPO3\FLOW3\Reflection\ObjectAccess::setProperty($transformation, $settingName, $settingValue)) {
				throw new \TYPO3\TYPO3CR\Migration\Exception\MigrationException('Cannot set setting "' . $settingName . '" on transformation "' . $transformationClassName . '" , check your configuration.', 1343293094);
			}
		}

		return $transformation;
	}

	/**
	 * Tries to resolve the given transformation name into a classname.
	 *
	 * The name can be a fully qualified class name or a name relative to the
	 * TYPO3\TYPO3CR\Migration\Transformations namespace.
	 *
	 * @param string $transformationName
	 * @return string
	 * @throws \TYPO3\TYPO3CR\Migration\Exception\MigrationException
	 */
	protected function resolveTransformationClassName($transformationName) {
		$resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName($transformationName);
		if ($resolvedObjectName !== FALSE) {
			return $resolvedObjectName;
		}

		$resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName('TYPO3\TYPO3CR\Migration\Transformations\\' . $transformationName);
		if ($resolvedObjectName !== FALSE) {
			return $resolvedObjectName;
		}

		throw new \TYPO3\TYPO3CR\Migration\Exception\MigrationException('A transformation with the name "' . $transformationName . '" could not be found.', 1343293064);
	}
}
?>