<?php

namespace Neos\ContentRepositoryRegistry\Utility;

use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;

/**
 * Utility trait for retrieving node types for nodes with a built-in fallback mechanism
 */
trait ContentRepositoryRegistryProvider
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;
}
