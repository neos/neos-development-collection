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
class TemplateImplementation extends AbstractTypoScriptObject {

	/**
	 * Path to the template which should be rendered
	 *
	 * @return string
	 */
	public function getTemplatePath() {
		return $this->tsValue('templatePath');
	}

	/**
	 * Path to the partial root
	 *
	 * @return string
	 */
	public function getPartialRootPath() {
		return $this->tsValue('partialRootPath');
	}

	/**
	 * Path to the layout root
	 *
	 * @return string
	 */
	public function getLayoutRootPath() {
		return $this->tsValue('layoutRootPath');
	}

	/**
	 * Name of a specific section, if only this section should be rendered.
	 *
	 * @return string
	 */
	public function getSectionName() {
		return $this->tsValue('sectionName');
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
		$fluidTemplate = new Helpers\FluidView(($this->tsRuntime->getControllerContext()->getRequest() instanceof \TYPO3\Flow\Mvc\ActionRequest) ? $this->tsRuntime->getControllerContext()->getRequest() : NULL);

		$templatePath = $this->getTemplatePath();
		if ($templatePath === NULL) {
			throw new \Exception(sprintf('Template path "%s" at path "%s"  not found', $templatePath, $this->path));
		}
		$fluidTemplate->setTemplatePathAndFilename($templatePath);

		$partialRootPath = $this->getPartialRootPath();
		if ($partialRootPath !== NULL) {
			$fluidTemplate->setPartialRootPath($partialRootPath);
		}

		$layoutRootPath = $this->getLayoutRootPath();
		if ($layoutRootPath !== NULL) {
			$fluidTemplate->setLayoutRootPath($layoutRootPath);
		}

			// Template resources need to be evaluated from the templates package not the requests package.
		if (strpos($templatePath, 'resource://') === 0) {
			$templateResourcePathParts = parse_url($templatePath);
			$fluidTemplate->setResourcePackage($templateResourcePathParts['host']);
		}

		foreach ($this->properties as $key => $value) {
			if (!is_array($value)) {
					// if a value is a SIMPLE TYPE, e.g. neither an Eel expression nor a TypoScript object,
					// we can just evaluate it (to handle processors) and then assign it to the template.
				$evaluatedValue = $this->tsValue($key);
				$fluidTemplate->assign($key, $evaluatedValue);
			} else {
					// It is an array; so we need to create a "proxy" for lazy evaluation, as it could be a
					// nested TypoScript object, Eel expression or simple value.
				$fluidTemplate->assign($key, new Helpers\TypoScriptPathProxy($this, $this->path . '/' . $key, $value));
			}
		}

		$this->initializeView($fluidTemplate);

			// TODO this should be done differently lateron
		$fluidTemplate->assign('fluidTemplateTsObject', $this);

		$sectionName = $this->getSectionName();

		if ($sectionName !== NULL) {
			return $fluidTemplate->renderSection($sectionName);
		} else {
			return $fluidTemplate->render();
		}
	}

	/**
	 * This is a template method which can be overridden in subclasses to add new variables which should
	 * be available inside the Fluid template. It is needed e.g. for Expose.
	 *
	 * @param Helpers\FluidView $view
	 * @return void
	 */
	protected function initializeView(Helpers\FluidView $view) {
		// template method
	}
}
