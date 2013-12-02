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
 * A TypoScript UriBuilder object
 *
 */
class UriBuilderImplementation extends TemplateImplementation {

	/**
	 * @var string
	 */
	protected $package;

	/**
	 * @var string
	 */
	protected $subpackage;

	/**
	 * @var string
	 */
	protected $controller;

	/**
	 * @var string
	 */
	protected $action;

	/**
	 * @var array
	 */
	protected $arguments;

	/**
	 * @var string
	 */
	protected $format;

	/**
	 * @var string
	 */
	protected $section;

	/**
	 * @var array
	 */
	protected $additionalParams;

	/**
	 * @var boolean
	 */
	protected $addQueryString;

	/**
	 * @var array
	 */
	protected $argumentsToBeExcludedFromQueryString;

	/**
	 * @var boolean
	 */
	protected $absolute;

	/**
	 * @return string
	 */
	public function evaluate() {
		$controllerContext = $this->getTsRuntime()->getControllerContext();
		$uriBuilder = $controllerContext->getUriBuilder()->reset();

		if ($this->getFormat() !== NULL) {
			$uriBuilder->setFormat($this->getFormat());
		}

		if ($this->getAdditionalParams() !== NULL) {
			$uriBuilder->setArguments($this->getAdditionalParams());
		}

		if ($this->getArgumentsToBeExcludedFromQueryString() !== NULL) {
			$uriBuilder->setArgumentsToBeExcludedFromQueryString($this->getArgumentsToBeExcludedFromQueryString());
		}

		if ($this->isAbsolute() === TRUE) {
			$uriBuilder->setCreateAbsoluteUri($this->isAbsolute());
		}

		if ($this->getSection() !== NULL) {
			$uriBuilder->setSection($this->getSection());
		}

		if ($this->isAddQueryString() === TRUE) {
			$uriBuilder->setAddQueryString(TRUE);
		}

		try {
			return $uriBuilder->uriFor(
				$this->getAction(),
				$this->getArguments(),
				$this->getController(),
				$this->getPackage(),
				$this->getSubpackage()
			);
		} catch(\Exception $exception) {
			return $this->tsRuntime->handleRenderingException($this->path, $exception);
		}
	}

	/**
	 * @param boolean $absolute
	 * @return void
	 */
	public function setAbsolute($absolute) {
		$this->absolute = $absolute;
	}

	/**
	 * @return boolean
	 */
	public function isAbsolute() {
		return $this->tsValue('absolute');
	}

	/**
	 * @param string $action
	 * @return void
	 */
	public function setAction($action) {
		$this->action = $action;
	}

	/**
	 * @return string
	 */
	public function getAction() {
		return $this->tsValue('action');
	}

	/**
	 * @param boolean $addQueryString
	 * @return void
	 */
	public function setAddQueryString($addQueryString) {
		$this->addQueryString = $addQueryString;
	}

	/**
	 * @return boolean
	 */
	public function isAddQueryString() {
		return $this->tsValue('addQueryString');
	}

	/**
	 * @param array $additionalParams
	 * @return void
	 */
	public function setAdditionalParams(array $additionalParams) {
		$this->additionalParams = $additionalParams;
	}

	/**
	 * @return array
	 */
	public function getAdditionalParams() {
		return $this->tsValue('additionalParams');
	}

	/**
	 * @param array $arguments
	 * @return void
	 */
	public function setArguments(array $arguments) {
		$this->arguments = $arguments;
	}

	/**
	 * @return array
	 */
	public function getArguments() {
		return $this->tsValue('arguments');
	}

	/**
	 * @param array $argumentsToBeExcludedFromQueryString
	 * @return void
	 */
	public function setArgumentsToBeExcludedFromQueryString(array $argumentsToBeExcludedFromQueryString) {
		$this->argumentsToBeExcludedFromQueryString = $argumentsToBeExcludedFromQueryString;
	}

	/**
	 * @return array
	 */
	public function getArgumentsToBeExcludedFromQueryString() {
		return $this->tsValue('argumentsToBeExcludedFromQueryString');
	}

	/**
	 * @param string $controller
	 * @return void
	 */
	public function setController($controller) {
		$this->controller = $controller;
	}

	/**
	 * @return string
	 */
	public function getController() {
		return $this->tsValue('controller');
	}

	/**
	 * @param string $format
	 * @return void
	 */
	public function setFormat($format) {
		$this->format = $format;
	}

	/**
	 * @return string
	 */
	public function getFormat() {
		return $this->tsValue('format');
	}

	/**
	 * @param string $package
	 * @return void
	 */
	public function setPackage($package) {
		$this->package = $package;
	}

	/**
	 * @return string
	 */
	public function getPackage() {
		return $this->tsValue('package');
	}

	/**
	 * @param string $section
	 * @return void
	 */
	public function setSection($section) {
		$this->section = $section;
	}

	/**
	 * @return string
	 */
	public function getSection() {
		return $this->tsValue('section');
	}

	/**
	 * @param string $subpackage
	 * @return void
	 */
	public function setSubpackage($subpackage) {
		$this->subpackage = $subpackage;
	}

	/**
	 * @return string
	 */
	public function getSubpackage() {
		return $this->tsValue('subpackage');
	}

}
