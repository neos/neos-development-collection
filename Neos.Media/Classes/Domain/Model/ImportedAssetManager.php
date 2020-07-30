<?php
namespace Neos\Media\Domain\Model;

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
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetSource\AssetSourceAwareInterface;
use Neos\Media\Domain\Repository\ImportedAssetRepository;
use Psr\Log\LoggerInterface;

class ImportedAssetManager
{
    /**
     * @Flow\Inject
     * @var  PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var ImportedAssetRepository
     */
    protected $importedAssetRepository;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ImportedAsset $importedAsset
     * @throws IllegalObjectTypeException
     */
    public function registerImportedAsset(ImportedAsset $importedAsset)
    {
        $this->importedAssetRepository->add($importedAsset);
        $this->logger->debug(sprintf('Asset imported: %s / %s as %s', $importedAsset->getAssetSourceIdentifier(), $importedAsset->getRemoteAssetIdentifier(), $importedAsset->getLocalAssetIdentifier()));
    }

    /**
     * Register that an asset was created.
     *
     * Wired via signal-slot with AssetService::assetCreated – see Package.php
     *
     * @param AssetInterface $asset
     * @throws IllegalObjectTypeException
     */
    public function registerCreatedAsset(AssetInterface $asset)
    {
        if (!$asset instanceof AssetVariantInterface || !$asset instanceof AssetSourceAwareInterface) {
            return;
        }

        $variantAssetIdentifier = $this->persistenceManager->getIdentifierByObject($asset);
        $originalAsset = $asset->getOriginalAsset();
        $originalAssetIdentifier = $this->persistenceManager->getIdentifierByObject($originalAsset);

        $originalImportedAsset = $this->importedAssetRepository->findOneByLocalAssetIdentifier($originalAssetIdentifier);
        if ($originalImportedAsset instanceof ImportedAsset) {
            $asset->setAssetSourceIdentifier($originalImportedAsset->getAssetSourceIdentifier());

            $variantImportedAsset = new ImportedAsset(
                $originalImportedAsset->getAssetSourceIdentifier(),
                $originalImportedAsset->getRemoteAssetIdentifier(),
                $variantAssetIdentifier,
                new \DateTimeImmutable(),
                $originalAssetIdentifier
            );

            $this->importedAssetRepository->add($variantImportedAsset);
            $this->logger->debug(sprintf('Asset created: %s / %s', $asset->getResource()->getFilename(), $this->persistenceManager->getIdentifierByObject($asset)));
        }
    }

    /**
     * When an asset was removed (supposedly by a user), also remove the corresponding entry in the imported assets registry
     *
     * Wired via signal-slot with AssetService::assetRemoved – see Package.php

     * @param AssetInterface $asset
     * @throws IllegalObjectTypeException
     */
    public function registerRemovedAsset(AssetInterface $asset)
    {
        $assetIdentifier = $this->persistenceManager->getIdentifierByObject($asset);
        $importedAsset = $this->importedAssetRepository->findOneByLocalAssetIdentifier($assetIdentifier);
        if ($importedAsset !== null) {
            $this->importedAssetRepository->remove($importedAsset);
            $this->logger->debug(sprintf('Asset removed: %s / %s', $asset->getResource()->getFilename(), $this->persistenceManager->getIdentifierByObject($asset)));
        }
    }
}
