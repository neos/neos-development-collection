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

use Neos\Media\Domain\Model\AssetCollection;

/**
 * Interface for an Asset Repository which supports collections
 */
interface SupportsCollectionsInterface
{
    /**
     * NOTE: This needs to be refactored to use an asset collection identifier instead of Media's domain model before
     *       it can become a public API for other asset sources.
     *
     * @param AssetCollection $assetCollection
     */
    public function filterByCollection(AssetCollection $assetCollection = null): void;
}
