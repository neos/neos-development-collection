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

namespace Neos\ContentRepository\Feature\NodeRemoval\Command;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\Feature\Common\MatchableWithNodeAddressInterface;
use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final class RestoreNodeAggregateCoverage implements
    \JsonSerializable,
    RebasableToOtherContentStreamsInterface,
    MatchableWithNodeAddressInterface
{
    public function __construct(
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        public readonly NodeAggregateIdentifier $nodeAggregateIdentifier,
        /** The origin of the node that should be used for coverage */
        public readonly OriginDimensionSpacePoint $originDimensionSpacePoint,
        /** The dimension space point the node aggregate should cover again. Must be a direct specialization. */
        public readonly DimensionSpacePoint $dimensionSpacePointToCover,
        /** If set to true, also all specializations of the selected dimension space point will be restored */
        public readonly bool $withSpecializations,
        /** If set to true, this will be applied to all descendant nodes as well */
        public readonly bool $recursive,
        public readonly UserIdentifier $initiatingUserIdentifier
    ) {
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            OriginDimensionSpacePoint::fromArray($array['originDimensionSpacePoint']),
            DimensionSpacePoint::fromArray($array['dimensionSpacePointToCover']),
            $array['withSpecializations'],
            $array['recursive'],
            UserIdentifier::fromString($array['initiatingUserIdentifier']),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'coveredDimensionSpacePoint' => $this->dimensionSpacePointToCover,
            'withSpecializations' => $this->withSpecializations,
            'recursive' => $this->recursive,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier
        ];
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): self
    {
        return new self(
            $targetContentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->originDimensionSpacePoint,
            $this->dimensionSpacePointToCover,
            $this->withSpecializations,
            $this->recursive,
            $this->initiatingUserIdentifier
        );
    }

    public function matchesNodeAddress(NodeAddress $nodeAddress): bool
    {
        return (
            $this->contentStreamIdentifier === $nodeAddress->contentStreamIdentifier
                && $this->nodeAggregateIdentifier->equals($nodeAddress->nodeAggregateIdentifier)
                && $this->dimensionSpacePointToCover === $nodeAddress->dimensionSpacePoint
        );
    }
}
