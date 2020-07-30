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
    private $assetType;

    /**
     * AssetType constructor.
     *
     * @param string $assetType
     */
    public function __construct(string $assetType)
    {
        if (!in_array($assetType, self::getAllowedValues(), true)) {
            throw new \InvalidArgumentException(sprintf('Invalid asset type "%s", allowed values are: "%s".', $assetType, implode('", "', self::getAllowedValues())), 1524130064);
        }
        $this->assetType = $assetType;
    }

    /**
     * @return string[]
     */
    public static function getAllowedValues(): array
    {
        return ['All', 'Image', 'Document', 'Video', 'Audio'];
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
    public function jsonSerialize(): string
    {
        return $this->assetType;
    }
}
