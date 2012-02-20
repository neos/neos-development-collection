<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Fluid Renderer, rendering a fluid template
 *
 * //tsPath variables TODO The result of this TS object is made available inside the template as "variables"
 * @api
 */
class FluidRenderer extends AbstractTsObject implements \ArrayAccess {

	/**
	 * Path to the template which should be rendered
	 *
	 * @var string
	 */
	protected $templatePath = NULL;

	/**
	 * Name of a specific section, if only this section should be rendered.
	 *
	 * @var string
	 */
	protected $sectionName = NULL;

	/**
	 * List of variables being made available inside the fluid template. use
	 * magic setters for setting them.
	 *
	 * @var array
	 */
	protected $variables = array();

	/**
	 * @param string $templatePath
	 */
	public function setTemplatePath($templatePath) {
		$this->templatePath = $templatePath;
	}

	/**
	 * @param string $sectionName
	 */
	public function setSectionName($sectionName) {
		$this->sectionName = $sectionName;
	}

	/**
	 * @return string
	 * @internal
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @return \TYPO3\TypoScript\Core\Runtime
	 * @internal
	 */
	public function getTsRuntime() {
		return $this->tsRuntime;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param mixed $context
	 * @return string
	 */
	public function evaluate($context) {
		$fluidTemplate = new \TYPO3\Fluid\View\StandaloneView();

		$templatePath = $this->tsValue('templatePath');
		if ($templatePath === NULL) {
			throw new \Exception('Template path "' . $templatePath . '" at path "' . $this->path . '"  not found');
		}
		$fluidTemplate->setTemplatePathAndFilename($templatePath);

		// Set controller package key from template path
		if (strpos($templatePath, 'resource://') === 0) {
			$tmp = substr($templatePath, 11);
			$tmp2 = explode('/', $tmp);

			$fluidTemplate->getRequest()->setControllerPackageKey(array_shift(($tmp2)));
		}

		foreach ($this->variables as $key => $value) {
			$evaluatedValue = $this->tsRuntime->evaluateProcessor($key, $this, $value);
			$fluidTemplate->assign($key, $evaluatedValue);
		}

			// TODO this should be done differently lateron
		$fluidTemplate->assign('fluidTemplateTsObject', $this);
		$fluidTemplate->assign('context', $context);

		$sectionName = $this->tsValue('sectionName');

		if ($sectionName !== NULL) {
			return $fluidTemplate->renderSection($sectionName);
		} else {
			return $fluidTemplate->render();
		}
	}

	/**
	 * @param mixed $offset
	 * @return boolean
	 */
	public function offsetExists($offset) {
		return isset($this->variables[$offset]);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		return $this->variables[$offset];
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		$this->variables[$offset] = $value;
	}

	/**
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset) {
		unset($this->variables[$offset]);
	}
}
?>