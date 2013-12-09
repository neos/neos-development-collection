<?php
namespace TYPO3\TypoScript\TypoScriptObjects\Helpers;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Fluid\Core\Parser\Configuration;
use TYPO3\Fluid\View\StandaloneView;
use TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject;

/**
 * Extended Fluid Template View for use in TypoScript.
 */
class FluidView extends StandaloneView implements TypoScriptAwareViewInterface {

	/**
	 * @var string
	 */
	protected $resourcePackage;

	/**
	 * @var AbstractTypoScriptObject
	 */
	protected $typoScriptObject;

	/**
	 * @param AbstractTypoScriptObject $typoScriptObject
	 * @param ActionRequest $request The current action request. If none is specified it will be created from the environment.
	 */
	public function __construct(AbstractTypoScriptObject $typoScriptObject, ActionRequest $request = NULL) {
		parent::__construct($request);
		$this->typoScriptObject = $typoScriptObject;
	}

	/**
	 * @param string $resourcePackage
	 */
	public function setResourcePackage($resourcePackage) {
		$this->resourcePackage = $resourcePackage;
	}

	/**
	 * @return string
	 */
	public function getResourcePackage() {
		return $this->resourcePackage;
	}

	/**
	 * @return AbstractTypoScriptObject
	 */
	public function getTypoScriptObject() {
		return $this->typoScriptObject;
	}

	/**
	 * Build parser configuration
	 *
	 * @return Configuration
	 */
	protected function buildParserConfiguration() {
		$parserConfiguration = $this->objectManager->get('TYPO3\Fluid\Core\Parser\Configuration');
		if (in_array($this->controllerContext->getRequest()->getFormat(), array('html', NULL))) {
			$resourceInterceptor = $this->objectManager->get('TYPO3\Fluid\Core\Parser\Interceptor\Resource');
			if ($this->resourcePackage !== NULL) {
				$resourceInterceptor->setDefaultPackageKey($this->resourcePackage);
			}
			$parserConfiguration->addInterceptor($this->objectManager->get('TYPO3\Fluid\Core\Parser\Interceptor\Escape'));
			$parserConfiguration->addInterceptor($resourceInterceptor);
		}
		return $parserConfiguration;
	}

}
