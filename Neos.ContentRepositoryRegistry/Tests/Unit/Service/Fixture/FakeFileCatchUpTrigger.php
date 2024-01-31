<?php
namespace Neos\ContentRepositoryRegistry\Tests\Unit\Service\Fixture;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Projection\ProjectionCatchUpLockIdentifier;
use Neos\ContentRepository\Core\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\Projections;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\PersistingStoreInterface;
use Symfony\Component\Lock\Store\PdoStore;

/**
 *
 */
class FakeFileCatchUpTrigger implements ProjectionCatchUpTriggerInterface
{
    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
    ) {}

    public function triggerCatchUp(Projections $projections): void
    {
        foreach ($projections as $projection) {
            $this->catchUp($projection);
        }
    }

    private function catchUp(ProjectionInterface $projection)
    {
        $lockFactory = new LockFactory(self::lockStore());
        $lock = $lockFactory->createLock(ProjectionCatchUpLockIdentifier::createRunning($this->contentRepositoryId, $projection::class)->value);
        if (!$lock->acquire()) {
            return;
        }
        try {
            /** @phpstan-ignore-next-line */
            [$projectionTopic, $projectionRun] = explode(':', $projection->getState()->state);
            $lines = file_exists(self::queueFilename()) ? file(self::queueFilename(), FILE_IGNORE_NEW_LINES) : [];
            $newLines = [];
            $counter = 0;
            foreach ($lines as $line) {
                $newLines[] = trim($line) . PHP_EOL;
                [$topic, $run, $lineCounter] = explode(':', trim($line));
                if ($topic !== $projectionTopic) {
                    continue;
                }
                $counter = (int)$lineCounter;
            }
            $newLines[] = implode(':', [$projectionTopic, $projectionRun, $counter + 1]) . PHP_EOL;
            file_put_contents(self::queueFilename(), $newLines);
        } finally {
            $lock->release();
        }
    }

    public static function lockStore(): PersistingStoreInterface
    {
        return new PdoStore('sqlite:/tmp/neos_deduplication_test.db');
    }

    public static function queueFilename(): string
    {
        return '/tmp/neos_deduplication_test_projection.txt';
    }
}
