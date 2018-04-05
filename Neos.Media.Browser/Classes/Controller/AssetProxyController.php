<?php

namespace Neos\Media\Browser\Controller;

/*
 * This file is part of the Neos.Media.Browser package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
  *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Media\Domain\Model\AssetSource\AssetNotFoundException;
use Neos\Media\Domain\Model\AssetSource\AssetProxy\HasRemoteOriginal;
use Neos\Media\Domain\Model\AssetSource\AssetProxy\SupportsIptcMetadata;
use Neos\Media\Domain\Model\AssetSource\AssetSource;
use Neos\Media\Domain\Model\AssetSource\AssetSourceConnectionException;
use Neos\Media\Domain\Model\ImportedAssetManager;
use Neos\Media\Domain\Model\AssetSource\AssetSourceAwareInterface;
use Neos\Media\Domain\Model\AssetSource\ImportedAsset;
use Neos\Media\Domain\Repository\ImportedAssetRepository;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\ResourceManagement\Exception;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Strategy\AssetModelMappingStrategyInterface;

/**
 * @Flow\Scope("singleton")
 */
class AssetProxyController extends ActionController
{
    /**
     * @Flow\InjectConfiguration(path="assetSources")
     * @var array
     */
    protected $assetSourcesConfiguration;

    /**
     * @var AssetSource[]
     */
    protected $assetSources = [];

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var ImportedAssetRepository
     */
    protected $importedAssetRepository;

    /**
     * @Flow\Inject
     * @var AssetModelMappingStrategyInterface
     */
    protected $assetModelMappingStrategy;

    /**
     * @Flow\Inject
     * @var ImportedAssetManager
     */
    protected $importedAssetManager;

    /**
     * @return void
     */
    public function initializeObject()
    {
        foreach ($this->assetSourcesConfiguration as $assetSourceIdentifier => $assetSourceConfiguration) {
            if (is_array($assetSourceConfiguration)) {
                $this->assetSources[$assetSourceIdentifier] = new $assetSourceConfiguration['assetSource']($assetSourceIdentifier, $assetSourceConfiguration['assetSourceOptions']);
            }
        }
    }

    /**
     * Import a specified asset from the given Asset Source
     *
     * @param string $assetSourceIdentifier Identifier of the asset source to import from, e.g. "neos"
     * @param string $assetIdentifier The asset-source specific identifier of the asset to import
     * @return string
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    public function importAction(string $assetSourceIdentifier, string $assetIdentifier)
    {
        $this->response->setHeader('Content-Type', 'application/json');

        if (!isset($this->assetSources[$assetSourceIdentifier])) {
            $this->response->setStatus(404);
            return '';
        }

        $assetProxyRepository = $this->assetSources[$assetSourceIdentifier]->getAssetProxyRepository();
        try {
            $assetProxy = $assetProxyRepository->getAssetProxy($assetIdentifier);
        } catch (AssetNotFoundException $e) {
            $this->response->setStatus(404);
            return '';
        } catch (AssetSourceConnectionException $e) {
            $this->response->setStatus(500, 'Connection to asset source failed');
            return '';
        }

        if (!$assetProxy instanceof HasRemoteOriginal) {
            $this->response->setStatus(400, 'Cannot import asset which does not have a remote original');
            return '';
        }

        $importedAsset = $this->importedAssetRepository->findOneByAssetSourceIdentifierAndRemoteAssetIdentifier($assetSourceIdentifier, $assetIdentifier);
        if (!$importedAsset instanceof ImportedAsset) {
            try {
                $assetResource = $this->resourceManager->importResource($assetProxy->getOriginalUri());
                $assetResource->setFilename($assetProxy->getFilename());
            } catch (Exception $e) {
                $this->response->setStatus(500, 'Failed importing the asset from the original source.');
                $this->systemLogger->log(sprintf('Failed importing an the asset %s from asset source %s. Original URI: %s. Error: %s', $assetProxy->getFilename(), $assetSourceIdentifier, $assetProxy->getOriginalUri(), $e->getMessage()), LOG_ERR, $e);
                return '';
            }

            /** @var Asset $asset */
            $assetModelClassName = $this->assetModelMappingStrategy->map($assetResource);
            $asset = new $assetModelClassName($assetResource);

            if (!$asset instanceof AssetSourceAwareInterface) {
                throw new \RuntimeException('The asset type ' . $assetModelClassName . ' does not implement the required MediaAssetsSourceAware interface.', 1516630096064);
            }

            $asset->setAssetSourceIdentifier($assetSourceIdentifier);
            if ($assetProxy instanceof SupportsIptcMetadata) {
                $asset->setTitle($assetProxy->getIptcProperty('Title'));
                $asset->setCaption($assetProxy->getIptcProperty('CaptionAbstract'));
            }

            $this->assetRepository->add($asset);

            $localAssetIdentifier = $this->persistenceManager->getIdentifierByObject($asset);

            $importedAsset = new ImportedAsset(
                $assetSourceIdentifier,
                $assetIdentifier,
                $localAssetIdentifier,
                null,
                new \DateTimeImmutable()
            );
            $this->importedAssetManager->registerImportedAsset($importedAsset);
        }

        $assetProxy = new \stdClass();
        $assetProxy->localAssetIdentifier = $importedAsset->getLocalAssetIdentifier();

        return json_encode($assetProxy);
    }
}
