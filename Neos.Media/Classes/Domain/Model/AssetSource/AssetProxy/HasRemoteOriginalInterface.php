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

/**
 * Interface for an Asset Proxy which depicts an asset whose original binary data is stored in a remote location
 */
interface HasRemoteOriginalInterface
{
    /**
     * Returns true if the binary data of the asset has already been imported into the Neos asset source.
     *
     * @return bool
     */
    public function isImported(): bool;
}
