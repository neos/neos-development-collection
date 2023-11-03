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

namespace Neos\ContentRepository\Core\Feature\RootNodeCreation\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Feature\NodeCreation\Dto\NodeAggregateIdsByNodePaths;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Create root node aggregate with node command
 *
 * A root node has no variants and no origin dimension space point but occupies the whole allowed dimension subspace.
 * It also has no tethered child nodes.
 *
 * @api commands are the write-API of the ContentRepository
 */
final readonly class CreateRootNodeAggregateWithNode implements
    CommandInterface,
    \JsonSerializable
{
    /**
     * @param WorkspaceName $workspaceName The workspace in which the root node should be created in
     * @param NodeAggregateId $nodeAggregateId The id of the root node aggregate to create
     * @param NodeTypeName $nodeTypeName Name of type of the new node to create
     * @param NodeAggregateIdsByNodePaths $tetheredDescendantNodeAggregateIds Predefined aggregate ids of tethered child nodes per path. For any tethered node that has no matching entry in this set, the node aggregate id is generated randomly. Since tethered nodes may have tethered child nodes themselves, this works for multiple levels ({@see self::withTetheredDescendantNodeAggregateIds()})
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public NodeAggregateId $nodeAggregateId,
        public NodeTypeName $nodeTypeName,
        public NodeAggregateIdsByNodePaths $tetheredDescendantNodeAggregateIds,
    ) {
    }

    /**
     * @param WorkspaceName $workspaceName The workspace in which the root node should be created in
     * @param NodeAggregateId $nodeAggregateId The id of the root node aggregate to create
     * @param NodeTypeName $nodeTypeName Name of type of the new node to create
     */
    public static function create(WorkspaceName $workspaceName, NodeAggregateId $nodeAggregateId, NodeTypeName $nodeTypeName): self
    {
        return new self(
            $workspaceName,
            $nodeAggregateId,
            $nodeTypeName,
            NodeAggregateIdsByNodePaths::createEmpty()
        );
    }

    /**
     * Specify explicitly the node aggregate ids for the tethered children {@see tetheredDescendantNodeAggregateIds}.
     *
     * In case you want to create a batch of commands where one creates the root node and a succeeding command needs
     * a tethered node aggregate id, you need to generate the child node aggregate ids in advance.
     *
     * _Alternatively you would need to fetch the created tethered node first from the subgraph.
     * {@see ContentSubgraphInterface::findChildNodeConnectedThroughEdgeName()}_
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
            $this->workspaceName,
            $this->nodeAggregateId,
            $this->nodeTypeName,
            $tetheredDescendantNodeAggregateIds,
        );
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            WorkspaceName::fromString($array['workspaceName']),
            NodeAggregateId::fromString($array['nodeAggregateId']),
            NodeTypeName::fromString($array['nodeTypeName']),
            isset($array['tetheredDescendantNodeAggregateIds'])
                ? NodeAggregateIdsByNodePaths::fromArray($array['tetheredDescendantNodeAggregateIds'])
                : NodeAggregateIdsByNodePaths::createEmpty()
        );
    }

    /**
     * @return array<string,\JsonSerializable>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
