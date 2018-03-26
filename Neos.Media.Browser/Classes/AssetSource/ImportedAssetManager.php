<?php
namespace Neos\Media\Browser\AssetSource;

/*
 * This file is part of the Neos.Media.Browser package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
  *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Media\Browser\Domain\Model\ImportedAsset;
use Neos\Media\Browser\Domain\Repository\ImportedAssetRepository;
use Neos\Flow\Annotations\Inject;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetVariantInterface;

class ImportedAssetManager
{
    /**
     * @Inject()
     * @var  PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Inject()
     * @var ImportedAssetRepository
     */
    protected $importedAssetRepository;

    /**
     * @Inject()
     * @var SystemLoggerInterface
     */
    protected $logger;

    /**
     * @param ImportedAsset $importedAsset
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    public function registerImportedAsset(ImportedAsset $importedAsset)
    {
        $this->importedAssetRepository->add($importedAsset);
        $this->logger->log(sprintf('Asset imported: %s / %s as %s', $importedAsset->getAssetSourceIdentifier(), $importedAsset->getRemoteAssetIdentifier(), $importedAsset->getLocalAssetIdentifier()), LOG_DEBUG);
    }

    /**
     * Register that an asset was created.
     *
     * Wired via signal-slot with AssetService::assetCreated – see Package.php
     *
     * @param AssetInterface $asset
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    public function registerCreatedAsset(AssetInterface $asset)
    {
        if (!$asset instanceof AssetVariantInterface) {
            return;
        }

        $variantAsset = $asset;
        $variantAssetIdentifier = $this->persistenceManager->getIdentifierByObject($asset);
        $originalAsset = $asset->getOriginalAsset();
        $originalAssetIdentifier = $this->persistenceManager->getIdentifierByObject($originalAsset);

        $originalImportedAsset = $this->importedAssetRepository->findOneByLocalAssetIdentifier($originalAssetIdentifier);
        if ($originalImportedAsset instanceof ImportedAsset && $variantAsset instanceof MediaAssetSourceAware) {
            $variantAsset->setAssetSourceIdentifier($originalImportedAsset->getAssetSourceIdentifier());

            $variantImportedAsset = new ImportedAsset(
                $originalImportedAsset->getAssetSourceIdentifier(),
                $originalImportedAsset->getRemoteAssetIdentifier(),
                $variantAssetIdentifier,
                $originalAssetIdentifier,
                new \DateTimeImmutable()
            );

            $this->importedAssetRepository->add($variantImportedAsset);
            $this->logger->log(sprintf('Asset created: %s / %s', $asset->getResource()->getFilename(), $this->persistenceManager->getIdentifierByObject($asset)), LOG_DEBUG);
        }
    }

    /**
     * When an asset was removed (supposedly by a user), also remove the corresponding entry in the imported assets registry
     *
     * Wired via signal-slot with AssetService::assetRemoved – see Package.php

     * @param AssetInterface $asset
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    public function registerRemovedAsset(AssetInterface $asset)
    {
        $assetIdentifier = $this->persistenceManager->getIdentifierByObject($asset);
        $importedAsset = $this->importedAssetRepository->findOneByLocalAssetIdentifier($assetIdentifier);
        if ($importedAsset !== null) {
            $this->importedAssetRepository->remove($importedAsset);
            $this->logger->log(sprintf('Asset removed: %s / %s', $asset->getResource()->getFilename(), $this->persistenceManager->getIdentifierByObject($asset)), LOG_DEBUG);
        }
    }
}
