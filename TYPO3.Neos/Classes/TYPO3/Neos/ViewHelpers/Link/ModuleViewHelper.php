<?php
namespace TYPO3\Neos\ViewHelpers\Link;

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
use TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * A view helper for creating links to modules.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <neos:link.module path="system/useradmin">some link</neos:link.module>
 * </code>
 * <output>
 * <a href="neos/system/useradmin">some link</a>
 * </output>
 */
class ModuleViewHelper extends AbstractTagBasedViewHelper {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\ViewHelpers\Uri\ModuleViewHelper
	 */
	protected $uriModuleViewHelper;

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
		$this->uriModuleViewHelper->setRenderingContext($this->renderingContext);

		$uri = $this->uriModuleViewHelper->render($path, $action, $arguments, $section, $format, $additionalParams, $addQueryString, $argumentsToBeExcludedFromQueryString);
		if ($uri !== NULL) {
			$this->tag->addAttribute('href', $uri);
		}

		$this->tag->setContent($this->renderChildren());
		$this->tag->forceClosingTag(TRUE);

		return $this->tag->render();
	}

}
