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
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Change visibility of the root node aggregate. A root node aggregate must be visible in all
 * configured dimensions.
 *
 * Needed when configured dimensions change.
 *
 * @api commands are the write-API of the ContentRepository
 */
final readonly class UpdateRootNodeAggregateDimensions implements
    CommandInterface,
    \JsonSerializable,
    RebasableToOtherWorkspaceInterface
{
    /**
     * @param WorkspaceName $workspaceName The workspace which the dimensions should be updated in
     * @param NodeAggregateId $nodeAggregateId The id of the node aggregate that should be updated
     * @param WorkspaceName $initialWorkspaceName The original workspace this adjustment was applied to. This workspace will be allowed to contain leftover changes when publishing.
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public NodeAggregateId $nodeAggregateId,
        public WorkspaceName $initialWorkspaceName,
    ) {
    }

    /**
     * @param WorkspaceName $workspaceName The workspace which the dimensions should be updated in
     * @param NodeAggregateId $nodeAggregateId The id of the node aggregate that should be updated
     */
    public static function create(WorkspaceName $workspaceName, NodeAggregateId $nodeAggregateId): self
    {
        return new self($workspaceName, $nodeAggregateId, $workspaceName);
    }

    public static function fromArray(array $array): self
    {
        return new self(
            WorkspaceName::fromString($array['workspaceName']),
            NodeAggregateId::fromString($array['nodeAggregateId']),
            isset($array['initialWorkspaceName'])
                ? WorkspaceName::fromString($array['initialWorkspaceName'])
                // legacy fallback & for creation in tests
                : WorkspaceName::fromString($array['workspaceName']),
        );
    }

    /**
     * @return array<string,\JsonSerializable>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function createCopyForWorkspace(
        WorkspaceName $targetWorkspaceName,
    ): self {
        return new self(
            $targetWorkspaceName,
            $this->nodeAggregateId,
            $this->initialWorkspaceName
        );
    }
}
