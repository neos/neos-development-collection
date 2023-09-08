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
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdsToPublishOrDiscard;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Publish a set of nodes in a workspace
 *
 * @api commands are the write-API of the ContentRepository
 */
final class PublishIndividualNodesFromWorkspace implements CommandInterface
{
    /**
     * @param WorkspaceName $workspaceName Name of the affected workspace
     * @param NodeIdsToPublishOrDiscard $nodesToPublish Ids of the nodes to publish or discard
     * @param ContentStreamId $contentStreamIdForMatchingPart The id of the new content stream that will contain all events to be published
     * @param ContentStreamId $contentStreamIdForRemainingPart The id of the new content stream that will contain all remaining events
     */
    private function __construct(
        public readonly WorkspaceName $workspaceName,
        public readonly NodeIdsToPublishOrDiscard $nodesToPublish,
        /**
         * during the publish process, we sort the events so that the events we want to publish
         * come first. In this process, two new content streams are generated:
         * - the first one contains all events which we want to publish
         * - the second one is based on the first one, and contains all the remaining events (which we want to keep
         *   in the user workspace).
         *
         * This property contains the ID of the first content stream, so that this command
         * can run fully deterministic - we need this for the test cases.
         */
        public readonly ContentStreamId $contentStreamIdForMatchingPart,
        /**
         * See the description of {@see $contentStreamIdForMatchingPart}.
         *
         * This property contains the ID of the second content stream, so that this command
         * can run fully deterministic - we need this for the test cases.
         */
        public readonly ContentStreamId $contentStreamIdForRemainingPart
    ) {
    }

    /**
     * @param WorkspaceName $workspaceName Name of the affected workspace
     * @param NodeIdsToPublishOrDiscard $nodesToPublish Ids of the nodes to publish or discard
     */
    public static function create(WorkspaceName $workspaceName, NodeIdsToPublishOrDiscard $nodesToPublish): self {
        return new self(
            $workspaceName,
            $nodesToPublish,
            ContentStreamId::create(),
            ContentStreamId::create()
        );
    }

    public function withContentStreamIdForMatchingPart(ContentStreamId $contentStreamIdForMatchingPart): self
    {
        return new self($this->workspaceName, $this->nodesToPublish, $contentStreamIdForMatchingPart, $this->contentStreamIdForRemainingPart);
    }

    public function withContentStreamIdForRemainingPart(ContentStreamId $contentStreamIdForRemainingPart): self
    {
        return new self($this->workspaceName, $this->nodesToPublish, $this->contentStreamIdForMatchingPart, $contentStreamIdForRemainingPart);
    }
}
