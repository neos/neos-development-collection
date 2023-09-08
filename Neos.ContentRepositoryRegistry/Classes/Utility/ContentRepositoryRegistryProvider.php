<?php

namespace Neos\ContentRepositoryRegistry\Utility;

use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;

/**
 * Utility trait for providing the content repository registry
 */
trait ContentRepositoryRegistryProvider
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;
}
