<?php
namespace Neos\Neos\Utility;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A collection of helper methods for the Neos backend assets
 */
class BackendAssetsUtility
{
    /**
     * Returns a shortened md5 of the built CSS file
     *
     * @return string
     */
    public function getCssBuiltVersion()
    {
        return substr(md5_file('resource://Neos.Neos/Public/Styles/Lite.css') ?: '', 0, 12);
    }
}
