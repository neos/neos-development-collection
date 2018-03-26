<?php
namespace Neos\Media\Browser\AssetSource;

/*
 * This file is part of the Neos.Media.Browser package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 * (c) Robert Lemke, Flownative GmbH - www.flownative.com
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Media\Browser\AssetSource\AssetProxy\AssetProxy;

interface MediaAssetSourceAware
{
    /**
     * @param string $assetSourceIdentifier
     */
    public function setAssetSourceIdentifier(string $assetSourceIdentifier);

    /**
     * @return string
     */
    public function getAssetSourceIdentifier();

    /**
     * @return AssetSource|null
     */
    public function getAssetSource();

    /**
     * @return AssetProxy|null
     */
    public function getAssetProxy();
}
