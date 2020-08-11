<?php
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
    /**
     * @var string
     */
    private $assetType = 'All';

    /**
     * AssetType constructor.
     *
     * @param string $assetType
     */
    public function __construct(string $assetType)
    {
        if (!in_array($assetType, ['All', 'Image', 'Document', 'Video', 'Audio'])) {
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
