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

namespace Neos\ContentRepository\Feature\WorkspaceDiscarding\Event;

use Neos\ContentRepository\Feature\Common\NodeIdentifiersToPublishOrDiscard;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\EventStore\EventInterface;

final class WorkspaceWasPartiallyDiscarded implements EventInterface
{
    public function __construct(
        public readonly WorkspaceName $workspaceName,
        /**
         * The new content stream; containing the data which we want to keep
         */
        public readonly ContentStreamIdentifier $newContentStreamIdentifier,
        /**
         * The old content stream, which contains ALL the data (discarded and non-discarded)
         */
        public readonly ContentStreamIdentifier $previousContentStreamIdentifier,
        public readonly NodeIdentifiersToPublishOrDiscard $discardedNodes,
        public readonly UserIdentifier $initiatingUserIdentifier
    ) {
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            ContentStreamIdentifier::fromString($values['newContentStreamIdentifier']),
            ContentStreamIdentifier::fromString($values['previousContentStreamIdentifier']),
            NodeIdentifiersToPublishOrDiscard::fromArray($values['discardedNodes']),
            UserIdentifier::fromString($values['initiatingUserIdentifier'])
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'workspaceName' => $this->workspaceName,
            'newContentStreamIdentifier' => $this->newContentStreamIdentifier,
            'previousContentStreamIdentifier' => $this->previousContentStreamIdentifier,
            'discardedNodes' => $this->discardedNodes,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier,
        ];
    }
}
