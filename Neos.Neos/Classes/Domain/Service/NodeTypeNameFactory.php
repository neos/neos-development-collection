<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\SharedModel\NodeType\NodeTypeName;

#[Flow\Proxy(false)]
final class NodeTypeNameFactory
{
    public const NAME_DOCUMENT = 'Neos.Neos:Document';
    public const NAME_SITE = 'Neos.Neos:Site';
    public const NAME_SITES = 'Neos.Neos:Sites';
    public const NAME_FALLBACK = 'Neos.Neos:FallbackNode';

    public static function forDocument(): NodeTypeName
    {
        return NodeTypeName::fromString(self::NAME_DOCUMENT);
    }

    public static function forSite(): NodeTypeName
    {
        return NodeTypeName::fromString(self::NAME_SITE);
    }

    public static function forSites(): NodeTypeName
    {
        return NodeTypeName::fromString(self::NAME_SITES);
    }

    public static function forFallback(): NodeTypeName
    {
        return NodeTypeName::fromString(self::NAME_FALLBACK);
    }
}
