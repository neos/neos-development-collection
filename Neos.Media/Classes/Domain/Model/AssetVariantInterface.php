<?php
namespace Neos\Media\Domain\Model;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * An interface of an asset which was derived from an original asset
 */
interface AssetVariantInterface extends AssetInterface
{
    /**
     * Returns the Asset this derived asset is based on
     *
     * @return AssetInterface
     * @api
     */
    public function getOriginalAsset();
}
