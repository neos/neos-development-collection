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
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Utility\PositionalArraySorter;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Flow\Log\SystemLoggerInterface;


/**
 * ViewHelper for the backend JavaScript configuration. Renders the required JS snippet to configure
 * the Neos backend.
 */
class JavascriptConfigurationViewHelper extends AbstractViewHelper {

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
	 * @var \TYPO3\Flow\Resource\ResourceManager
	 */
	protected $resourceManager;

	/**
	 * @Flow\Inject
	 * @var SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\I18n\Service
	 */
	protected $i18nService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject(setting="userInterface.defaultLocale")
	 * @var string
	 */
	protected $defaultLocale;

	/**
	 * @return string
	 */
	public function render() {
		$configuration = array(
			'window.T3Configuration = {};',
			'window.T3Configuration.locale = "' . $this->defaultLocale . '";',
			'window.T3Configuration.localeIncludes = ' . json_encode($this->getXliffAsJsonUri()) . ';',
			'window.T3Configuration.UserInterface = ' . json_encode($this->settings['userInterface']) . ';',
			'window.T3Configuration.nodeTypes = {};',
			'window.T3Configuration.nodeTypes.groups = ' . json_encode($this->getNodeTypeGroupsSettings()) . ';',
			'window.T3Configuration.requirejs = {};',
			'window.T3Configuration.neosStaticResourcesBaseUri = ' . json_encode($this->resourceManager->getPublicPackageResourceUri('TYPO3.Neos', '')) . ';',
			'window.T3Configuration.requirejs.paths = ' . json_encode($this->getRequireJsPathMapping()) . ';'
		);

		$neosJavaScriptBasePath = $this->getStaticResourceWebBaseUri('resource://TYPO3.Neos/Public/JavaScript');

		$configuration[] = 'window.T3Configuration.neosJavascriptBasePath = ' . json_encode($neosJavaScriptBasePath) . ';';

		if ($this->bootstrap->getContext()->isDevelopment()) {
			$configuration[] = 'window.T3Configuration.DevelopmentMode = true;';
		}

		return (implode("\n", $configuration));
	}

	/**
	 * Returns array with the I18n json uri
	 *
	 * @return array
	 */
	protected function getXliffAsJsonUri() {
		$uriBuilder = $this->controllerContext->getUriBuilder();
		$uriBuilder->setCreateAbsoluteUri(TRUE);

		return array($uriBuilder->uriFor('getXliffAsJson', array(), 'Backend\\Backend', 'TYPO3.Neos'));
	}

	/**
	 * @param string $resourcePath
	 * @return string
	 */
	protected function getStaticResourceWebBaseUri($resourcePath) {
		$localizedResourcePathData = $this->i18nService->getLocalizedFilename($resourcePath);

		$matches = array();
		try {
			if (preg_match('#resource://([^/]+)/Public/(.*)#', current($localizedResourcePathData), $matches) === 1) {
				$packageKey = $matches[1];
				$path = $matches[2];
				return $this->resourceManager->getPublicPackageResourceUri($packageKey, $path);
			}
		} catch (\Exception $exception) {
			$this->systemLogger->logException($exception);
		}
		return '';
	}

	/**
	 * @return array
	 */
	protected function getRequireJsPathMapping() {
		$pathMappings = array();

		$validatorSettings = ObjectAccess::getPropertyPath($this->settings, 'userInterface.validators');
		if (is_array($validatorSettings)) {
			foreach ($validatorSettings as $validatorName => $validatorConfiguration) {
				if (isset($validatorConfiguration['path'])) {
					$pathMappings[$validatorName] = $this->getStaticResourceWebBaseUri($validatorConfiguration['path']);
				}
			}
		}

		$editorSettings = ObjectAccess::getPropertyPath($this->settings, 'userInterface.inspector.editors');
		if (is_array($editorSettings)) {
			foreach ($editorSettings as $editorName => $editorConfiguration) {
				if (isset($editorConfiguration['path'])) {
					$pathMappings[$editorName] = $this->getStaticResourceWebBaseUri($editorConfiguration['path']);
				}
			}
		}

		$requireJsPathMappingSettings = ObjectAccess::getPropertyPath($this->settings, 'userInterface.requireJsPathMapping');
		if (is_array($requireJsPathMappingSettings)) {
			foreach ($requireJsPathMappingSettings as $namespace => $path) {
				$pathMappings[$namespace] = $this->getStaticResourceWebBaseUri($path);
			}
		}

		return $pathMappings;
	}

	/**
	 * @return array
	 */
	protected function getNodeTypeGroupsSettings() {
		$settings = array();
		$nodeTypeGroupsSettings = new PositionalArraySorter($this->settings['nodeTypes']['groups']);
		foreach ($nodeTypeGroupsSettings->toArray() as $nodeTypeGroupName => $nodeTypeGroupSettings) {
			if (!isset($nodeTypeGroupSettings['label'])) {
				continue;
			}
			$settings[] = array(
				'name' => $nodeTypeGroupName,
				'label' => $nodeTypeGroupSettings['label']
			);
		}

		return $settings;
	}

	/**
	 * @param array $settings
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

}
