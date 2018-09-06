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

use Neos\Media\Domain\Model\AssetSource\AssetProxy\AssetProxyInterface;

interface AssetSourceAwareInterface
{
    /**
     * @param string $assetSourceIdentifier
     */
    public function setAssetSourceIdentifier(string $assetSourceIdentifier): void;

    /**
     * @return string
     */
    public function getAssetSourceIdentifier(): ?string;

    /**
     * @return AssetProxyInterface|null
     */
    public function getAssetProxy(): ?AssetProxyInterface;
}
