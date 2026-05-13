<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayer;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

class HackyProjectionStates
{
    public static WorkspaceName|null $currentWorkspaceForPublication = null;

    public static ContentStreamLayer|null $temporaryLayer = null;

    public static function isInSimulation(): bool
    {
        return self::$currentWorkspaceForPublication !== null;
    }

    public static function reset(): void
    {
        self::$currentWorkspaceForPublication = null;
        self::$temporaryLayer = null;
    }
}
