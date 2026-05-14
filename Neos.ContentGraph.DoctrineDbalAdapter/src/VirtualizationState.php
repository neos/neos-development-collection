<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayer;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

final readonly class VirtualizationState
{
    public function __construct(
        public WorkspaceName $workspaceName,
        public ContentStreamLayer $temporaryContentStreamLayer
    ) {
    }
}
