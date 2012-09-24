<?php
namespace TYPO3\TYPO3\ViewHelpers\Link;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A view helper for creating links to modules.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <typo3:link.module path="system/useradmin">some link</typo3:link.module>
 * </code>
 *
 * Output:
 * <a href="typo3/system/useradmin">some link</a>
 * (depending on current node, format etc.)
 *
 * @FLOW3\Scope("prototype")
 */
class ModuleViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'a';

	/**
	 * Initialize arguments
	 *
	 * @return void
	 */
	public function initializeArguments() {
		$this->registerUniversalTagAttributes();
		$this->registerTagAttribute('name', 'string', 'Specifies the name of an anchor');
		$this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked document');
		$this->registerTagAttribute('rev', 'string', 'Specifies the relationship between the linked document and the current document');
		$this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');
	}

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
		$mainRequest = $this->controllerContext->getRequest()->getMainRequest();

		$uriBuilder = new \TYPO3\FLOW3\Mvc\Routing\UriBuilder();
		$uriBuilder->setRequest($mainRequest);
		$modifiedArguments = array('module' => $path);
		if ($arguments !== array()) {
			$modifiedArguments['moduleArguments'] = $arguments;
		}
		if ($action !== NULL) {
			$modifiedArguments['moduleArguments']['@action'] = $action;
		}

		try {
			$uri = $uriBuilder
				->reset()
				->setSection($section)
				->setCreateAbsoluteUri(TRUE)
				->setArguments($additionalParams)
				->setAddQueryString($addQueryString)
				->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
				->setFormat($format)
				->uriFor('index', $modifiedArguments, 'Backend\Module', 'TYPO3.TYPO3');
			$this->tag->addAttribute('href', $uri);
		} catch (\TYPO3\FLOW3\Exception $exception) {
			throw new \TYPO3\Fluid\Core\ViewHelper\Exception($exception->getMessage(), $exception->getCode(), $exception);
		}

		$this->tag->setContent($this->renderChildren());
		$this->tag->forceClosingTag(TRUE);

		return $this->tag->render();
	}

}
?>