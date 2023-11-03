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
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * change visibility of the root node aggregate. A root node aggregate must be visible in all
 * configured dimensions.
 * Needed when configured dimensions change.
 *
 * @api commands are the write-API of the ContentRepository
 */
final readonly class UpdateRootNodeAggregateDimensions implements
    CommandInterface,
    \JsonSerializable
{
    /**
     * @param WorkspaceName $workspaceName The workspace which the dimensions should be updated in
     * @param NodeAggregateId $nodeAggregateId The id of the node aggregate that should be updated
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public NodeAggregateId $nodeAggregateId,
    ) {
    }

    /**
     * @param WorkspaceName $workspaceName The workspace which the dimensions should be updated in
     * @param NodeAggregateId $nodeAggregateId The id of the node aggregate that should be updated
     */
    public static function create(WorkspaceName $workspaceName, NodeAggregateId $nodeAggregateId): self
    {
        return new self($workspaceName, $nodeAggregateId);
    }

    /**
     * @param array<string,string> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            WorkspaceName::fromString($array['workspaceName']),
            NodeAggregateId::fromString($array['nodeAggregateId'])
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
