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

namespace Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * A node id (Content Stream, NodeAggregateId, DimensionSpacePoint); used when
 * publishing or discarding individual nodes
 * ({@see \Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace} and
 * {@see \Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardIndividualNodesFromWorkspace}
 * and the corresponding events).
 *
 * @api used as part of commands
 */
final readonly class NodeIdToPublishOrDiscard implements \JsonSerializable
{
    public function __construct(
        public WorkspaceName $workspaceName,
        public NodeAggregateId $nodeAggregateId,
        public DimensionSpacePoint $dimensionSpacePoint,
    ) {
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            WorkspaceName::fromString($array['workspaceName']),
            NodeAggregateId::fromString($array['nodeAggregateId']),
            DimensionSpacePoint::fromArray($array['dimensionSpacePoint']),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
