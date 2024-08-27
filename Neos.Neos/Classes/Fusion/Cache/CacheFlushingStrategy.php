<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion\Cache;

enum CacheFlushingStrategy
{
    case IMMEDIATELY;
    case ON_SHUTDOWN;
}
