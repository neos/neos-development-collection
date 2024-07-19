<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion\Cache;

/**
 * A special enum to explicitly represent any workspace {@see CacheTag}
 */
enum CacheTagWorkspaceName
{
    case ANY;
}
