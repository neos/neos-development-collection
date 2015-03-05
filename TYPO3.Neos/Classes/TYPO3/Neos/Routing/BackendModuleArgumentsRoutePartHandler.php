<?php
namespace TYPO3\Neos\Routing;

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
 * A route part handler for finding nodes specifically in the website's frontend.
 *
 * @Flow\Scope("singleton")
 */
class BackendModuleArgumentsRoutePartHandler extends \TYPO3\Flow\Mvc\Routing\DynamicRoutePart {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Iterate through the configured modules, find the matching module and set
	 * the route path accordingly
	 *
	 * @param array $value (contains action, controller and package of the module controller)
	 * @return boolean
	 */
	protected function resolveValue($value) {
		if (is_array($value)) {
			$this->value = isset($value['@action']) && $value['@action'] !== 'index' ? $value['@action'] : '';
			if (isset($value['@format'])) {
				$this->value .= '.' . $value['@format'];
			}
			$exceedingArguments = array();
			foreach ($value as $argumentKey => $argumentValue) {
				if (substr($argumentKey, 0, 1) !== '@' && substr($argumentKey, 0, 2) !== '__') {
					$exceedingArguments[$argumentKey] = $argumentValue;
				}
			}
			if ($exceedingArguments !== array()) {
				$exceedingArguments = \TYPO3\Flow\Utility\Arrays::removeEmptyElementsRecursively($exceedingArguments);
				$exceedingArguments = $this->persistenceManager->convertObjectsToIdentityArrays($exceedingArguments);
				$queryString = http_build_query(array($this->name => $exceedingArguments), NULL, '&');
				if ($queryString !== '') {
					$this->value .= '?' . $queryString;
				}
			}
		}

		return TRUE;
	}

}
