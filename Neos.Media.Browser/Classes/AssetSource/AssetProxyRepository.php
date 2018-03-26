<?php
namespace Neos\Media\Browser\AssetSource;

/*
 * This file is part of the Neos.Media.Browser package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
  *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Media\Browser\AssetSource\AssetProxy\AssetProxy;
use Neos\Media\Domain\Model\Tag;

interface AssetProxyRepository
{
    /**
     * @param string $identifier
     * @return AssetProxy
     * @throws AssetNotFoundException
     * @throws AssetSourceConnectionException
     */
    public function getAssetProxy(string $identifier): AssetProxy;

    /**
     * @param AssetTypeFilter $assetType
     */
    public function filterByType(AssetTypeFilter $assetType = null): void;

    /**
     * @return AssetProxyQueryResult
     * @throws AssetSourceConnectionException
     */
    public function findAll(): AssetProxyQueryResult;

    /**
     * @param string $searchTerm
     * @return AssetProxyQueryResult
     */
    public function findBySearchTerm(string $searchTerm): AssetProxyQueryResult;

    /**
     * @param Tag $tag
     * @return AssetProxyQueryResult
     */
    public function findByTag(Tag $tag): AssetProxyQueryResult;

    /**
     * @return AssetProxyQueryResult
     */
    public function findUntagged(): AssetProxyQueryResult;

    /**
     * Count all assets, regardless of tag or collection
     *
     * @return int
     */
    public function countAll(): int;
}
