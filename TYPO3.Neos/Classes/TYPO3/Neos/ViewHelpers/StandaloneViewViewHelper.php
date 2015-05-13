<?php
namespace TYPO3\Neos\ViewHelpers;

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
 * A View Helper to render a fluid template based on the given template path and filename.
 *
 * This will just set up a standalone Fluid view and render the template found at the
 * given path and filename. Any arguments passed will be assigned to that template,
 * the rendering result is returned.
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * <neos:standaloneView templatePathAndFilename="fancyTemplatePathAndFilename" arguments="{foo: bar, quux: baz}" />
 * </code>
 * <output>
 * <some><fancy/></html
 * (depending on template and arguments given)
 * </output>
 *
 * @Flow\Scope("prototype")
 */
class StandaloneViewViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var boolean
	 */
	protected $escapeOutput = FALSE;

	/**
	 * @param string $templatePathAndFilename Path and filename of the template to render
	 * @param array $arguments Arguments to assign to the template before rendering
	 * @return string
	 */
	public function render($templatePathAndFilename, $arguments = array()) {
		$standaloneView = new \TYPO3\Fluid\View\StandaloneView($this->controllerContext->getRequest());
		$standaloneView->setTemplatePathAndFilename($templatePathAndFilename);
		return $standaloneView->assignMultiple($arguments)->render();
	}

}
