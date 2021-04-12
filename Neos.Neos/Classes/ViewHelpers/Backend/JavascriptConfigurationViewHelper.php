<?php
namespace Neos\Neos\ViewHelpers\Backend;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\I18n\Service;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Security\Context;
use Neos\Utility\Files;
use Neos\Utility\PositionalArraySorter;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Utility\BackendAssetsUtility;

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
     * @var ThrowableStorageInterface
     */
    private $throwableStorage;

    /**
     * @param ThrowableStorageInterface $throwableStorage
     */
    public function injectThrowableStorage(ThrowableStorageInterface $throwableStorage)
    {
        $this->throwableStorage = $throwableStorage;
    }

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
        $configuration = [
            'window.NeosCMS = !!window.NeosCMS ? window.NeosCMS : {};',
            'window.NeosCMS.Configuration = {};',
            'window.NeosCMS.Configuration.UserInterface = ' . json_encode($this->settings['userInterface']) . ';',
            'window.NeosCMS.Configuration.nodeTypes = {};',
            'window.NeosCMS.Configuration.nodeTypes.groups = ' . json_encode($this->getNodeTypeGroupsSettings()) . ';',
            'window.NeosCMS.Configuration.neosStaticResourcesBaseUri = ' . json_encode($this->resourceManager->getPublicPackageResourceUri('Neos.Neos', '')) . ';',
            'window.NeosCMS.Configuration.maximumFileUploadSize = ' . $this->renderMaximumFileUploadSize()
        ];

        $neosJavaScriptBasePath = $this->getStaticResourceWebBaseUri('resource://Neos.Neos/Public/JavaScript');

        $configuration[] = 'window.NeosCMS.Configuration.neosJavascriptBasePath = ' . json_encode($neosJavaScriptBasePath) . ';';

        if ($this->bootstrap->getContext()->isDevelopment()) {
            $configuration[] = 'window.NeosCMS.Configuration.DevelopmentMode = true;';
        }

        if ($activeDomain = $this->domainRepository->findOneByActiveRequest()) {
            $configuration[] = 'window.NeosCMS.Configuration.site = "' . $activeDomain->getSite()->getNodeName() . '";';
        }

        /**
         * create backwards compatibility
         * @deprecated Can be removed with Neos 8.0
         */
        $configuration[] = 'window.T3Configuration = Object.assign({}, window.NeosCMS.Configuration);';

        /**
         * Add empty requireJS pathes. Don`t remove the whole path to break the external API.
         * @deprecated Can be removed with Neos 8.0
         */
        $configuration[] = 'window.NeosCMS.Configuration.requirejs = {};';
        $configuration[] = 'window.NeosCMS.Configuration.requirejs.paths = [];';

        return implode("\n", $configuration);
    }

    /**
     * @param string $resourcePath
     * @return string
     */
    protected function getStaticResourceWebBaseUri($resourcePath)
    {
        $localizedResourcePathData = $this->i18nService->getLocalizedFilename($resourcePath);

        $matches = [];
        try {
            if (preg_match('#resource://([^/]+)/Public/(.*)#', current($localizedResourcePathData), $matches) === 1) {
                $packageKey = $matches[1];
                $path = $matches[2];
                return $this->resourceManager->getPublicPackageResourceUri($packageKey, $path);
            }
        } catch (\Exception $exception) {
            $logMessage = $this->throwableStorage->logThrowable($exception);
            $this->logger->error($logMessage, LogEnvironment::fromMethodName(__METHOD__));
        }
        return '';
    }

    /**
     * @return array
     */
    protected function getNodeTypeGroupsSettings()
    {
        $settings = [];
        $nodeTypeGroupsSettings = new PositionalArraySorter($this->settings['nodeTypes']['groups']);
        foreach ($nodeTypeGroupsSettings->toArray() as $nodeTypeGroupName => $nodeTypeGroupSettings) {
            if (!isset($nodeTypeGroupSettings['label'])) {
                continue;
            }
            $settings[] = [
                'name' => $nodeTypeGroupName,
                'label' => $nodeTypeGroupSettings['label'],
                'collapsed' => isset($nodeTypeGroupSettings['collapsed']) ? $nodeTypeGroupSettings['collapsed'] : true
            ];
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
