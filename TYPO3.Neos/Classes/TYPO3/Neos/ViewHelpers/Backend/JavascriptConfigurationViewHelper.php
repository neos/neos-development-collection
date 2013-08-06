<?php
namespace TYPO3\Neos\ViewHelpers\Backend;

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
 * ViewHelper for the backend 'container'. Renders the required HTML to integrate
 * the Neos backend into a website.
 */
class JavascriptConfigurationViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var \TYPO3\Neos\Cache\CacheManager
	 * @Flow\Inject
	 */
	protected $cacheManager;

	/**
	 * @var \TYPO3\Flow\Core\Bootstrap
	 * @Flow\Inject
	 */
	protected $bootstrap;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Resource\Publishing\ResourcePublisher
	 */
	protected $resourcePublisher;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\I18n\Service
	 */
	protected $i18nService;

	/**
	 * @return string
	 */
	public function render() {
		$configurationCacheIdentifier = $this->cacheManager->getConfigurationCacheVersion();

		$vieSchemaUri = $this->controllerContext->getUriBuilder()
			->reset()
			->setCreateAbsoluteUri(TRUE)
			->uriFor('vieSchema', array('version' => $configurationCacheIdentifier), 'Backend\Schema', 'TYPO3.Neos');

		$nodeTypeSchemaUri = $this->controllerContext->getUriBuilder()
			->reset()
			->setCreateAbsoluteUri(TRUE)
			->uriFor('nodeTypeSchema', array('version' => $configurationCacheIdentifier), 'Backend\Schema', 'TYPO3.Neos');

		$menuDataUri = $this->controllerContext->getUriBuilder()
			->reset()
			->setCreateAbsoluteUri(TRUE)
			->uriFor('index', array('version' => $configurationCacheIdentifier), 'Backend\Menu', 'TYPO3.Neos');

		$configuration = array(
			'window.T3Configuration = {};',
			'window.T3Configuration.NodeTypeSchemaUri = ' . json_encode($nodeTypeSchemaUri) . ';',
			'window.T3Configuration.VieSchemaUri = ' . json_encode($vieSchemaUri) . ';',
			'window.T3Configuration.MenuDataUri = ' . json_encode($menuDataUri) . ';',
			'window.T3Configuration.UserInterface = ' . json_encode($this->settings['userInterface']) . ';',
			'window.T3Configuration.enableAloha = ' . json_encode($this->settings['enableAloha']) . ';',
			'window.T3Configuration.nodeTypeGroups = ' . json_encode($this->settings['nodeTypeGroups']) . ';'
		);

		$resourcePath = 'resource://TYPO3.Neos/Public/JavaScript';
		$localizedResourcePathData = $this->i18nService->getLocalizedFilename($resourcePath);
		$matches = array();
		if (preg_match('#resource://([^/]+)/Public/(.*)#', current($localizedResourcePathData), $matches) === 1) {
			$package = $matches[1];
			$path = $matches[2];
		}
		$neosJavaScriptBasePath = $this->resourcePublisher->getStaticResourcesWebBaseUri() . 'Packages/' . $package . '/' . $path;

		$configuration[] = 'window.T3Configuration.neosJavascriptBasePath = ' . json_encode($neosJavaScriptBasePath) . ';';

		if ($this->bootstrap->getContext()->isDevelopment()) {
			$configuration[] = 'window.T3Configuration.DevelopmentMode = true;';
		}

		return (implode("\n", $configuration));
	}

	/**
	 * @param array $settings
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

}
?>