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

use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final class NodeTypeNameFactory
{
    public const NAME_CONTENT = 'Neos.Neos:Content';
    public const NAME_CONTENT_COLLECTION = 'Neos.Neos:ContentCollection';
    public const NAME_DOCUMENT = 'Neos.Neos:Document';
    public const NAME_FALLBACK = 'Neos.Neos:FallbackNode';
    public const NAME_SHORTCUT = 'Neos.Neos:Shortcut';
    public const NAME_SITE = 'Neos.Neos:Site';
    public const NAME_SITES = 'Neos.Neos:Sites';

    public static function forContent(): NodeTypeName
    {
        return NodeTypeName::fromString(self::NAME_CONTENT);
    }

    public static function forContentCollection(): NodeTypeName
    {
        return NodeTypeName::fromString(self::NAME_CONTENT_COLLECTION);
    }

    public static function forDocument(): NodeTypeName
    {
        return NodeTypeName::fromString(self::NAME_DOCUMENT);
    }

    public static function forFallback(): NodeTypeName
    {
        return NodeTypeName::fromString(self::NAME_FALLBACK);
    }

    public static function forShortcut(): NodeTypeName
    {
        return NodeTypeName::fromString(self::NAME_SHORTCUT);
    }

    public static function forSite(): NodeTypeName
    {
        return NodeTypeName::fromString(self::NAME_SITE);
    }

    public static function forSites(): NodeTypeName
    {
        return NodeTypeName::fromString(self::NAME_SITES);
    }

    public static function forRoot(): NodeTypeName
    {
        return NodeTypeName::fromString(NodeTypeName::ROOT_NODE_TYPE_NAME);
    }
}
