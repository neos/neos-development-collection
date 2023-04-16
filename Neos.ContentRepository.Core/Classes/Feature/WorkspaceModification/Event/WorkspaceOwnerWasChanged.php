<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\WorkspaceModification\Event;

use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\EventStore\EventInterface;

/**
 * Event triggered to indicate that the owner of a workspace has changed.
 * Setting $newWorkspaceOwner to null, removes the current workspace owner.
 *
 * @api events are the persistence-API of the content repository
 */
final class WorkspaceOwnerWasChanged implements EventInterface
{
    public function __construct(
        public readonly WorkspaceName $workspaceName,
        public readonly ?string $newWorkspaceOwner,
    ) {
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            $values['newWorkspaceOwner'],
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
