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

use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Feature\Common\NodeIdentifierToPublishOrDiscard;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\Feature\Common\MatchableWithNodeIdentifierToPublishOrDiscardInterface;
use Neos\ContentRepository\Feature\NodeDisabling\Command\NodeVariantSelectionStrategy;
use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

final class RemoveNodeAggregate implements
    CommandInterface,
    \JsonSerializable,
    RebasableToOtherContentStreamsInterface,
    MatchableWithNodeIdentifierToPublishOrDiscardInterface
{
    public function __construct(
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        public readonly NodeAggregateIdentifier $nodeAggregateIdentifier,
        /** One of the dimension space points covered by the node aggregate in which the user intends to remove it */
        public readonly DimensionSpacePoint $coveredDimensionSpacePoint,
        public readonly NodeVariantSelectionStrategy $nodeVariantSelectionStrategy,
        public readonly UserIdentifier $initiatingUserIdentifier,
        /**
         * This is usually the NodeAggregateIdentifier of the parent node of the deleted node. It is needed for instance
         * in the Neos UI for the following scenario:
         * - when removing a node, you still need to be able to publish the removal.
         * - For this to work, the Neos UI needs to know the identifier of the removed Node, **on the page
         *   where the removal happened** (so that the user can decide to publish a single page INCLUDING the removal
         *   on the page)
         * - Because this command will *remove* the edge,
         *   we cannot know the position in the tree after doing the removal anymore.
         *
         * That's why we need this field: For the Neos UI, it stores the document node of the removed node
         * (see Remove.php), as that is what the UI needs lateron for the change display.
         */
        public readonly ?NodeAggregateIdentifier $removalAttachmentPoint = null
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
            UserIdentifier::fromString($array['initiatingUserIdentifier']),
            isset($array['removalAttachmentPoint'])
                ? NodeAggregateIdentifier::fromString($array['removalAttachmentPoint'])
                : null
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
            'coveredDimensionSpacePoint' => $this->coveredDimensionSpacePoint,
            'nodeVariantSelectionStrategy' => $this->nodeVariantSelectionStrategy,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier,
            'removalAttachmentPoint' => $this->removalAttachmentPoint
        ];
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): self
    {
        return new self(
            $targetContentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->coveredDimensionSpacePoint,
            $this->nodeVariantSelectionStrategy,
            $this->initiatingUserIdentifier,
            $this->removalAttachmentPoint
        );
    }

    public function matchesNodeIdentifier(NodeIdentifierToPublishOrDiscard $nodeIdentifierToPublish): bool
    {
        return (
            $this->contentStreamIdentifier === $nodeIdentifierToPublish->contentStreamIdentifier
                && $this->nodeAggregateIdentifier->equals($nodeIdentifierToPublish->nodeAggregateIdentifier)
                && $this->coveredDimensionSpacePoint === $nodeIdentifierToPublish->dimensionSpacePoint
        );
    }
}
