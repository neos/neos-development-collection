<?php
declare(strict_types=1);

namespace Neos\Media\Domain\Model\AssetSource;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
  *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

final class AssetTypeFilter implements \JsonSerializable
{
    const TYPE_ALL = 'All';
    const TYPE_IMAGE = 'Image';
    const TYPE_DOCUMENT = 'Document';
    const TYPE_VIDEO = 'Video';
    const TYPE_AUDIO = 'Audio';

    /**
     * @var string
     */
    private $assetType;

    /**
     * AssetType constructor.
     *
     * @param string $assetType
     */
    public function __construct(string $assetType)
    {
        if (!in_array($assetType, [static::TYPE_ALL, static::TYPE_IMAGE, static::TYPE_DOCUMENT, static::TYPE_VIDEO, static::TYPE_AUDIO])) {
            throw new \InvalidArgumentException(sprintf('Invalid asset type "%s".', $assetType), 1524130064);
        }
        $this->assetType = $assetType;
    }

    /**
     * @return string
     */
    public function getAssetType(): string
    {
        return $this->assetType;
    }

    /**
     * @return bool
     */
    public function hasAllAllowed(): bool
    {
        return $this->assetType === static::TYPE_ALL;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->assetType;
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->assetType;
    }
}
