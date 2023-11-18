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
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
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
     * @param ContentStreamId $contentStreamId The content stream in which the create operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The unique identifier of the node aggregate to create
     * @param NodeTypeName $nodeTypeName Name of the node type of the new node
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint Origin of the new node in the dimension space. Will also be used to calculate a set of dimension points where the new node will cover from the configured specializations.
     * @param NodeAggregateId $parentNodeAggregateId The id of the node aggregate underneath which the new node is added
     * @param PropertyValuesToWrite $initialPropertyValues The node's initial property values. Will be merged over the node type's default property values
     * @param NodeAggregateId|null $succeedingSiblingNodeAggregateId Node aggregate id of the node's succeeding sibling (optional). If not given, the node will be added as the parent's first child
     * @param NodeName|null $nodeName The node's optional name. Set if there is a meaningful relation to its parent that should be named.
     * @param NodeAggregateIdsByNodePaths $tetheredDescendantNodeAggregateIds Predefined aggregate ids of tethered child nodes per path. For any tethered node that has no matching entry in this set, the node aggregate id is generated randomly. Since tethered nodes may have tethered child nodes themselves, this works for multiple levels ({@see self::withTetheredDescendantNodeAggregateIds()})
     */
    private function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $nodeAggregateId,
        public readonly NodeTypeName $nodeTypeName,
        public readonly OriginDimensionSpacePoint $originDimensionSpacePoint,
        public readonly NodeAggregateId $parentNodeAggregateId,
        public readonly PropertyValuesToWrite $initialPropertyValues,
        public readonly ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        public readonly ?NodeName $nodeName,
        public readonly NodeAggregateIdsByNodePaths $tetheredDescendantNodeAggregateIds,
    ) {
    }

    /**
     * @param ContentStreamId $contentStreamId The content stream in which the create operation is to be performed
     * @param NodeAggregateId $nodeAggregateId The unique identifier of the node aggregate to create
     * @param NodeTypeName $nodeTypeName Name of the node type of the new node
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint Origin of the new node in the dimension space. Will also be used to calculate a set of dimension points where the new node will cover from the configured specializations.
     * @param NodeAggregateId $parentNodeAggregateId The id of the node aggregate underneath which the new node is added
     * @param NodeAggregateId|null $succeedingSiblingNodeAggregateId Node aggregate id of the node's succeeding sibling (optional). If not given, the node will be added as the parent's first child
     * @param NodeName|null $nodeName The node's optional name. Set if there is a meaningful relation to its parent that should be named.
     * @param PropertyValuesToWrite|null $initialPropertyValues The node's initial property values. Will be merged over the node type's default property values
     */
    public static function create(ContentStreamId $contentStreamId, NodeAggregateId $nodeAggregateId, NodeTypeName $nodeTypeName, OriginDimensionSpacePoint $originDimensionSpacePoint, NodeAggregateId $parentNodeAggregateId, ?NodeAggregateId $succeedingSiblingNodeAggregateId = null, ?NodeName $nodeName = null, ?PropertyValuesToWrite $initialPropertyValues = null): self
    {
        return new self($contentStreamId, $nodeAggregateId, $nodeTypeName, $originDimensionSpacePoint, $parentNodeAggregateId, $initialPropertyValues ?: PropertyValuesToWrite::createEmpty(), $succeedingSiblingNodeAggregateId, $nodeName, NodeAggregateIdsByNodePaths::createEmpty());
    }

    public function withInitialPropertyValues(PropertyValuesToWrite $newInitialPropertyValues): self
    {
        return new self(
            $this->contentStreamId,
            $this->nodeAggregateId,
            $this->nodeTypeName,
            $this->originDimensionSpacePoint,
            $this->parentNodeAggregateId,
            $newInitialPropertyValues,
            $this->succeedingSiblingNodeAggregateId,
            $this->nodeName,
            $this->tetheredDescendantNodeAggregateIds,
        );
    }

    /**
     * Specify explicitly the node aggregate ids for the tethered children {@see tetheredDescendantNodeAggregateIds}.
     *
     * In case you want to create a batch of commands where one creates the node and a succeeding command needs
     * a tethered node aggregate id, you need to generate the child node aggregate ids in advance.
     *
     * _Alternatively you would need to fetch the created tethered node first from the subgraph.
     * {@see ContentSubgraphInterface::findNodeByPath()}_
     *
     * The helper method {@see NodeAggregateIdsByNodePaths::createForNodeType()} will generate recursively
     * node aggregate ids for every tethered child node:
     *
     * ```php
     * $tetheredDescendantNodeAggregateIds = NodeAggregateIdsByNodePaths::createForNodeType(
     *     $command->nodeTypeName,
     *     $nodeTypeManager
     * );
     * $command = $command->withTetheredDescendantNodeAggregateIds($tetheredDescendantNodeAggregateIds):
     * ```
     *
     * The generated node aggregate id for the tethered node "main" is this way known before the command is issued:
     *
     * ```php
     * $mainNodeAggregateId = $command->tetheredDescendantNodeAggregateIds->getNodeAggregateId(NodePath::fromString('main'));
     * ```
     *
     * Generating the node aggregate ids from user land is totally optional.
     */
    public function withTetheredDescendantNodeAggregateIds(NodeAggregateIdsByNodePaths $tetheredDescendantNodeAggregateIds): self
    {
        return new self(
            $this->contentStreamId,
            $this->nodeAggregateId,
            $this->nodeTypeName,
            $this->originDimensionSpacePoint,
            $this->parentNodeAggregateId,
            $this->initialPropertyValues,
            $this->succeedingSiblingNodeAggregateId,
            $this->nodeName,
            $tetheredDescendantNodeAggregateIds,
        );
    }
}
