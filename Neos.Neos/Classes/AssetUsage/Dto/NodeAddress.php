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

namespace Neos\Neos\AssetUsage\Dto;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Annotations as Flow;

/**
 * Is used to address a node within the AssetUsage package
 *
 * @internal
 */
#[Flow\Proxy(false)]
final class NodeAddress
{
    /**
     * @internal use NodeAddressFactory, if you want to create a NodeAddress
     */
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly DimensionSpacePoint $dimensionSpacePoint,
        public readonly NodeAggregateId $nodeAggregateId,
    ) {
    }

    public function __toString(): string
    {
        return sprintf(
            'NodeAddress[contentStream=%s, dimensionSpacePoint=%s, nodeAggregateIdentifier=%s]',
            $this->contentStreamId,
            $this->dimensionSpacePoint,
            $this->nodeAggregateId
        );
    }
}
