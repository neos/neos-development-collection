<?php
namespace Neos\Media\Domain\Model\AssetSource\AssetProxy;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
  *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Media\Domain\Model\AssetSource\AssetSourceInterface;
use Psr\Http\Message\UriInterface;

/**
 * Interface for a stand-in object of remote or already imported assets from an asset source.
 */
interface AssetProxyInterface
{
    /**
     * @return AssetSourceInterface
     */
    public function getAssetSource(): AssetSourceInterface;

    /**
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * @return string
     */
    public function getLabel(): string;

    /**
     * @return string
     */
    public function getFilename(): string;

    /**
     * @return \DateTimeInterface
     */
    public function getLastModified(): \DateTimeInterface;

    /**
     * @return int
     */
    public function getFileSize(): int;

    /**
     * @return string
     */
    public function getMediaType(): string;

    /**
     * @return int|null
     */
    public function getWidthInPixels(): ?int;

    /**
     * @return int|null
     */
    public function getHeightInPixels(): ?int;

    /**
     * @return null|UriInterface
     */
    public function getThumbnailUri(): ?UriInterface;

    /**
     * @return null|UriInterface
     */
    public function getPreviewUri(): ?UriInterface;

    /**
     * @return resource
     */
    public function getImportStream();

    /**
     * @return null|string
     */
    public function getLocalAssetIdentifier(): ?string;
}
