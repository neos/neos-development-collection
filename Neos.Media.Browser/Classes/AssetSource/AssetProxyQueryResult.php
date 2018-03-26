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

interface AssetProxyQueryResult extends \Countable, \Iterator, \ArrayAccess
{
    /**
     * Returns a clone of the query object
     *
     * @return AssetProxyQuery
     */
    public function getQuery(): AssetProxyQuery;

    /**
     * Returns the first asset proxy in the result set
     *
     * @return AssetProxy|null
     */
    public function getFirst(): ?AssetProxy;

    /**
     * Returns an array with the asset proxies in the result set
     *
     * @return AssetProxy[]
     */
    public function toArray(): array;
}
