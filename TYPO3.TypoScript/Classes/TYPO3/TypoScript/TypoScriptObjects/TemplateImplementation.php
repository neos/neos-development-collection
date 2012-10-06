<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * TypoScript object rendering a fluid template
 *
 * //tsPath variables TODO The result of this TS object is made available inside the template as "variables"
 * @api
 */
class TemplateImplementation extends AbstractTypoScriptObject implements \ArrayAccess {

	/**
	 * Path to the template which should be rendered
	 *
	 * @var string
	 */
	protected $templatePath = NULL;

	/**
	 * Path to the partial root
	 *
	 * @var string
	 */
	protected $partialRootPath = NULL;

	/**
	 * Path to the layout root
	 *
	 * @var string
	 */
	protected $layoutRootPath = NULL;


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
	 * Allows to set the template path.
	 *
	 * @param string $templatePath
	 * @return void
	 */
	public function setTemplatePath($templatePath) {
		$this->templatePath = $templatePath;
	}

	/**
	 * Allows to set the partial root path.
	 *
	 * @param string $partialRootPath
	 * @return void
	 */
	public function setPartialRootPath($partialRootPath) {
		$this->partialRootPath = $partialRootPath;
	}

	/**
	 * Allows to set the layout root path.
	 *
	 * @param string $layoutRootPath
	 * @return void
	 */
	public function setLayoutRootPath($layoutRootPath) {
		$this->layoutRootPath = $layoutRootPath;
	}


	/**
	 * @param string $sectionName
	 * @return void
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
	 * @return string
	 */
	public function evaluate() {
			// while we use the existing ActionRequest if possible, we need to *clone* it as we might need to modify the controllerPackageKey later.
		$fluidTemplate = new \TYPO3\Fluid\View\StandaloneView(($this->tsRuntime->getControllerContext()->getRequest() instanceof \TYPO3\Flow\Mvc\ActionRequest) ? clone $this->tsRuntime->getControllerContext()->getRequest() : NULL);

		$templatePath = $this->tsValue('templatePath');
		if ($templatePath === NULL) {
			throw new \Exception('Template path "' . $templatePath . '" at path "' . $this->path . '"  not found');
		}
		$fluidTemplate->setTemplatePathAndFilename($templatePath);

		$partialRootPath = $this->tsValue('partialRootPath');
		if ($partialRootPath !== NULL) {
			$fluidTemplate->setPartialRootPath($partialRootPath);
		}

		$layoutRootPath = $this->tsValue('layoutRootPath');
		if ($layoutRootPath !== NULL) {
			$fluidTemplate->setLayoutRootPath($layoutRootPath);
		}

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