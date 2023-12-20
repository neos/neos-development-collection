<?php

namespace Neos\ContentRepository\Core\Projection;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;

/**
 *
 */
final class ProjectionCatchUpLockIdentifier
{
    private function __construct(public string $value)
    {
    }

    private static function generateIdentifier(ContentRepositoryId $contentRepositoryId, string $projectionClassName): string
    {
        return md5(sprintf('%s_%s', $contentRepositoryId->value, $projectionClassName));
    }

    public static function createRunning(ContentRepositoryId $contentRepositoryId, string $projectionClassName): self
    {
        return new self(self::generateIdentifier($contentRepositoryId, $projectionClassName) . 'RUN');
    }

    public static function createQueued(ContentRepositoryId $contentRepositoryId, string $projectionClassName): self
    {
        return new self(self::generateIdentifier($contentRepositoryId, $projectionClassName) . 'QUEUE');
    }
}
