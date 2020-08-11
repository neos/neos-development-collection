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

use Psr\Http\Message\UriInterface;

/**
 * Interface for an Asset Proxy which provides an URI to the original binary data
 */
interface ProvidesOriginalUriInterface
{
    /**
     * @return null|UriInterface
     */
    public function getOriginalUri(): ?UriInterface;
}
