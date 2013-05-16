<?php
namespace TYPO3\TypoScript\ViewHelpers;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
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
class RenderViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Evaluate the TypoScript object at $path and return the rendered result.
	 *
	 * @param string $path the relative TypoScript path to be rendered
	 * @param array $context the context variables to be set
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function render($path, array $context = NULL) {
		if (strpos($path, '/') === 0 || strpos($path, '.') === 0) {
			throw new \InvalidArgumentException('When calling the TypoScript render view helper only relative paths are allowed.', 1368740480);
		}
		if (preg_match('/^[a-z0-9.]+$/i', $path) !== 1) {
			throw new \InvalidArgumentException('Invalid path given to the TypoScript render view helper ', 1368740484);
		}

		$fluidTemplateTsObject = $this->templateVariableContainer->get('fluidTemplateTsObject'); // TODO: should be retrieved differently lateron
		if ($context !== NULL) {
			$currentContext = $fluidTemplateTsObject->getTsRuntime()->getCurrentContext();
			foreach ($context as $k => $v) {
				$currentContext[$k] = $v;
			}
			$fluidTemplateTsObject->getTsRuntime()->pushContextArray($currentContext);
		}

		$path = str_replace('.', '/', $path);
		$absolutePath = $fluidTemplateTsObject->getPath() . '/' . $path;

		$output = $fluidTemplateTsObject->getTsRuntime()->render($absolutePath);

		if ($context !== NULL) {
			$fluidTemplateTsObject->getTsRuntime()->popContext();
		}

		return $output;
	}
}
?>