<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Projection;

use Neos\ContentRepository\ValueObject\ContentRepositoryId;

/**
 * Common interface for all projections
 */
interface ProjectionFactoryInterface
{
    /**
     * @param ContentRepositoryId $contentRepositoryId
     * @param array<mixed> $options
     * @return ProjectionInterface<ProjectionStateInterface>
     */
    public function create(ContentRepositoryId $contentRepositoryId, array $options): ProjectionInterface;
}
