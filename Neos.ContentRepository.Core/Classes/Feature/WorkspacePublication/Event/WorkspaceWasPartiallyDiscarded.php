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

namespace Neos\ContentRepository\Core\Feature\WorkspacePublication\Event;

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsWorkspaceName;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdsToPublishOrDiscard;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @api events are the persistence-API of the content repository
 */
final readonly class WorkspaceWasPartiallyDiscarded implements EventInterface, EmbedsWorkspaceName
{
    public function __construct(
        public WorkspaceName $workspaceName,
        /**
         * The new content stream; containing the data which we want to keep
         */
        public ContentStreamId $newContentStreamId,
        /**
         * The old content stream, which contains ALL the data (discarded and non-discarded)
         */
        public ContentStreamId $previousContentStreamId,
        public NodeIdsToPublishOrDiscard $discardedNodes,
    ) {
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            ContentStreamId::fromString($values['newContentStreamId']),
            ContentStreamId::fromString($values['previousContentStreamId']),
            NodeIdsToPublishOrDiscard::fromArray($values['discardedNodes']),
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
