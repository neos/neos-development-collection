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

namespace Neos\ContentRepository\Core\Feature\WorkspacePublication\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Discard a set of nodes in a workspace
 *
 * @api commands are the write-API of the ContentRepository
 */
final readonly class DiscardIndividualNodesFromWorkspace implements CommandInterface
{
    /**
     * @param WorkspaceName $workspaceName Name of the affected workspace
     * @param NodeAggregateIds $nodesToDiscard Ids of the nodes to be discarded
     * @param ContentStreamId $newContentStreamId The id of the new content stream, that will contain the remaining changes which were not discarded
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public NodeAggregateIds $nodesToDiscard,
        public ContentStreamId $newContentStreamId
    ) {
        if ($this->nodesToDiscard->isEmpty()) {
            throw new \InvalidArgumentException(sprintf('The command "DiscardIndividualNodesFromWorkspace" for workspace %s must contain nodes to publish', $this->workspaceName->value), 1737448741);
        }
    }

    /**
     * @param WorkspaceName $workspaceName Name of the affected workspace
     * @param NodeAggregateIds $nodesToDiscard Ids of the nodes to be discarded
     */
    public static function create(
        WorkspaceName $workspaceName,
        NodeAggregateIds $nodesToDiscard,
    ): self {
        return new self(
            $workspaceName,
            $nodesToDiscard,
            ContentStreamId::create()
        );
    }

    public static function fromArray(array $array): self
    {
        return new self(
            WorkspaceName::fromString($array['workspaceName']),
            NodeAggregateIds::fromArray($array['nodesToDiscard']),
            isset($array['newContentStreamId']) ? ContentStreamId::fromString($array['newContentStreamId']) : ContentStreamId::create(),
        );
    }

    /**
     * Call this method if you want to run this command fully deterministically, f.e. during test cases
     */
    public function withNewContentStreamId(ContentStreamId $newContentStreamId): self
    {
        return new self($this->workspaceName, $this->nodesToDiscard, $newContentStreamId);
    }
}
