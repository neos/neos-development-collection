<?php
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
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Neos\Media\Domain\Model\Thumbnail;
use Neos\Media\Domain\Repository\ThumbnailRepository;
use Neos\Media\Exception\NoThumbnailAvailableException;
use Neos\Media\Exception\ThumbnailServiceException;

/**
 * An internal thumbnail service.
 *
 * Note that this repository is not part of the public API. Use the asset's getThumbnail() method instead.
 *
 * @Flow\Scope("singleton")
 */
class ThumbnailService
{
    /**
     * @Flow\Inject
     * @var ImageService
     */
    protected $imageService;

    /**
     * @Flow\Inject
     * @var ThumbnailRepository
     */
    protected $thumbnailRepository;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

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
     * @Flow\InjectConfiguration("thumbnailPresets")
     * @var boolean
     */
    protected $presets;

    /**
     * @var array
     */
    protected $thumbnailCache = [];

    /**
     * Returns a thumbnail of the given asset
     *
     * If the maximum width / height is not specified or exceeds the original asset's dimensions, the width / height of
     * the original asset is used.
     *
     * @param AssetInterface $asset The asset to render a thumbnail for
     * @param ThumbnailConfiguration $configuration
     * @return ImageInterface
     * @throws \Exception
     */
    public function getThumbnail(AssetInterface $asset, ThumbnailConfiguration $configuration)
    {
        // Calculates the dimensions of the thumbnail to be generated and returns the thumbnail image if the new
        // dimensions differ from the specified image dimensions, otherwise the original image is returned.
        if ($asset instanceof ImageInterface) {
            if ($asset->getWidth() === null && $asset->getHeight() === null) {
                return $asset;
            }
            $maximumWidth = ($configuration->getMaximumWidth() > $asset->getWidth()) ? $asset->getWidth() : $configuration->getMaximumWidth();
            $maximumHeight = ($configuration->getMaximumHeight() > $asset->getHeight()) ? $asset->getHeight() : $configuration->getMaximumHeight();
            if ($configuration->isUpScalingAllowed() === false && $maximumWidth === $asset->getWidth() && $maximumHeight === $asset->getHeight()) {
                return $asset;
            }
        }

        $assetIdentifier = $this->persistenceManager->getIdentifierByObject($asset);
        $configurationHash = $configuration->getHash();
        if (!isset($this->thumbnailCache[$assetIdentifier])) {
            $this->thumbnailCache[$assetIdentifier] = [];
        }
        if (isset($this->thumbnailCache[$assetIdentifier][$configurationHash])) {
            $thumbnail = $this->thumbnailCache[$assetIdentifier][$configurationHash];
        } else {
            $thumbnail = $this->thumbnailRepository->findOneByAssetAndThumbnailConfiguration($asset, $configuration);
            $this->thumbnailCache[$assetIdentifier][$configurationHash] = $thumbnail;
        }
        $async = $configuration->isAsync();
        if ($thumbnail === null) {
            try {
                $thumbnail = new Thumbnail($asset, $configuration, $async);
                $this->emitThumbnailCreated($thumbnail);

                // If the thumbnail strategy failed to generate a valid thumbnail
                if ($async === false && $thumbnail->getResource() === null && $thumbnail->getStaticResource() === null) {
                    $this->thumbnailRepository->remove($thumbnail);
                    return null;
                }

                if (!$this->persistenceManager->isNewObject($asset)) {
                    $this->thumbnailRepository->add($thumbnail);
                }
                $asset->addThumbnail($thumbnail);

                // Allow thumbnails to be persisted even if this is a "safe" HTTP request:
                $this->persistenceManager->whiteListObject($thumbnail);
                $this->thumbnailCache[$assetIdentifier][$configurationHash] = $thumbnail;
            } catch (NoThumbnailAvailableException $exception) {
                $this->systemLogger->logException($exception);
                return null;
            }
            $this->persistenceManager->whiteListObject($thumbnail);
            $this->thumbnailCache[$assetIdentifier][$configurationHash] = $thumbnail;
        } elseif ($thumbnail->getResource() === null && $async === false) {
            try {
                $this->refreshThumbnail($thumbnail);
            } catch (NoThumbnailAvailableException $exception) {
                $this->systemLogger->logException($exception);
                return null;
            }
        }

        return $thumbnail;
    }

    /**
     * @return array Returns preset configuration for all presets
     */
    public function getPresets()
    {
        return $this->presets;
    }

    /**
     * @param string $preset The preset identifier
     * @param boolean $async
     * @return ThumbnailConfiguration
     * @throws ThumbnailServiceException
     */
    public function getThumbnailConfigurationForPreset($preset, $async = false)
    {
        if (!isset($this->presets[$preset])) {
            throw new ThumbnailServiceException(sprintf('Thumbnail preset configuration for "%s" not found.', $preset), 1447664950);
        }
        $presetConfiguration = $this->presets[$preset];
        $thumbnailConfiguration = new ThumbnailConfiguration(
            isset($presetConfiguration['width']) ? $presetConfiguration['width'] : null,
            isset($presetConfiguration['maximumWidth']) ? $presetConfiguration['maximumWidth'] : null,
            isset($presetConfiguration['height']) ? $presetConfiguration['height'] : null,
            isset($presetConfiguration['maximumHeight']) ? $presetConfiguration['maximumHeight'] : null,
            isset($presetConfiguration['allowCropping']) ? $presetConfiguration['allowCropping'] : false,
            isset($presetConfiguration['allowUpScaling']) ? $presetConfiguration['allowUpScaling'] : false,
            $async
        );
        return $thumbnailConfiguration;
    }

    /**
     * Refreshes a thumbnail and persists the thumbnail
     *
     * @param Thumbnail $thumbnail
     * @return void
     */
    public function refreshThumbnail(Thumbnail $thumbnail)
    {
        $thumbnail->refresh();
        $this->persistenceManager->whiteListObject($thumbnail);
        if (!$this->persistenceManager->isNewObject($thumbnail)) {
            $this->thumbnailRepository->update($thumbnail);
        }
    }

    /**
     * @param ImageInterface $thumbnail
     * @return string
     * @throws ThumbnailServiceException
     */
    public function getUriForThumbnail(ImageInterface $thumbnail)
    {
        $resource = $thumbnail->getResource();
        if ($resource) {
            return $this->resourceManager->getPublicPersistentResourceUri($resource);
        }

        $staticResource = $thumbnail->getStaticResource();
        if ($staticResource === null) {
            throw new ThumbnailServiceException(sprintf(
                'Could not generate URI for static thumbnail "%s".',
                $this->persistenceManager->getIdentifierByObject($thumbnail)
            ), 1450178437);
        }

        return $this->resourceManager->getPublicPackageResourceUriByPath($staticResource);
    }

    /**
     * Signals that a thumbnail was created.
     *
     * @Flow\Signal
     * @param Thumbnail $thumbnail
     * @return void
     */
    protected function emitThumbnailCreated(Thumbnail $thumbnail)
    {
    }
}
