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

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\EventStore\EventInterface;

/**
 * @api events are the persistence-API of the content repository
 */
final class WorkspaceWasDiscarded implements EventInterface
{
    public function __construct(
        public readonly WorkspaceName $workspaceName,
        /**
         * The new, empty, content stream
         */
        public readonly ContentStreamId $newContentStreamId,
        /**
         * The old content stream (which contains the discarded data)
         */
        public readonly ContentStreamId $previousContentStreamId,
    ) {
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            ContentStreamId::fromString($values['newContentStreamId']),
            ContentStreamId::fromString($values['previousContentStreamId']),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'workspaceName' => $this->workspaceName,
            'newContentStreamId' => $this->newContentStreamId,
            'previousContentStreamId' => $this->previousContentStreamId,
        ];
    }
}
