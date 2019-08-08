<?php

declare(strict_types = 1);

namespace Neos\Media\Domain\Service;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\Exception;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetSource\AssetProxy\HasRemoteOriginalInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxy\SupportsIptcMetadataInterface;
use Neos\Media\Domain\Model\AssetSource\AssetSourceAwareInterface;
use Neos\Media\Domain\Model\AssetSource\AssetSourceInterface;
use Neos\Media\Domain\Model\ImportedAsset;
use Neos\Media\Domain\Model\ImportedAssetManager;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Repository\ImportedAssetRepository;
use Neos\Media\Domain\Strategy\AssetModelMappingStrategyInterface;
use Neos\Media\Exception\AssetSourceServiceException;
use Psr\Log\LoggerInterface;

/**
 * A service for Asset Sources and Asset Proxies
 *
 * @Flow\Scope("singleton")
 */
final class AssetSourceService
{
    /**
     * @Flow\InjectConfiguration(path="assetSources")
     * @var array
     */
    protected $assetSourcesConfiguration;

    /**
     * @Flow\Inject
     * @var ImportedAssetManager
     */
    protected $importedAssetManager;

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
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $systemLogger;

    /**
     * @var AssetSourceInterface[]
     */
    private $assetSources = [];

    /**
     * @return AssetSourceInterface[]
     */
    public function getAssetSources(): array
    {
        $this->initialize();
        return $this->assetSources;
    }

    /**
     * Import a specified asset from the given Asset Source
     *
     * @param string $assetSourceIdentifier Identifier of the asset source to import from, e.g. "neos"
     * @param string $assetIdentifier The asset-source specific identifier of the asset to import
     * @return ImportedAsset
     * @throws AssetSourceServiceException
     * @throws \Exception
     */
    public function importAsset(string $assetSourceIdentifier, string $assetIdentifier): ImportedAsset
    {
        $this->initialize();

        if (!isset($this->assetSources[$assetSourceIdentifier])) {
            throw new AssetSourceServiceException(sprintf('Asset source %s was not found.', $assetSourceIdentifier), 1538753977);
        }

        $assetProxyRepository = $this->assetSources[$assetSourceIdentifier]->getAssetProxyRepository();
        $assetProxy = $assetProxyRepository->getAssetProxy($assetIdentifier);

        if (!$assetProxy instanceof HasRemoteOriginalInterface) {
            $this->systemLogger->error(sprintf('Failed importing an the asset %s from asset source %s because it does not have a remote original.', $assetProxy->getFilename(), $assetSourceIdentifier), LogEnvironment::fromMethodName(__METHOD__));
            throw new AssetSourceServiceException(sprintf('Failed importing an the asset %s from asset source %s because it does not have a remote original.', $assetProxy->getFilename(), $assetSourceIdentifier), 1538754066);
        }

        $importedAsset = $this->importedAssetRepository->findOneByAssetSourceIdentifierAndRemoteAssetIdentifier($assetSourceIdentifier, $assetIdentifier);
        if (!$importedAsset instanceof ImportedAsset) {
            try {
                $assetResource = $this->resourceManager->importResource($assetProxy->getImportStream());
                $assetResource->setFilename($assetProxy->getFilename());
            } catch (Exception $exception) {
                $this->systemLogger->error(sprintf('Failed importing an the asset %s from asset source %s. Original URI: %s. Error: %s', $assetProxy->getFilename(), $assetSourceIdentifier, $assetProxy->getImportStream(), $exception->getMessage()), LogEnvironment::fromMethodName(__METHOD__));
                throw $exception;
            }

            /** @var Asset $asset */
            $assetModelClassName = $this->assetModelMappingStrategy->map($assetResource);
            $asset = new $assetModelClassName($assetResource);

            if (!$asset instanceof AssetSourceAwareInterface) {
                throw new AssetSourceServiceException('The asset type ' . $assetModelClassName . ' does not implement the required MediaAssetsSourceAware interface.', 1516630096);
            }

            $asset->setAssetSourceIdentifier($assetSourceIdentifier);
            if ($assetProxy instanceof SupportsIptcMetadataInterface) {
                $asset->setTitle($assetProxy->getIptcProperty('Title'));
                $asset->setCaption($assetProxy->getIptcProperty('CaptionAbstract'));
                $asset->setCopyrightNotice($assetProxy->getIptcProperty('CopyrightNotice'));
            }

            $this->assetRepository->add($asset);

            $localAssetIdentifier = $this->persistenceManager->getIdentifierByObject($asset);

            $importedAsset = new ImportedAsset(
                $assetSourceIdentifier,
                $assetIdentifier,
                $localAssetIdentifier,
                new \DateTimeImmutable(),
                null
            );
            $this->importedAssetManager->registerImportedAsset($importedAsset);
        }
        return $importedAsset;
    }

    /**
     * @return void
     */
    private function initialize(): void
    {
        if ($this->assetSources === []) {
            foreach ($this->assetSourcesConfiguration as $assetSourceIdentifier => $assetSourceConfiguration) {
                if (is_array($assetSourceConfiguration)) {
                    $this->assetSources[$assetSourceIdentifier] = new $assetSourceConfiguration['assetSource']($assetSourceIdentifier, $assetSourceConfiguration['assetSourceOptions'] ?? []);
                }
            }
        }
    }
}
