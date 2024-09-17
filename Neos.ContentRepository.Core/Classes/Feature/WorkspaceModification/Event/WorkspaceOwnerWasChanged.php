<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\WorkspaceModification\Event;

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsWorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Event triggered to indicate that the owner of a workspace has changed.
 * Setting $newWorkspaceOwner to null, removes the current workspace owner.
 *
 * @api events are the persistence-API of the content repository
 */
final readonly class WorkspaceOwnerWasChanged implements EventInterface, EmbedsWorkspaceName
{
    public function __construct(
        public WorkspaceName $workspaceName,
        public ?string $newWorkspaceOwner,
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
            $values['newWorkspaceOwner'] ?? null,
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
