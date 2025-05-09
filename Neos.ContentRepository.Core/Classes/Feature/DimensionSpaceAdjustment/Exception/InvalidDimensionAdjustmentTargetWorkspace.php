<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Exception;

use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @api
 */
class InvalidDimensionAdjustmentTargetWorkspace extends \DomainException
{
    public static function becauseWorkspaceMustBeRootOrRootBased(WorkspaceName $workspaceName): self
    {
        return new self(
            sprintf('Workspace %s must be root workspace or target a root workspace.', $workspaceName->value),
            1742042968
        );
    }
}
