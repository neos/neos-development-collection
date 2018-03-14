<?php

namespace Neos\ContentRepository\Domain\Projection\Content;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;

final class RootNodeIdentifiers
{
    /**
     * @var ContentStreamIdentifier
     */
    private static $rootContentStreamIdentifier;

    /**
     * @var DimensionSpacePoint
     */
    private static $rootDimensionSpacePoint;


    /**
     * @var NodeAggregateIdentifier
     */
    private static $rootNodeAggregateIdentifier;

    /**
     * the Root node is part of *every* content stream; thus the Root node is assigned a special
     * "root" content stream.
     *
     * @return ContentStreamIdentifier
     */
    public static function rootContentStreamIdentifier(): ContentStreamIdentifier
    {
        if (!self::$rootContentStreamIdentifier) {
            self::$rootContentStreamIdentifier = new ContentStreamIdentifier('00000000-0000-0000-0000-000000000000');
        }
        return self::$rootContentStreamIdentifier;
    }

    /**
     * the Root node is part of *every* dimension; thus the Root node is assigned a special
     * "root" dimension.
     *
     * @return DimensionSpacePoint
     */
    public static function rootDimensionSpacePoint(): DimensionSpacePoint
    {
        if (!self::$rootDimensionSpacePoint) {
            self::$rootDimensionSpacePoint = new DimensionSpacePoint([]);
        }
        return self::$rootDimensionSpacePoint;
    }

    /**
     * the Root node is its own Node Aggregate
     *
     * @return NodeAggregateIdentifier
     */
    public static function rootNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        if (!self::$rootNodeAggregateIdentifier) {
            self::$rootNodeAggregateIdentifier = new NodeAggregateIdentifier('00000000-0000-0000-0000-000000000000');
        }
        return self::$rootNodeAggregateIdentifier;
    }
}
