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

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\EventStore\EventInterface;

final class WorkspaceWasPartiallyPublished implements EventInterface
{
    /**
     * TODO build
     * @var array<int,NodeAddress>
     */
    private array $publishedNodeAddresses;

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
        public readonly UserIdentifier $initiatingUserIdentifier
    ) {
        $this->publishedNodeAddresses = [];
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['sourceWorkspaceName']),
            WorkspaceName::fromString($values['targetWorkspaceName']),
            ContentStreamIdentifier::fromString($values['newSourceContentStreamIdentifier']),
            ContentStreamIdentifier::fromString($values['previousSourceContentStreamIdentifier']),
            UserIdentifier::fromString($values['initiatingUserIdentifier'])
        );
    }

    public function jsonSerialize(): mixed
    {
        return [
            'sourceWorkspaceName' => $this->sourceWorkspaceName,
            'targetWorkspaceName' => $this->targetWorkspaceName,
            'newSourceContentStreamIdentifier' => $this->newSourceContentStreamIdentifier,
            'previousSourceContentStreamIdentifier' => $this->previousSourceContentStreamIdentifier,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier,
        ];
    }
}
