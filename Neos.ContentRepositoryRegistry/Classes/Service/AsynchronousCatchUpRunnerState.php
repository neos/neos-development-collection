<?php
namespace Neos\ContentRepositoryRegistry\Service;

use Neos\Cache\Frontend\FrontendInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;

/**
 *
 */
final readonly class AsynchronousCatchUpRunnerState
{
    private string $cacheKeyRunning;
    private string $cacheKeyQueued;

    private function __construct(
        public ContentRepositoryId $contentRepositoryId,
        public string $projectionClassName,
        private FrontendInterface $cache
    ) {
        $cacheKeyPrefix = $contentRepositoryId->value . '_' . md5($projectionClassName);
        $this->cacheKeyRunning = $cacheKeyPrefix . '_RUNNING';
        $this->cacheKeyQueued = $cacheKeyPrefix . '_QUEUED';
    }

    public static function create(ContentRepositoryId $contentRepositoryId, string $projectionClassName, FrontendInterface $cache): self
    {
        return new self($contentRepositoryId, $projectionClassName, $cache);
    }

    public function isRunning(): bool
    {
        return $this->cache->has($this->cacheKeyRunning);
    }

    public function run(): void
    {
        $this->cache->set($this->cacheKeyRunning, 1);
    }

    public function setStopped(): void
    {
        $this->cache->remove($this->cacheKeyRunning);
    }

    public function isQueued(): bool
    {
        return $this->cache->has($this->cacheKeyQueued);
    }

    public function queue(): void
    {
        $this->cache->set($this->cacheKeyQueued, 1);
    }

    public function dequeue(): void
    {
        $this->cache->remove($this->cacheKeyQueued);
    }
}
