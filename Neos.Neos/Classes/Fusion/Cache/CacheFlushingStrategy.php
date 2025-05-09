<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion\Cache;

enum CacheFlushingStrategy
{
    /**
     * All content changes in the content repository are persisted immediately and thus an immediate flush is also required.
     */
    case IMMEDIATE;
    /**
     * Changes like from assets (changing a title) will only be persisted when flow is shutting down. Thus we delay the flush as well.
     */
    case ON_SHUTDOWN;
}
