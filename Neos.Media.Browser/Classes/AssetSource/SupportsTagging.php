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

use Neos\Media\Domain\Model\Tag;

interface SupportsTagging
{
    /**
     * NOTE: This needs to be refactored to use a tag identifier instead of Media's domain model before
     *       it can become a public API for other asset sources.
     *
     * @param Tag $tag
     * @return AssetProxyQueryResult
     */
    public function findByTag(Tag $tag): AssetProxyQueryResult;

    /**
     * @return AssetProxyQueryResult
     */
    public function findUntagged(): AssetProxyQueryResult;

    /**
     * @return int
     */
    public function countUntagged(): int;
}
