<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\ReferenceTypeGraph;

use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeGraph;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

interface ReferenceTypeGraphFactoryInterface
{
    /** @param array<string, mixed> $options */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): ReferenceTypeGraph;
}
