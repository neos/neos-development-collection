<?php
namespace Neos\Media\Domain\Model\AssetSource\Neos;

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
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxy\AssetProxyInterface;
use Neos\Media\Domain\Model\AssetSource\AssetProxy\ProvidesOriginalUriInterface;
use Neos\Media\Domain\Model\AssetSource\AssetSourceInterface;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Exception\AssetServiceException;
use Neos\Media\Exception\ThumbnailServiceException;
use Psr\Http\Message\UriInterface;

final class NeosAssetProxy implements AssetProxyInterface, ProvidesOriginalUriInterface
{
    /**
     * @var NeosAssetSource
     */
    private $assetSource;

    /**
     * @var Asset
     */
    private $asset;

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
     * @param AssetInterface $asset
     * @param NeosAssetSource $assetSource
     */
    public function __construct(AssetInterface $asset, NeosAssetSource $assetSource)
    {
        if (!$asset instanceof Asset) {
            throw new \RuntimeException(sprintf('%s currently only support the concrete Asset implementation because several methods are not part of the AssetInterface yet.', __CLASS__), 1524128585);
        }

        $this->asset = $asset;
        $this->assetSource = $assetSource;
    }

    /**
     * @return AssetSourceInterface
     */
    public function getAssetSource(): AssetSourceInterface
    {
        return $this->assetSource;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->persistenceManager->getIdentifierByObject($this->asset);
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        if (empty($this->asset->getTitle())) {
            return $this->asset->getResource()->getFilename();
        }
        return $this->asset->getTitle();
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->asset->getResource()->getFilename();
    }

    /**
     * @return \DateTimeInterface
     */
    public function getLastModified(): \DateTimeInterface
    {
        return $this->asset->getLastModified();
    }

    /**
     * @return int
     */
    public function getFileSize(): int
    {
        return $this->asset->getResource()->getFileSize() ?? 0;
    }

    /**
     * @return string
     */
    public function getMediaType(): string
    {
        return $this->asset->getMediaType() ?? 'application/octet-stream';
    }

    /**
     * @return int|null
     */
    public function getWidthInPixels(): ?int
    {
        if ($this->asset instanceof ImageInterface) {
            return $this->asset->getWidth() ?? null;
        }
        return 0;
    }

    /**
     * @return int|null
     */
    public function getHeightInPixels(): ?int
    {
        if ($this->asset instanceof ImageInterface) {
            return $this->asset->getHeight() ?? 0;
        }
        return 0;
    }

    /**
     * @return AssetInterface
     */
    public function getAsset(): AssetInterface
    {
        return $this->asset;
    }

    /**
     * @return UriInterface
     * @throws AssetServiceException
     * @throws ThumbnailServiceException
     */
    public function getThumbnailUri(): ?UriInterface
    {
        return $this->assetSource->getThumbnailUriForAsset($this->asset);
    }

    /**
     * @return UriInterface
     * @throws AssetServiceException
     * @throws ThumbnailServiceException
     */
    public function getPreviewUri(): ?UriInterface
    {
        return $this->assetSource->getPreviewUriForAsset($this->asset);
    }

    /**
     * @return null|UriInterface
     */
    public function getOriginalUri(): ?UriInterface
    {
        $uriString = $this->resourceManager->getPublicPersistentResourceUri($this->asset->getResource());
        return $uriString !== false ? new Uri($uriString) : null;
    }

    /**
     * @return resource
     */
    public function getImportStream()
    {
        return $this->asset->getResource()->getStream();
    }

    /**
     * @return string
     */
    public function getLocalAssetIdentifier(): ?string
    {
        return $this->persistenceManager->getIdentifierByObject($this->asset);
    }
}
