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
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\Security\Dto\UserId;
use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainer;
use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainerFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngine;
use Neos\ContentRepository\TestSuite\Fakes\FakeAuthProvider;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\EventStream\VirtualStreamName;
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
        FakeAuthProvider::setDefaultUserId(UserId::fromString(sprintf('Testing [pid %s]', getmypid())));
        $this->objectManager = Bootstrap::$staticObjectManager;
        $this->contentRepositoryRegistry = $this->objectManager->get(ContentRepositoryRegistry::class);
    }

    protected function onNotSuccessfulTest(\Throwable $t): void
    {
        try {
            $this->log('Start logging exception');
            $messageLines = [];
            $level = 0;
            $exception = $t;
            do {
                $level++;
                if ($level >= 8) {
                    $messageLines[] = '...Recursion';
                    break;
                }

                $exceptionFqn = $exception::class;

                $messageLines[] = <<<MESSAGE
                Class: {$exceptionFqn}
                Message: {$exception->getMessage()}
                Code: {$exception->getCode()}
                File: {$exception->getFile()}
                Line: {$exception->getLine()}

                Trace: {$exception->getTraceAsString()}
                MESSAGE;
            } while ($exception = $exception->getPrevious());
            file_put_contents(self::LOGGING_PATH, join("\n\n", $messageLines), FILE_APPEND);
            $this->log('Fished exception logging');
        } catch (\Throwable $throwable) {
            $this->log(sprintf('Failed logging exception [%s (%d)]: %s', $throwable::class, $throwable->getCode(), $throwable->getMessage()));
        }
        parent::onNotSuccessfulTest($t);
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

    final protected function getEventStore(ContentRepositoryId $contentRepositoryId): EventStoreInterface
    {
        $eventStoreAccessor = new class implements ContentRepositoryServiceFactoryInterface {
            public EventStoreInterface|null $eventStore;
            public SubscriptionEngine|null $subscriptionEngine;
            public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentRepositoryServiceInterface
            {
                $this->eventStore = $serviceFactoryDependencies->eventStore;
                $this->subscriptionEngine = $serviceFactoryDependencies->subscriptionEngine;
                return new class implements ContentRepositoryServiceInterface
                {
                };
            }
        };
        $this->contentRepositoryRegistry->buildService($contentRepositoryId, $eventStoreAccessor);
        return $eventStoreAccessor->eventStore;
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
