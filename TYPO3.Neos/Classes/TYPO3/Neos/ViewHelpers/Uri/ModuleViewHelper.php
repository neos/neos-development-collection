<?php
namespace TYPO3\Neos\ViewHelpers\Uri;

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
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * A view helper for creating links to modules.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <link rel="some-module" href="{neos:link.module(path: 'system/useradmin')}" />
 * </code>
 *
 * Output:
 * <link rel="some-module" href="neos/system/useradmin" />
 * (depending on current node, format etc.)
 *
 * @Flow\Scope("prototype")
 */
class ModuleViewHelper extends AbstractViewHelper {

	/**
	 * @Flow\Inject
	 * @var UriBuilder
	 */
	protected $uriBuilder;

	/**
	 * Render a link to a specific module
	 *
	 * @param string $path Target module path
	 * @param string $action Target module action
	 * @param array $arguments Arguments
	 * @param string $section The anchor to be added to the URI
	 * @param string $format The requested format, e.g. ".html"
	 * @param array $additionalParams additional query parameters that won't be prefixed like $arguments (overrule $arguments)
	 * @param boolean $addQueryString If set, the current query parameters will be kept in the URI
	 * @param array $argumentsToBeExcludedFromQueryString arguments to be removed from the URI. Only active if $addQueryString = TRUE
	 * @return string The rendered link
	 * @throws \TYPO3\Fluid\Core\ViewHelper\Exception
	 */
	public function render($path, $action = NULL, $arguments = array(), $section = '', $format = '', array $additionalParams = array(), $addQueryString = FALSE, array $argumentsToBeExcludedFromQueryString = array()) {
		$this->setMainRequestToUriBuilder();
		$modifiedArguments = array('module' => $path);
		if ($arguments !== array()) {
			$modifiedArguments['moduleArguments'] = $arguments;
		}
		if ($action !== NULL) {
			$modifiedArguments['moduleArguments']['@action'] = $action;
		}

		try {
			return $this->uriBuilder
				->reset()
				->setSection($section)
				->setCreateAbsoluteUri(TRUE)
				->setArguments($additionalParams)
				->setAddQueryString($addQueryString)
				->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
				->setFormat($format)
				->uriFor('index', $modifiedArguments, 'Backend\Module', 'TYPO3.Neos');
		} catch (\TYPO3\Flow\Exception $exception) {
			throw new \TYPO3\Fluid\Core\ViewHelper\Exception($exception->getMessage(), $exception->getCode(), $exception);
		}
	}

	/**
	 * Extracted out to this method in order to be better unit-testable.
	 *
	 * @return void
	 */
	protected function setMainRequestToUriBuilder() {
		$mainRequest = $this->controllerContext->getRequest()->getMainRequest();
		$this->uriBuilder->setRequest($mainRequest);
	}

}
