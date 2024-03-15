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

namespace Neos\ContentRepository\Core\Feature\NodeVariation\Event;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\CoverageNodeMoveMapping;
use Neos\ContentRepository\Core\Feature\NodeVariation\Dto\CoverageNodeVariantMapping;
use Neos\ContentRepository\Core\Feature\NodeVariation\Dto\CoverageNodeVariantMappings;
use Neos\ContentRepository\Core\Feature\NodeVariation\Dto\EndSiblingVariantPosition;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * This event represents the **Translation** use case, i.e. variant creation across non-connected dimensions.
 *
 * This event means: "Copy all properties (and references) of the given node from the source to the target dimension."
 *
 * ## Positioning of Nodes across variants
 *
 * When translating content, e.g. from en to de, we effectively make a new node visible in all dimensions
 * specified by {@see $peerCoverage} (i.e. we create **new edges** in the Content Graph). Because the edges
 * contain positioning information ("I am 3rd child", ...), we need to specify exactly where the new edges
 * should go to; as there might be different nodes and different sortings existing in all the different
 * target dimensions.
 *
 * TODO: ALSO PARENT NODE AGG ID?? OR ONLY SUCC SIBLING?? ({@see SucceedingSiblingNodeMoveDestination})
 *
 *
 * ## Background: What is Node Variation?
 *
 * Node Variation is the process of creating a materialized copy of an existing node across dimensions.
 * As example, node1,language=en to node1,language=de. The materialized nodes then have properties on their own,
 * and do not shine through from their original variant.
 *
 * **Types of Node Variation**
 *
 * Let's take the following dimension graph (with fallbacks ch=>de, and at=>de):
 *
 * ```
 *     ┌─────┐    ┌────┐
 *     │ de  │    │ en │
 *     └──▲──┘    └────┘
 *        │
 *    ┌───┴───┐
 * ┌──┴─┐  ┌──┴─┐
 * │ ch │  │ at │
 * └────┘  └────┘
 * ```
 *
 * - **Translation**: Variant creation across non-connected dimensions is implemented by
 *   {@see NodePeerVariantWasCreated} - this is most common for translating content, e.g.
 *   from `de` to `en`, from `ch` to `at` or from `en` to `at` (and vice versa).
 *
 *   ^^^^^^^ WE ARE HERE (in this event)
 *
 * - **Materialization**: If content exists e.g. in `de` and by default shines through to both
 *   `ch` and `at`; if you want to disable this shine-through and **override** content e.g.
 *   in `ch`, this is implemented by {@see NodeSpecializationVariantWasCreated}.
 *
 * - **Generalization**: If content exists in a specialized variant (e.g. `at`) and you want to
 *   create the generalized variant (`de` in the example above), you need {@see NodeGeneralizationVariantWasCreated}.
 *
 *
 * @api events are the persistence-API of the content repository
 */
final class NodePeerVariantWasCreated implements
    EventInterface,
    PublishableToOtherContentStreamsInterface,
    EmbedsContentStreamAndNodeAggregateId
{
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $nodeAggregateId,
        /**
         * The source which should be translated (i.e. whose properties should be copied)
         */
        public readonly OriginDimensionSpacePoint $sourceOrigin,

        /**
         * The target dimension (where the content should be copied *to*)
         */
        public readonly OriginDimensionSpacePoint $peerOrigin,

        /**
         * Because {@see $peerOrigin} can have nested dimensions underneath (e.g. in the example above from `en` to `de`)
         * this is the multiplied-out list of dimensions where the translation will be *visible in*. Will at least
         * contain {@see $peerOrigin}, but could have additional dimensions as well.
         *
         * TODO: REMOVE THIS, and create positionMappings from it?
         */
        public readonly DimensionSpacePointSet $peerCoverage, // DEPRECATED

        public readonly CoverageNodeVariantMappings $positionMappings,
    ) {
    }

    public function getContentStreamId(): ContentStreamId
    {
        return $this->contentStreamId;
    }

    public function getNodeAggregateId(): NodeAggregateId
    {
        return $this->nodeAggregateId;
    }

    public function createCopyForContentStream(ContentStreamId $targetContentStreamId): self
    {
        return new self(
            $targetContentStreamId,
            $this->nodeAggregateId,
            $this->sourceOrigin,
            $this->peerOrigin,
            $this->peerCoverage,
            $this->positionMappings,
        );
    }

    public static function fromArray(array $values): self
    {
        if (!isset($values['positionMappings'])) {
            // TODO: if peerCoverage and no positionMappings, create peerCoverage with type "end"

            // DEPRECATED
            $coverageNodeVariantMappings = [];
            foreach (DimensionSpacePointSet::fromArray($values['peerCoverage'])->getIterator() as $dimensionSpacePoint) {
                $coverageNodeVariantMappings[] = CoverageNodeVariantMapping::createForNewEndSibling($dimensionSpacePoint, EndSiblingVariantPosition::create());
            }
            $coverageNodeVariantMappings = CoverageNodeVariantMappings::create(...$coverageNodeVariantMappings);
        } else {
            $coverageNodeVariantMappings = CoverageNodeVariantMappings::fromArray($values['positionMappings']);
        }

        return new self(
            ContentStreamId::fromString($values['contentStreamId']),
            NodeAggregateId::fromString($values['nodeAggregateId']),
            OriginDimensionSpacePoint::fromArray($values['sourceOrigin']),
            OriginDimensionSpacePoint::fromArray($values['peerOrigin']),
            DimensionSpacePointSet::fromArray($values['peerCoverage']), // DEPRECATED
            $coverageNodeVariantMappings,
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
