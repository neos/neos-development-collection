<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature;

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
        public readonly string $eventStreamName,
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
}
