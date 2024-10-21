<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\WorkspaceModification\Event;

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsWorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Event triggered to indicate that a workspace title or description has changed.
 *
 * @deprecated This event will never be emitted, and it is ignored in the core projections. This implementation is just kept for backwards-compatibility
 * @internal
 */
final readonly class WorkspaceWasRenamed implements EventInterface, EmbedsWorkspaceName
{
    public function __construct(
        public WorkspaceName $workspaceName,
        public string $workspaceTitle,
        public string $workspaceDescription,
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
            $values['workspaceTitle'],
            $values['workspaceDescription'],
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
