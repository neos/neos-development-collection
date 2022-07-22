<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Projection;

use Neos\ContentRepository\ValueObject\ContentRepositoryId;

/**
 * Interface for a {@see ProjectionCatchUpTriggerInterface} factory
 */
interface ProjectionCatchUpTriggerFactoryInterface
{
    /**
     * @param ContentRepositoryId $contentRepositoryId
     * @param array<mixed> $options
     * @return ProjectionCatchUpTriggerInterface
     */
    public function create(ContentRepositoryId $contentRepositoryId, array $options): ProjectionCatchUpTriggerInterface;
}
