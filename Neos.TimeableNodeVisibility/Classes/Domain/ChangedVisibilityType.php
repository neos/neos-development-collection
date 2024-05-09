<?php

declare(strict_types=1);

namespace Neos\TimeableNodeVisibility\Domain;

/**
 * @internal
 */
enum ChangedVisibilityType: string
{
    case NODE_WAS_ENABLED = 'NODE_WAS_ENABLED';
    case NODE_WAS_DISABLED = 'NODE_WAS_DISABLED';
}
