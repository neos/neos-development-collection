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
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\ImageInterface;
use TYPO3\Media\Domain\Model\ThumbnailConfiguration;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\Media\Domain\Repository\ThumbnailRepository;
use TYPO3\Media\Exception\NoThumbnailAvailableException;
use TYPO3\Media\Exception\ThumbnailServiceException;

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
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Resource\ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\InjectConfiguration("thumbnailPresets")
     * @var boolean
     */
    protected $presets;

    /**
     * Returns a thumbnail of the given asset
     *
     * If the maximum width / height is not specified or exceeds the original asset's dimensions, the width / height of
     * the original asset is used.
     *
     * @param AssetInterface $asset The asset to render a thumbnail for
     * @param ThumbnailConfiguration $configuration
     * @param boolean $async Create asynchronous thumbnail if it doesn't already exist
     * @return Thumbnail
     * @throws \Exception
     */
    public function getThumbnail(AssetInterface $asset, ThumbnailConfiguration $configuration, $async = false)
    {
        $thumbnail = $this->thumbnailRepository->findOneByAssetAndThumbnailConfiguration($asset, $configuration);
        if ($thumbnail === null) {
            if (!$asset instanceof ImageInterface) {
                throw new NoThumbnailAvailableException(sprintf('ThumbnailService could not generate a thumbnail for asset of type "%s" because currently only Image assets are supported.', get_class($asset)), 1381493670);
            }
            $thumbnail = new Thumbnail($asset, $configuration, $async);
            $this->thumbnailRepository->add($thumbnail);
            $asset->addThumbnail($thumbnail);

            // Allow thumbnails to be persisted even if this is a "safe" HTTP request:
            $this->persistenceManager->whiteListObject($thumbnail);
        } elseif ($thumbnail->getResource() === null && $async === false) {
            $thumbnail->refresh();
            $this->persistenceManager->whiteListObject($thumbnail);
            $this->thumbnailRepository->update($thumbnail);
        }

        return $thumbnail;
    }

    /**
     * @param AssetInterface $asset
     * @param integer $maximumWidth
     * @param integer $maximumHeight
     * @return array
     */
    public function getStaticThumbnailForAsset(AssetInterface $asset, $maximumWidth, $maximumHeight)
    {
        $iconSize = $this->getDocumentIconSize($maximumWidth, $maximumHeight);

        if (is_file('resource://TYPO3.Media/Public/Icons/16px/' . $asset->getResource()->getFileExtension() . '.png')) {
            $icon = sprintf('Icons/%spx/' . $asset->getResource()->getFileExtension() . '.png', $iconSize);
        } else {
            $icon = sprintf('Icons/%spx/_blank.png', $iconSize);
        }

        $icon = $this->resourceManager->getPublicPackageResourceUri('TYPO3.Media', $icon);

        return array(
            'width' => $iconSize,
            'height' => $iconSize,
            'src' => $icon
        );
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
     * @return ThumbnailConfiguration
     * @throws ThumbnailServiceException
     */
    public function getThumbnailConfigurationForPreset($preset)
    {
        if (!isset($this->presets[$preset])) {
            throw new ThumbnailServiceException(sprintf('Thumbnail preset configuration for "%s" not found.', $preset), 1447664950);
        }
        $thumbnailConfiguration = new ThumbnailConfiguration;
        call_user_func_array(array($thumbnailConfiguration, '__construct'), $this->presets[$preset]);
        return $thumbnailConfiguration;
    }

    /**
     * @param integer $maximumWidth
     * @param integer $maximumHeight
     * @return integer
     */
    protected function getDocumentIconSize($maximumWidth, $maximumHeight)
    {
        $size = max($maximumWidth, $maximumHeight);
        if ($size <= 16) {
            return 16;
        } elseif ($size <= 32) {
            return 32;
        } elseif ($size <= 48) {
            return 48;
        } else {
            return 512;
        }
    }
}
