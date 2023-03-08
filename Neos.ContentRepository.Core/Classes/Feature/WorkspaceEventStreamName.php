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

namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\EventStore\Model\Event\StreamName;

/**
 * A workspaces event stream name
 *
 * @internal
 */
final class WorkspaceEventStreamName
{
    private const EVENT_STREAM_NAME_PREFIX = 'Workspace:';

    private function __construct(
        private readonly string $eventStreamName,
    ) {
    }

    public static function fromWorkspaceName(WorkspaceName $workspaceName): self
    {
        return new self(self::EVENT_STREAM_NAME_PREFIX . $workspaceName->name);
    }

    public function getEventStreamName(): StreamName
    {
        return StreamName::fromString($this->eventStreamName);
    }

    public function __toString(): string
    {
        return $this->eventStreamName;
    }
}
