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

namespace Neos\ContentRepository\Core\Feature\NodeCreation\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Dto\NodeAggregateIdsByNodePaths;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * CreateNodeAggregateWithNode
 *
 * Creates a new node aggregate with a new node in the given `contentStreamId`
 * with the given `nodeAggregateId` and `originDimensionSpacePoint`.
 * The node will be appended as child node of the given `parentNodeId` which must cover the given
 * `originDimensionSpacePoint`.
 *
 * @api commands are the write-API of the ContentRepository
 */
final class CreateNodeAggregateWithNode implements CommandInterface
{
    /**
     * Node aggregate id of the node's succeeding sibling (optional)
     * If not given, the node will be added as the parent's first child
     */
    public readonly ?NodeAggregateId $succeedingSiblingNodeAggregateId;

    /**
     * The node's optional name. Set if there is a meaningful relation to its parent that should be named.
     */
    public readonly ?NodeName $nodeName;

    /**
     * The node's initial property values. Will be merged over the node type's default property values
     */
    public readonly PropertyValuesToWrite $initialPropertyValues;

    /**
     * NodeAggregateIds for tethered descendants (optional).
     *
     * If the given node type declares tethered child nodes, you may predefine their node aggregate ids
     * using this assignment registry.
     * Since tethered child nodes may have tethered child nodes themselves,
     * this registry is indexed using relative node paths to the node to create in the first place.
     */
    public readonly NodeAggregateIdsByNodePaths $tetheredDescendantNodeAggregateIds;

    // TODO: CREATE METHODS FÜR ALLE COMMANDS
    public function __construct(
        public readonly ContentStreamId $contentStreamIdd,
        public readonly NodeAggregateId $nodeAggregateId,
        public readonly NodeTypeName $nodeTypeName,
        /**
         * Origin of the new node in the dimension space.
         * Will also be used to calculate a set of dimension points where the new node will cover
         * from the configured specializations.
         */
        public readonly OriginDimensionSpacePoint $originDimensionSpacePoint,
        public readonly NodeAggregateId $parentNodeAggregateId,
        ?NodeAggregateId $succeedingSiblingNodeAggregateId = null,
        ?NodeName $nodeName = null,
        ?PropertyValuesToWrite $initialPropertyValues = null,
        ?NodeAggregateIdsByNodePaths $tetheredDescendantNodeAggregateIds = null
    ) {
        $this->succeedingSiblingNodeAggregateId = $succeedingSiblingNodeAggregateId;
        $this->nodeName = $nodeName;
        $this->initialPropertyValues = $initialPropertyValues ?: PropertyValuesToWrite::fromArray([]);
        $this->tetheredDescendantNodeAggregateIds = $tetheredDescendantNodeAggregateIds
            ?: new NodeAggregateIdsByNodePaths([]);
    }

    public function withInitialPropertyValues(PropertyValuesToWrite $newInitialPropertyValues): self
    {
        return new self(
            $this->contentStreamIdd,
            $this->nodeAggregateId,
            $this->nodeTypeName,
            $this->originDimensionSpacePoint,
            $this->parentNodeAggregateId,
            $this->succeedingSiblingNodeAggregateId,
            $this->nodeName,
            $newInitialPropertyValues,
            $this->tetheredDescendantNodeAggregateIds
        );
    }
}
