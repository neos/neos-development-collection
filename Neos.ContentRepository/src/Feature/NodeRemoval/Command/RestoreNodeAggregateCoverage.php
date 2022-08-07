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
use Neos\ContentRepository\Feature\Common\RecursionMode;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\Feature\Common\MatchableWithNodeAddressInterface;
use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

/**
 * The command to restore coverage of a node aggregate.
 * If a specialization variant of a node is deleted, the node and its descendants are no longer available
 * in the variant's dimension space point.
 * With this command, the fallback mechanism can be restored for that node and its descendants,
 * i.e. the node will be available in the specialization dimension space point with fallback content.
 */
final class RestoreNodeAggregateCoverage implements
    \JsonSerializable,
    RebasableToOtherContentStreamsInterface,
    MatchableWithNodeAddressInterface
{
    public function __construct(
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        public readonly NodeAggregateIdentifier $nodeAggregateIdentifier,
        /** The dimension space point the node aggregate should cover again. */
        public readonly DimensionSpacePoint $dimensionSpacePointToCover,
        /** If set to true, also all specializations of the selected dimension space point will be restored */
        public readonly bool $withSpecializations,
        /** The mode to determine which descendants to affect as well. {@see RecursionMode} */
        public readonly RecursionMode $recursionMode,
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
            DimensionSpacePoint::fromArray($array['dimensionSpacePointToCover']),
            $array['withSpecializations'],
            RecursionMode::from($array['recursionMode']),
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
            'recursionMode' => $this->recursionMode,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier
        ];
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): self
    {
        return new self(
            $targetContentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->dimensionSpacePointToCover,
            $this->withSpecializations,
            $this->recursionMode,
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
