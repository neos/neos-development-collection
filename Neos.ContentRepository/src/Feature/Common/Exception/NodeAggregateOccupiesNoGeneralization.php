<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Common\Exception;

use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\Flow\Annotations as Flow;

/**
 * The exception to be thrown if a node aggregate does not occupy any generalization
 * of the given origin dimension space point
 */
#[Flow\Proxy(false)]
final class NodeAggregateOccupiesNoGeneralization extends \DomainException
{
    public static function butWasSupposedTo(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $attemptedOriginDimensionSpacePoint
    ): self {
        return new self(
            'Node aggregate "' . $nodeAggregateIdentifier
                . '" does currently occupy no generalization of ' . json_encode($attemptedOriginDimensionSpacePoint),
            1659821215
        );
    }
}
