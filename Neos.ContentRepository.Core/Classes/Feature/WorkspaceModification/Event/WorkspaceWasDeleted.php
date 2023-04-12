<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\WorkspaceModification\Event;

use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\EventStore\EventInterface;

/**
 * Event triggered to indicate that a workspace got deleted.
 *
 * @api events are the persistence-API of the content repository
 */
final class WorkspaceWasDeleted implements EventInterface
{
    public function __construct(
        public readonly WorkspaceName $workspaceName,
    ) {
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
