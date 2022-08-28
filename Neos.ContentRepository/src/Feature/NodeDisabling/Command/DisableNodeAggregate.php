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

namespace Neos\ContentRepository\Feature\NodeDisabling\Command;

use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Feature\Common\NodeIdentifierToPublishOrDiscard;
use Neos\ContentRepository\Feature\Common\NodeVariantSelectionStrategy;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\Feature\Common\MatchableWithNodeIdentifierToPublishOrDiscardInterface;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

/**
 * Disable the given node aggregate in the given content stream in a dimension space point using a given strategy
 *
 * @api commands are the write-API of the ContentRepository
 */
final class DisableNodeAggregate implements
    CommandInterface,
    \JsonSerializable,
    RebasableToOtherContentStreamsInterface,
    MatchableWithNodeIdentifierToPublishOrDiscardInterface
{
    public function __construct(
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        public readonly NodeAggregateIdentifier $nodeAggregateIdentifier,
        /** One of the dimension space points covered by the node aggregate in which the user intends to disable it */
        public readonly DimensionSpacePoint $coveredDimensionSpacePoint,
        /** The strategy the user chose to determine which specialization variants will also be disabled */
        public readonly NodeVariantSelectionStrategy $nodeVariantSelectionStrategy,
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
            DimensionSpacePoint::fromArray($array['coveredDimensionSpacePoint']),
            NodeVariantSelectionStrategy::from($array['nodeVariantSelectionStrategy']),
            UserIdentifier::fromString($array['initiatingUserIdentifier'])
        );
    }

    /**
     * @return array<string,\JsonSerializable>
     */
    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'coveredDimensionSpacePoint' => $this->coveredDimensionSpacePoint,
            'nodeVariantSelectionStrategy' => $this->nodeVariantSelectionStrategy,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier
        ];
    }

    public function createCopyForContentStream(ContentStreamIdentifier $target): self
    {
        return new self(
            $target,
            $this->nodeAggregateIdentifier,
            $this->coveredDimensionSpacePoint,
            $this->nodeVariantSelectionStrategy,
            $this->initiatingUserIdentifier
        );
    }

    public function matchesNodeIdentifier(NodeIdentifierToPublishOrDiscard $nodeIdentifierToPublish): bool
    {
        return (
            $this->contentStreamIdentifier === $nodeIdentifierToPublish->contentStreamIdentifier
                && $this->coveredDimensionSpacePoint === $nodeIdentifierToPublish->dimensionSpacePoint
                && $this->nodeAggregateIdentifier->equals($nodeIdentifierToPublish->nodeAggregateIdentifier)
        );
    }
}
