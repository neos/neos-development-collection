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
 * Render a TypoScript object with a relative TypoScript path, optionally pushing
 * new variables onto the TypoScript context
 */
class RenderTypoScriptViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Evaluate the TypoScript object at $path and return the rendered result.
	 *
	 * @param string $path the relative TypoScript path to be rendered
	 * @param array $context the context variables to be set
	 * @return string
	 */
	public function render($path, array $context = NULL) {
		$fluidTemplateTsObject = $this->templateVariableContainer->get('fluidTemplateTsObject'); // TODO: should be retrieved differently lateron
		if ($context !== NULL) {
			$currentContext = $fluidTemplateTsObject->getTsRuntime()->getCurrentContext();
			foreach ($context as $k => $v) {
				$currentContext[$k] = $v;
			}
			$fluidTemplateTsObject->getTsRuntime()->pushContextArray($currentContext);
		}
		if (strpos($path, '/') === 0) {
			$absolutePath = $path;
		} else {
			$absolutePath = $fluidTemplateTsObject->getPath() . '/' . $path;
		}

		$output = $fluidTemplateTsObject->getTsRuntime()->render($absolutePath);

		if ($context !== NULL) {
			$fluidTemplateTsObject->getTsRuntime()->popContext();
		}

		return $output;
	}
}
?>