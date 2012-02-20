<?php
namespace TYPO3\TypoScript\ViewHelpers;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Render a TypoScript object with a relative TypoScript path
 *
 * @author sebastian
 */
class RenderTypoScriptViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {
	public function render($path) {
		$fluidTemplateTsObject = $this->templateVariableContainer->get('fluidTemplateTsObject'); // TODO: should be retrieved differently

		$absolutePath = $fluidTemplateTsObject->getPath() . '/' . $path;
		return $fluidTemplateTsObject->getTsRuntime()->render($absolutePath);
	}
}

?>
