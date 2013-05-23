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

/**
 * Extended Fluid Template View for use in TypoScript.
 *
 */
class FluidView extends \TYPO3\Fluid\View\StandaloneView {

	/**
	 * @var string
	 */
	protected $resourcePackage;

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
	 * Build parser configuration
	 *
	 * @return \TYPO3\Fluid\Core\Parser\Configuration
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
?>