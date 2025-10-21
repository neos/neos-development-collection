<?php

/*
 * This file is part of the Neos.ContentRepository.BehavioralTests package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Parallel;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainer;
use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainerFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Abstract parallel test cases
 */
abstract class AbstractParallelTestCase extends TestCase // we don't use Flows functional test case as it would reset the database afterwards (see FlowEntitiesTrait)
{
    private const LOGGING_PATH = __DIR__ . '/log.txt';

    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    protected ObjectManagerInterface $objectManager;

    public function setUp(): void
    {
        $this->objectManager = Bootstrap::$staticObjectManager;
        $this->contentRepositoryRegistry = $this->objectManager->get(ContentRepositoryRegistry::class);
    }

    final protected function awaitFile(string $filename): void
    {
        $waiting = 0;
        while (!is_file($filename)) {
            usleep(1000);
            $waiting++;
            clearstatcache(true, $filename);
            if ($waiting > 60000) {
                throw new \Exception('timeout while waiting on file ' . $filename);
            }
        }
    }

    final protected function awaitFileRemoval(string $filename): void
    {
        $waiting = 0;
        while (is_file($filename)) {
            usleep(1000);
            $waiting++;
            clearstatcache(true, $filename);
            if ($waiting > 60000) {
                throw new \Exception('timeout while waiting on file ' . $filename);
            }
        }
    }

    final protected function setUpContentRepository(
        ContentRepositoryId $contentRepositoryId
    ): ContentRepository {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        /** @var ContentRepositoryMaintainer $contentRepositoryMaintainer */
        $contentRepositoryMaintainer = $this->contentRepositoryRegistry->buildService($contentRepositoryId, new ContentRepositoryMaintainerFactory());
        $contentRepositoryMaintainer->setUp();
        // reset events and projections
        $contentRepositoryMaintainer->prune();
        return $contentRepository;
    }

    final protected function log(string $message): void
    {
        file_put_contents(self::LOGGING_PATH, self::shortClassName($this::class) . ': [pid ' . getmypid() . ', time ' . time() . '] ' .  $message . PHP_EOL, FILE_APPEND);
    }

    final protected static function shortClassName(string $className): string
    {
        return substr($className, strrpos($className, '\\') + 1);
    }
}
