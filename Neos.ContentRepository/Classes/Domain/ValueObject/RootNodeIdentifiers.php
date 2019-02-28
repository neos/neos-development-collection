<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Domain\ValueObject;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\Flow\Annotations as Flow;

/**
 * Internal list of root node identifiers needed at various internal places.
 *
 * @Flow\Proxy(false)
 */
final class RootNodeIdentifiers
{
    /**
     * @var DimensionSpacePoint
     */
    private static $rootDimensionSpacePoint;

    /**
     * @var NodeAggregateIdentifier
     */
    private static $rootNodeAggregateIdentifier;

    /**
     * the Root node is part of *every* dimension; thus the Root node is at home
     * in the empty dimension; and it will be visible in *all* dimensions.
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
     * @throws \Exception
     */
    public static function rootNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        if (!self::$rootNodeAggregateIdentifier) {
            self::$rootNodeAggregateIdentifier = NodeAggregateIdentifier::fromString('00000000-0000-0000-0000-000000000000');
        }
        return self::$rootNodeAggregateIdentifier;
    }
}
