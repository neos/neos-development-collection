<?php
namespace TYPO3\Media\Domain\Service;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Persistence\RepositoryInterface;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\ImageInterface;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\Media\Domain\Model\ThumbnailConfiguration;
use TYPO3\Media\Domain\Repository\AssetRepository;
use TYPO3\Media\Domain\Strategy\AssetUsageStrategyInterface;
use TYPO3\Media\Exception\AssetServiceException;

/**
 * An asset service that handles for example commands on assets, retrieves information
 * about usage of assets and rendering thumbnails.
 *
 * @Flow\Scope("singleton")
 */
class AssetService
{
    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var ThumbnailService
     */
    protected $thumbnailService;

    /**
     * @Flow\Inject
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var array
     */
    protected $usageStrategies;

    /**
     * Returns the repository for an asset
     *
     * @param AssetInterface $asset
     * @return RepositoryInterface
     * @api
     */
    public function getRepository(AssetInterface $asset)
    {
        $assetRepositoryClassName = str_replace('\\Model\\', '\\Repository\\', get_class($asset)) . 'Repository';

        if (class_exists($assetRepositoryClassName)) {
            return $this->objectManager->get($assetRepositoryClassName);
        }

        return $this->objectManager->get(AssetRepository::class);
    }

    /**
     * Calculates the dimensions of the thumbnail to be generated and returns the thumbnail URI.
     * In case of Images this is a thumbnail of the image, in case of other assets an icon representation.
     *
     * @param AssetInterface $asset
     * @param ThumbnailConfiguration $configuration
     * @param ActionRequest $request Request argument must be provided for asynchronous thumbnails
     * @return array|null Array with keys "width", "height" and "src" if the thumbnail generation work or null
     * @throws AssetServiceException
     */
    public function getThumbnailUriAndSizeForAsset(AssetInterface $asset, ThumbnailConfiguration $configuration, ActionRequest $request = null)
    {
        $thumbnailImage = $this->thumbnailService->getThumbnail($asset, $configuration);
        if (!$thumbnailImage instanceof ImageInterface) {
            return null;
        }
        $resource = $thumbnailImage->getResource();
        if ($thumbnailImage instanceof Thumbnail) {
            $staticResource = $thumbnailImage->getStaticResource();
            if ($configuration->isAsync() === true && $resource === null && $staticResource === null) {
                if ($request === null) {
                    throw new AssetServiceException('Request argument must be provided for async thumbnails.', 1447660835);
                }
                $this->uriBuilder->setRequest($request->getMainRequest());
                $uri = $this->uriBuilder
                    ->reset()
                    ->setCreateAbsoluteUri(true)
                    ->uriFor('thumbnail', array('thumbnail' => $thumbnailImage), 'Thumbnail', 'TYPO3.Media');
            } else {
                $uri = $this->thumbnailService->getUriForThumbnail($thumbnailImage);
            }
        } else {
            $uri = $this->resourceManager->getPublicPersistentResourceUri($resource);
        }
        return array(
            'width' => $thumbnailImage->getWidth(),
            'height' => $thumbnailImage->getHeight(),
            'src' => $uri
        );
    }

    /**
     * Returns all registered asset usage strategies
     *
     * @return array<\TYPO3\Media\Domain\Strategy\AssetUsageStrategyInterface>
     * @throws \TYPO3\Flow\Object\Exception\UnknownObjectException
     */
    protected function getUsageStrategies()
    {
        if (is_array($this->usageStrategies)) {
            return $this->usageStrategies;
        }

        $assetUsageStrategieImplementations = $this->reflectionService->getAllImplementationClassNamesForInterface('TYPO3\Media\Domain\Strategy\AssetUsageStrategyInterface');
        foreach ($assetUsageStrategieImplementations as $assetUsageStrategieImplementationClassName) {
            $this->usageStrategies[] = $this->objectManager->get($assetUsageStrategieImplementationClassName);
        }

        return $this->usageStrategies;
    }

    /**
     * Returns an array of asset usage references.
     *
     * @param AssetInterface $asset
     * @return array<\TYPO3\Media\Domain\Model\Dto\UsageReference>
     */
    public function getUsageReferences(AssetInterface $asset)
    {
        $usages = [];
        /** @var AssetUsageStrategyInterface $strategy */
        foreach ($this->getUsageStrategies() as $strategy) {
            $usages = Arrays::arrayMergeRecursiveOverrule($usages, $strategy->getUsageReferences($asset));
        }

        return $usages;
    }

    /**
     * Returns the total count of times an asset is used.
     *
     * @param AssetInterface $asset
     * @return integer
     */
    public function getUsageCount(AssetInterface $asset)
    {
        $usageCount = 0;
        /** @var AssetUsageStrategyInterface $strategy */
        foreach ($this->getUsageStrategies() as $strategy) {
            $usageCount += $strategy->getUsageCount($asset);
        }

        return $usageCount;
    }

    /**
     * Returns true if the asset is used.
     *
     * @param AssetInterface $asset
     * @return boolean
     */
    public function isInUse(AssetInterface $asset)
    {
        /** @var AssetUsageStrategyInterface $strategy */
        foreach ($this->getUsageStrategies() as $strategy) {
            if ($strategy->isInUse($asset) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validates if the asset can be removed
     *
     * @param AssetInterface $asset
     * @throws AssetServiceException Thrown if the asset can not be removed
     * @return void
     */
    public function validateRemoval(AssetInterface $asset)
    {
        if ($this->isInUse($asset)) {
            throw new AssetServiceException('Asset could not be deleted, because it is still in use.', 1462196420);
        }
    }
}
