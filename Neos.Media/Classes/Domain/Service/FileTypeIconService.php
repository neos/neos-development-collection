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

use Neos\Media\Domain\Model\AssetInterface;

/**
 * Service that retrieves an icon for the filetype of a given asset
 */
class FileTypeIconService
{
    /**
     * Returns an icon for a filetype within given dimensions
     *
     * @param AssetInterface $asset
     * @param integer $maximumWidth
     * @param integer $maximumHeight
     * @return array
     */
    public static function getIcon(AssetInterface $asset, $maximumWidth, $maximumHeight)
    {
        // TODO: Could be configurable at some point
        $iconPackage = 'Neos.Media';

        $iconSize = self::getDocumentIconSize($maximumWidth, $maximumHeight);

        if (is_file('resource://' . $iconPackage . '/Public/Icons/16px/' . $asset->getResource()->getFileExtension() . '.png')) {
            $icon = sprintf('Icons/%spx/' . $asset->getResource()->getFileExtension() . '.png', $iconSize);
        } else {
            $icon = sprintf('Icons/%spx/_blank.png', $iconSize);
        }

        return [
            'width' => $iconSize,
            'height' => $iconSize,
            'src' => 'resource://' . $iconPackage . '/Public/' . $icon,
            'alt' => $asset->getResource()->getFileExtension()
        ];
    }

    /**
     * @param integer $maximumWidth
     * @param integer $maximumHeight
     * @return integer
     */
    protected static function getDocumentIconSize($maximumWidth, $maximumHeight)
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
