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
use TYPO3\Flow\Utility\Arrays;

/**
 * A route part handler for finding nodes specifically in the website's frontend.
 *
 * @Flow\Scope("singleton")
 */
class BackendModuleRoutePartHandler extends \TYPO3\Flow\Mvc\Routing\DynamicRoutePart {

	const MATCHRESULT_FOUND = TRUE;
	const MATCHRESULT_NOSUCHMODULE = -1;
	const MATCHRESULT_NOCONTROLLER = -2;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Iterate through the segments of the current request path
	 * find the corresponding module configuration and set controller & action
	 * accordingly
	 *
	 * @param string $value
	 * @return boolean|integer
	 */
	protected function matchValue($value) {
		$format = pathinfo($value, PATHINFO_EXTENSION);
		if ($format !== '') {
			$value = substr($value, 0, strlen($value) - strlen($format) - 1);
		}
		$segments = Arrays::trimExplode('/', $value);

		$currentModuleBase = $this->settings['modules'];
		if ($segments === array() || !isset($currentModuleBase[$segments[0]])) {
			return self::MATCHRESULT_NOSUCHMODULE;
		}

		$modulePath = array();
		$level = 0;
		$moduleConfiguration = NULL;
		$moduleController = NULL;
		$moduleAction = 'index';
		foreach ($segments as $segment) {
			if (isset($currentModuleBase[$segment])) {
				$modulePath[] = $segment;
				$moduleConfiguration = $currentModuleBase[$segment];

				if (isset($moduleConfiguration['controller'])) {
					$moduleController = $moduleConfiguration['controller'];
				} else {
					$moduleController = NULL;
				}

				if (isset($moduleConfiguration['submodules'])) {
					$currentModuleBase = $moduleConfiguration['submodules'];
				} else {
					$currentModuleBase = array();
				}
			} else {
				if ($level === count($segments) - 1) {
					$moduleMethods = array_change_key_case(array_flip(get_class_methods($moduleController)), CASE_LOWER);
					if (array_key_exists($segment . 'action', $moduleMethods)) {
						$moduleAction = $segment;
						break;
					}
				}
				return self::MATCHRESULT_NOSUCHMODULE;
			}
			$level++;
		}

		if ($moduleController === NULL) {
			return self::MATCHRESULT_NOCONTROLLER;
		}

		$this->value = array(
			'module' => implode('/', $modulePath),
			'controller' => $moduleController,
			'action' => $moduleAction
		);

		if ($format !== '') {
			$this->value['format'] = $format;
		}

		return self::MATCHRESULT_FOUND;
	}

	/**
	 * @param string $requestPath
	 * @return string
	 */
	protected function findValueToMatch($requestPath) {
		return $requestPath;
	}

	/**
	 * Iterate through the configured modules, find the matching module and set
	 * the route path accordingly
	 *
	 * @param array $value (contains action, controller and package of the module controller)
	 * @return boolean
	 */
	protected function resolveValue($value) {
		if (is_array($value)) {
			$this->value = $value['module'];
		} else {
			$this->value = $value;
		}
		return TRUE;
	}

}
