<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Exception;

use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * The exception to be thrown if any workspace contains changes but was not supposed to
 *
 * @api
 */
final class WorkspaceContainsPublishableChanges extends \RuntimeException
{
    public static function butWasNotSupposedToForBaseWorkspaceChange(WorkspaceName $workspaceName): self
    {
        throw new self(sprintf('The workspace %s needs to be empty before switching the base workspace.', $workspaceName->value), 1681455989);
    }

    public static function butWasNotSupposedTo(WorkspaceName ...$workspaceNames): self
    {
        return new self(
            'The following workspaces still contain changes: ' . implode(
                ',',
                array_map(
                    fn (WorkspaceName $workspaceName): string => $workspaceName->value,
                    $workspaceNames
                )
            ),
            1741889917
        );
    }
}
