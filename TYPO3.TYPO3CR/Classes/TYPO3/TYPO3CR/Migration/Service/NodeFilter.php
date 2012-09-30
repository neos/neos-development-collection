<?php
namespace TYPO3\TYPO3CR\Migration\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Service to determine if a given node matches a series of filters given by configuration.
 *
 * @Flow\Scope("singleton")
 */
class NodeFilter {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var array
	 */
	protected $filterConjunctions = array();

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param array $filterConfiguration
	 * @return boolean
	 */
	public function matchFilters(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, array $filterConfiguration) {
		$filterConjunction = $this->buildFilterConjunction($filterConfiguration);
		foreach ($filterConjunction as $filter) {
			if (!$filter->matches($node)) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * @param array $filterConfigurations
	 * @return array<\TYPO3\TYPO3CR\Migration\FilterInterface>
	 */
	protected function buildFilterConjunction(array $filterConfigurations) {
		$conjunctionIdentifier = md5(serialize($filterConfigurations));
		if (isset($this->filterConjunctions[$conjunctionIdentifier])) {
			return $this->filterConjunctions[$conjunctionIdentifier];
		}

		$conjunction = array();
		foreach ($filterConfigurations as $filterConfiguration) {
			$conjunction[] = $this->constructFilterObject($filterConfiguration);
		}
		$this->filterConjunctions[$conjunctionIdentifier] = $conjunction;

		return $conjunction;
	}

	/**
	 * @param array $filterConfiguration
	 * @return \TYPO3\TYPO3CR\Migration\Filters\FilterInterface
	 * @throws \TYPO3\TYPO3CR\Migration\Exception\MigrationException
	 */
	protected function constructFilterObject($filterConfiguration) {
		$filterClassName = $this->resolveFilterClass($filterConfiguration['type']);
		$filter = new $filterClassName;
		foreach ($filterConfiguration['settings'] as $propertyName => $propertyValue) {
			$setterName = 'set' . ucfirst($propertyName);
			if (method_exists($filter, $setterName)) {
				$filter->$setterName($propertyValue);
			} else {
				throw new \TYPO3\TYPO3CR\Migration\Exception\MigrationException('Filter "' . $filterClassName . '" does not have a setter for "' . $propertyName . '", so maybe it is not supported.', 1343199531);
			}
		}

		return $filter;
	}

	/**
	 * Resolves the class name for the filter by first assuming it is a full qualified class name and otherwise searching
	 * in this package (so filters delivered in TYPO3.TYPO3CR can be used by simply giving the class name without namespace).
	 *
	 * @param string $name
	 * @return string
	 * @throws \TYPO3\TYPO3CR\Migration\Exception\MigrationException
	 */
	protected function resolveFilterClass($name) {
		$resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName($name);
		if ($resolvedObjectName !== FALSE) {
			return $resolvedObjectName;
		}

		$resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName('TYPO3\TYPO3CR\Migration\Filters\\' . $name);
		if ($resolvedObjectName !== FALSE) {
			return $resolvedObjectName;
		}

		throw new \TYPO3\TYPO3CR\Migration\Exception\MigrationException('A filter with the name "' . $name . '" could not be found.', 1343199467);
	}
}
?>