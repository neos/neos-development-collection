<?php
namespace TYPO3\Neos\ViewHelpers\Backend;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\I18n\Service;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\ResourceManagement\ResourceManager;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\Utility\PositionalArraySorter;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Utility\BackendAssetsUtility;

/**
 * ViewHelper for the backend JavaScript configuration. Renders the required JS snippet to configure
 * the Neos backend.
 */
class JavascriptConfigurationViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @Flow\Inject
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var Service
     */
    protected $i18nService;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var BackendAssetsUtility
     */
    protected $backendAssetsUtility;

    /**
     * @Flow\Inject
     * @var DomainRepository
     */
    protected $domainRepository;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @return string
     */
    public function render()
    {
        $configuration = array(
            'window.T3Configuration = {};',
            'window.T3Configuration.UserInterface = ' . json_encode($this->settings['userInterface']) . ';',
            'window.T3Configuration.nodeTypes = {};',
            'window.T3Configuration.nodeTypes.groups = ' . json_encode($this->getNodeTypeGroupsSettings()) . ';',
            'window.T3Configuration.requirejs = {};',
            'window.T3Configuration.neosStaticResourcesBaseUri = ' . json_encode($this->resourceManager->getPublicPackageResourceUri('TYPO3.Neos', '')) . ';',
            'window.T3Configuration.requirejs.paths = ' . json_encode($this->getRequireJsPathMapping()) . ';',
            'window.T3Configuration.maximumFileUploadSize = ' . $this->renderMaximumFileUploadSize()
        );

        $neosJavaScriptBasePath = $this->getStaticResourceWebBaseUri('resource://TYPO3.Neos/Public/JavaScript');

        $configuration[] = 'window.T3Configuration.neosJavascriptBasePath = ' . json_encode($neosJavaScriptBasePath) . ';';
        if ($this->backendAssetsUtility->shouldLoadMinifiedJavascript()) {
            $configuration[] = 'window.T3Configuration.neosJavascriptVersion = ' . json_encode($this->backendAssetsUtility->getJavascriptBuiltVersion()) . ';';
        }

        if ($this->bootstrap->getContext()->isDevelopment()) {
            $configuration[] = 'window.T3Configuration.DevelopmentMode = true;';
        }

        if ($activeDomain = $this->domainRepository->findOneByActiveRequest()) {
            $configuration[] = 'window.T3Configuration.site = "' . $activeDomain->getSite()->getNodeName() . '";';
        }

        return implode("\n", $configuration);
    }

    /**
     * @param string $resourcePath
     * @return string
     */
    protected function getStaticResourceWebBaseUri($resourcePath)
    {
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
    protected function getRequireJsPathMapping()
    {
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
    protected function getNodeTypeGroupsSettings()
    {
        $settings = array();
        $nodeTypeGroupsSettings = new PositionalArraySorter($this->settings['nodeTypes']['groups']);
        foreach ($nodeTypeGroupsSettings->toArray() as $nodeTypeGroupName => $nodeTypeGroupSettings) {
            if (!isset($nodeTypeGroupSettings['label'])) {
                continue;
            }
            $settings[] = array(
                'name' => $nodeTypeGroupName,
                'label' => $nodeTypeGroupSettings['label'],
                'collapsed' => isset($nodeTypeGroupSettings['collapsed']) ? $nodeTypeGroupSettings['collapsed'] : true
            );
        }

        return $settings;
    }

    /**
     * Returns the lowest configured maximum upload file size
     *
     * @return string
     */
    protected function renderMaximumFileUploadSize()
    {
        $maximumFileUploadSizeInBytes = min(Files::sizeStringToBytes(ini_get('post_max_size')), Files::sizeStringToBytes(ini_get('upload_max_filesize')));
        return sprintf('"%d"; // %s, as configured in php.ini', $maximumFileUploadSizeInBytes, Files::bytesToSizeString($maximumFileUploadSizeInBytes));
    }
}
