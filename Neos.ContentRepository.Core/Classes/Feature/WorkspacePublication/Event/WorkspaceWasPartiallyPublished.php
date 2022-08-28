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

namespace Neos\ContentRepository\Feature\WorkspacePublication\Event;

use Neos\ContentRepository\Feature\Common\NodeIdentifiersToPublishOrDiscard;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\EventStore\EventInterface;

/**
 * @api events are the persistence-API of the content repository
 */
final class WorkspaceWasPartiallyPublished implements EventInterface
{
    public function __construct(
        /**
         * From which workspace have changes been partially published?
         */
        public readonly WorkspaceName $sourceWorkspaceName,
        /**
         * The target workspace where the changes have been published to.
         */
        public readonly WorkspaceName $targetWorkspaceName,
        /**
         * The new content stream for the $sourceWorkspaceName
         */
        public readonly ContentStreamIdentifier $newSourceContentStreamIdentifier,
        /**
         * The old content stream, which contains ALL the data (discarded and non-discarded)
         */
        public readonly ContentStreamIdentifier $previousSourceContentStreamIdentifier,
        public readonly NodeIdentifiersToPublishOrDiscard $publishedNodes,
        public readonly UserIdentifier $initiatingUserIdentifier
    ) {
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['sourceWorkspaceName']),
            WorkspaceName::fromString($values['targetWorkspaceName']),
            ContentStreamIdentifier::fromString($values['newSourceContentStreamIdentifier']),
            ContentStreamIdentifier::fromString($values['previousSourceContentStreamIdentifier']),
            NodeIdentifiersToPublishOrDiscard::fromArray($values['publishedNodes']),
            UserIdentifier::fromString($values['initiatingUserIdentifier'])
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'sourceWorkspaceName' => $this->sourceWorkspaceName,
            'targetWorkspaceName' => $this->targetWorkspaceName,
            'newSourceContentStreamIdentifier' => $this->newSourceContentStreamIdentifier,
            'previousSourceContentStreamIdentifier' => $this->previousSourceContentStreamIdentifier,
            'publishedNodes' => $this->publishedNodes,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier,
        ];
    }
}
