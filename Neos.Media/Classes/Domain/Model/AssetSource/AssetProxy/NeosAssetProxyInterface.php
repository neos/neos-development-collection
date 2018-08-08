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

use Neos\Media\Domain\Model\AssetInterface;

/**
 * Interface for a stand-in object of remote or already imported assets from an asset source.
 */
interface NeosAssetProxyInterface
{
    public function getAsset(): AssetInterface;
}
