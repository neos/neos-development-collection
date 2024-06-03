<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\WorkspacePublication\Event;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @api events are the persistence-API of the content repository
 */
final readonly class WorkspaceWasPublished implements EventInterface
{
    public function __construct(
        /**
         * From which workspace have changes been published?
         */
        public WorkspaceName $sourceWorkspaceName,
        /**
         * The target workspace where the changes have been published to.
         */
        public WorkspaceName $targetWorkspaceName,
        /**
         * The new, empty content stream ID of $sourceWorkspaceName, (after the publish was successful)
         */
        public ContentStreamId $newSourceContentStreamId,
        /**
         * The old content stream ID of $sourceWorkspaceName (which is not active anymore now)
         */
        public ContentStreamId $previousSourceContentStreamId
    ) {
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['sourceWorkspaceName']),
            WorkspaceName::fromString($values['targetWorkspaceName']),
            ContentStreamId::fromString($values['newSourceContentStreamId']),
            ContentStreamId::fromString($values['previousSourceContentStreamId']),
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
