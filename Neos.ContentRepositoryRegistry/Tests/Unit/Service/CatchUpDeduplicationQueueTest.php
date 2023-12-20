<?php
namespace Neos\ContentRepositoryRegistry\Tests\Unit\Service;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\ContentRepositoryRegistry\Service\CatchUpDeduplicationQueue;
use Neos\ContentRepositoryRegistry\Tests\Unit\Service\Fixture\FakeFileCatchUpTrigger;
use Neos\ContentRepositoryRegistry\Tests\Unit\Service\Fixture\FakeProjectionWithState;
use PHPUnit\Framework\TestCase;

/**
 * This is not a regular unit test, it won't run with the rest of the testsuite.
 * the following two commands after another would run the parallel tests and then the validation of results:
 * requires "brianium/paratest": "^6.11"
 *
 * "paratest Packages/Neos/Neos.ContentRepositoryRegistry/Tests/Unit/Service --functional --filter 'runParallel'  --processes 20 -c Build/BuildEssentials/PhpUnit/UnitTests.xml",
 * "paratest Packages/Neos/Neos.ContentRepositoryRegistry/Tests/Unit/Service --functional --filter 'validateEvents' -c Build/BuildEssentials/PhpUnit/UnitTests.xml"
 */
class CatchUpDeduplicationQueueTest extends TestCase
{
    public static function consistency_dataProvider(): iterable
    {
        for ($i = 0; $i < 30; $i++) {
            yield [$i];
        }
    }

    /**
     * @dataProvider consistency_dataProvider
     * @group parallel
     * @test
     */
    public function runParallel(int $run): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString('parallel');
        $randomTopics = range('a', 'c');
        for ($i = 0; $i < 10; $i++) {
            $topic = self::either(...$randomTopics) . ':' . $run;
            $queue = new CatchUpDeduplicationQueue(
                $contentRepositoryId,
                FakeFileCatchUpTrigger::lockStore(),
                new FakeFileCatchUpTrigger($contentRepositoryId)
                );
            $queue->requestCatchUp(Projections::fromArray([new FakeProjectionWithState($topic)]));
        }
        self::assertTrue(true);
    }

    /**
     * @group parallel_check
     * @test
     */
    public function validateEvents(): void
    {
        self::assertFileExists(FakeFileCatchUpTrigger::queueFilename());
        $lines = file(FakeFileCatchUpTrigger::queueFilename(), FILE_IGNORE_NEW_LINES) ?: [];
        unlink(FakeFileCatchUpTrigger::queueFilename());
        $countersPerTopic = [];
        self::assertGreaterThan(30, count($lines));
        foreach ($lines as $index => $line) {
            [$topic, $runNumber, $counter] = explode(':', $line);
            // we are ignoring the run number, it's more for diagnostic purposes, the order is not defined and can be assumed random.
            $countersPerTopic[$topic] = $countersPerTopic[$topic] ?? 0;
            self::assertSame($countersPerTopic[$topic], (int)$counter - 1, sprintf('Failed for topic "%s" in line %d:', $topic, $index + 1));
            $countersPerTopic[$topic] = (int)$counter;
        }

    }

    private static function either(...$choices): string
    {
        return (string)$choices[array_rand($choices)];
    }
}


