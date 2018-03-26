<?php

namespace Neos\Media\Browser\Service;

/*
* This file is part of the Neos.Media.Browser package.
*
* (c) Contributors of the Neos Project - www.neos.io
*
* This package is Open Source Software. For the full copyright and license
* information, please view the LICENSE file which was distributed with this
* source code.
*/

/**
 * Service that retrieves an icon for the file type of a given filename
 */
class FileTypeIconService
{
    /**
     * Returns an icon for a file type within given dimensions
     *
     * @param string $filename
     * @param integer $maximumWidth
     * @param integer $maximumHeight
     * @return array
     */
    public static function getIcon($filename, $maximumWidth, $maximumHeight)
    {
        $iconPackage = 'Neos.Media';
        $iconSize = self::getDocumentIconSize($maximumWidth, $maximumHeight);

        $pathInfo = pathinfo($filename);
        $fileExtension = isset($pathInfo['extension']) ? $pathInfo['extension'] : '';

        if (is_file('resource://' . $iconPackage . '/Public/Icons/16px/' . $fileExtension . '.png')) {
            $icon = sprintf('Icons/%spx/' . $fileExtension . '.png', $iconSize);
        } else {
            $icon = sprintf('Icons/%spx/_blank.png', $iconSize);
        }

        return [
            'width' => $iconSize,
            'height' => $iconSize,
            'src' => 'resource://' . $iconPackage . '/Public/' . $icon,
            'alt' => $fileExtension
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
