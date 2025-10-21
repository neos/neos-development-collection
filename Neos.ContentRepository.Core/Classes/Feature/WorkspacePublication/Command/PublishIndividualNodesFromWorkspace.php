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
 * Publish a set of nodes in a workspace
 *
 * @api commands are the write-API of the ContentRepository
 */
final readonly class PublishIndividualNodesFromWorkspace implements CommandInterface
{
    /**
     * @param WorkspaceName $workspaceName Name of the affected workspace
     * @param NodeAggregateIds $nodesToPublish Ids of the nodes to publish or discard
     * @param ContentStreamId $contentStreamIdForRemainingPart The id of the new content stream that will contain all remaining events {@see self::withContentStreamIdForRemainingPart()}
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public NodeAggregateIds $nodesToPublish,
        public ContentStreamId $contentStreamIdForRemainingPart
    ) {
        if ($this->nodesToPublish->isEmpty()) {
            throw new \InvalidArgumentException(sprintf('The command "PublishIndividualNodesFromWorkspace" for workspace %s must contain nodes to publish', $this->workspaceName->value), 1737448717);
        }
    }

    /**
     * @param WorkspaceName $workspaceName Name of the affected workspace
     * @param NodeAggregateIds $nodesToPublish Ids of the nodes to publish or discard
     */
    public static function create(WorkspaceName $workspaceName, NodeAggregateIds $nodesToPublish): self
    {
        return new self(
            $workspaceName,
            $nodesToPublish,
            ContentStreamId::create()
        );
    }

    public static function fromArray(array $array): self
    {
        return new self(
            WorkspaceName::fromString($array['workspaceName']),
            NodeAggregateIds::fromArray($array['nodesToPublish']),
            isset($array['contentStreamIdForRemainingPart']) ? ContentStreamId::fromString($array['contentStreamIdForRemainingPart']) : ContentStreamId::create(),
        );
    }

    /**
     * The id of the new content stream that will contain all remaining events
     *
     * This method adds its ID, so that the command
     * can run fully deterministic - we need this for the test cases.
     */
    public function withContentStreamIdForRemainingPart(ContentStreamId $contentStreamIdForRemainingPart): self
    {
        return new self($this->workspaceName, $this->nodesToPublish, $contentStreamIdForRemainingPart);
    }
}
